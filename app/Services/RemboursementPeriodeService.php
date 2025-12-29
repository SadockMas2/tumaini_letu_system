<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\PaiementCredit;
use App\Models\Compte;
use App\Helpers\CreditRepartitionHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RemboursementPeriodeService
{
    /**
     * Récupère tous les remboursements par période
     */
    public function getRemboursementsParPeriode(
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin,
        ?string $typeCredit = null
    ): Collection {
        Log::info('📊 DÉBUT CALCUL REMBOURSEMENTS PAR PÉRIODE', [
            'periode' => $periode,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'type_credit' => $typeCredit
        ]);

        // Récupérer les crédits approuvés
        $credits = $this->getCreditsApprouves($typeCredit);
        
        Log::info('📈 CRÉDITS TROUVÉS', ['count' => $credits->count()]);

        $remboursements = collect();
        
        foreach ($credits as $credit) {
            try {
                $remboursementsCredit = $this->calculerRemboursementsCredit(
                    $credit, 
                    $periode, 
                    $dateDebut, 
                    $dateFin
                );
                
                $remboursements = $remboursements->merge($remboursementsCredit);
                
            } catch (\Exception $e) {
                Log::error('Erreur calcul remboursements crédit: ' . $e->getMessage(), [
                    'credit_id' => $credit->id ?? 'N/A',
                    'type' => isset($credit->type_credit) ? $credit->type_credit : 'groupe'
                ]);
            }
        }

        Log::info('✅ CALCUL TERMINÉ', ['remboursements_count' => $remboursements->count()]);
        
        return $remboursements->sortBy('date_periode');
    }
    
    /**
     * Récupère les crédits approuvés
     */
    private function getCreditsApprouves(?string $typeCredit): Collection
    {
        $credits = collect();
        
        // Crédits individuels
        if (!$typeCredit || $typeCredit === 'individuel') {
            $creditsIndividuels = Credit::where('statut_demande', 'approuve')
                ->with(['compte', 'paiements'])
                ->where('montant_total', '>', 0)
                ->get();
                
            Log::info('📋 CRÉDITS INDIVIDUELS', ['count' => $creditsIndividuels->count()]);
            
            foreach ($creditsIndividuels as $credit) {
                // Ajouter les informations manquantes
                $credit->type_credit = 'individuel';
                if (!$credit->date_octroi && $credit->created_at) {
                    $credit->date_octroi = $credit->created_at;
                }
                if (!$credit->date_echeance && $credit->date_octroi) {
                    $credit->date_echeance = $credit->date_octroi->copy()->addMonths(4);
                }
                $credits->push($credit);
            }
        }
        
        // Crédits groupe
        if (!$typeCredit || $typeCredit === 'groupe') {
            $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
                ->with(['compte'])
                ->where('montant_total', '>', 0)
                ->get();
                
            Log::info('📋 CRÉDITS GROUPE', ['count' => $creditsGroupe->count()]);
            
            foreach ($creditsGroupe as $creditGroupe) {
                // Créer un objet compatible
                $credit = new \stdClass();
                $credit->id = $creditGroupe->id;
                $credit->type_credit = 'groupe';
                $credit->montant_accorde = $creditGroupe->montant_accorde;
                $credit->montant_total = $creditGroupe->montant_total;
                $credit->remboursement_hebdo = $creditGroupe->remboursement_hebdo_total ?? ($creditGroupe->montant_total / 16);
                $credit->date_octroi = $creditGroupe->date_octroi;
                $credit->date_echeance = $creditGroupe->date_echeance;
                $credit->compte = $creditGroupe->compte;
                $credit->paiements = PaiementCredit::where('credit_groupe_id', $creditGroupe->id)->get();
                
                $credits->push($credit);
            }
        }

        return $credits;
    }
    
    /**
     * Calcule les remboursements pour un crédit
     */
    private function calculerRemboursementsCredit(
        $credit,
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin
    ): Collection {
        $remboursements = collect();
        
        // Si pas de date d'octroi, utiliser aujourd'hui - 2 semaines pour le calcul
        $dateOctroi = $credit->date_octroi ?? now()->subWeeks(2);
        $datePremiereEcheance = $dateOctroi->copy()->addWeeks(2);
        
        // Si la date de début est avant la première échéance, commencer à la première échéance
        if ($dateDebut->lt($datePremiereEcheance)) {
            $dateCourante = $datePremiereEcheance->copy();
        } else {
            $dateCourante = $dateDebut->copy();
        }
        
        // Pour chaque période dans l'intervalle
        while ($dateCourante <= $dateFin) {
            // Calculer le numéro d'échéance
            $numeroEcheance = $this->calculerNumeroEcheance($datePremiereEcheance, $dateCourante);
            
            // Si on est dans la période de remboursement (1-16 semaines)
            if ($numeroEcheance >= 1 && $numeroEcheance <= 16) {
                // Calculer le montant du remboursement
                $montantTotal = $this->getMontantRemboursementHebdo($credit);
                
                // Calculer la répartition capital/intérêts
                $repartition = $this->calculerRepartition($credit, $montantTotal, $numeroEcheance);
                
                // Vérifier si déjà payé
                $statut = $this->determinerStatut($credit, $dateCourante, $periode, $montantTotal);
                
                // Formater les données
                $remboursements->push([
                    'credit_id' => $credit->id,
                    'type_credit' => $credit->type_credit,
                    'numero_compte' => $this->getNumeroCompte($credit),
                    'nom_complet' => $this->getNomComplet($credit),
                    'periode' => $this->formatPeriode($dateCourante, $periode, $numeroEcheance),
                    'date_periode' => $dateCourante->copy(),
                    'montant_total' => $montantTotal,
                    'capital' => $repartition['capital'],
                    'interets' => $repartition['interets'],
                    'pourcentage_capital' => $montantTotal > 0 ? 
                        round(($repartition['capital'] / $montantTotal) * 100, 2) : 0,
                    'pourcentage_interets' => $montantTotal > 0 ? 
                        round(($repartition['interets'] / $montantTotal) * 100, 2) : 0,
                    'statut' => $statut,
                    'numero_echeance' => $numeroEcheance,
                ]);
            }
            
            // Passer à la période suivante
            $dateCourante = $this->incrementerDate($dateCourante, $periode);
        }
        
        return $remboursements;
    }
    
    /**
     * Calcule le numéro d'échéance
     */
    private function calculerNumeroEcheance(Carbon $datePremiereEcheance, Carbon $date): int
    {
        $semainesEcoulees = $datePremiereEcheance->diffInWeeks($date);
        return $semainesEcoulees + 1;
    }
    
    /**
     * Récupère le montant de remboursement hebdomadaire
     */
    private function getMontantRemboursementHebdo($credit): float
    {
        if (isset($credit->remboursement_hebdo) && $credit->remboursement_hebdo > 0) {
            return floatval($credit->remboursement_hebdo);
        }
        
        // Calcul par défaut
        return floatval($credit->montant_total) / 16;
    }
    
    /**
     * Calcule la répartition capital/intérêts
     */
    private function calculerRepartition($credit, float $montantTotal, int $numeroEcheance): array
    {
        // Calcul simple : capital = montant accordé / 16, intérêts = reste
        $capitalHebdo = floatval($credit->montant_accorde) / 16;
        $interetsHebdo = $montantTotal - $capitalHebdo;
        
        return [
            'capital' => $capitalHebdo,
            'interets' => $interetsHebdo
        ];
    }
    
    /**
     * Formate le libellé de la période
     */
    private function formatPeriode(Carbon $date, string $periode, int $echeance): string
    {
        switch ($periode) {
            case 'jour':
                return $date->translatedFormat('l d/m/Y');
            case 'semaine':
                return "Semaine {$echeance} (" . $date->format('d/m/Y') . ")";
            case 'mois':
                return $date->translatedFormat('F Y');
            default:
                return "Période {$echeance}";
        }
    }
    
    /**
     * Incrémente la date selon la période
     */
    private function incrementerDate(Carbon $date, string $periode): Carbon
    {
        switch ($periode) {
            case 'jour':
                return $date->addDay();
            case 'semaine':
                return $date->addWeek();
            case 'mois':
                return $date->addMonth();
            default:
                return $date->addWeek();
        }
    }
    
    /**
     * Récupère le numéro de compte
     */
    private function getNumeroCompte($credit): string
    {
        if (isset($credit->compte) && $credit->compte) {
            return $credit->compte->numero_compte ?? 'N/A';
        }
        
        return 'N/A';
    }
    
    /**
     * Récupère le nom complet
     */
    private function getNomComplet($credit): string
    {
        if (isset($credit->compte) && $credit->compte) {
            if ($credit->type_credit === 'groupe') {
                return $credit->compte->nom ?? 'Groupe';
            } else {
                $nom = $credit->compte->nom ?? '';
                $prenom = $credit->compte->prenom ?? '';
                return trim($nom . ' ' . $prenom);
            }
        }
        
        return 'Inconnu';
    }
    
    /**
     * Détermine le statut du remboursement
     */
    private function determinerStatut($credit, Carbon $date, string $periode, float $montantAttendu): string
    {
        // Si la date est dans le passé
        if ($date->lt(now())) {
            // Calculer le montant déjà payé pour cette période
            $montantDejaPaye = $this->calculerMontantDejaPaye($credit, $date, $periode);
            
            if ($montantDejaPaye >= $montantAttendu) {
                return 'Payé';
            } elseif ($montantDejaPaye > 0) {
                return 'Partiel';
            } else {
                return 'En retard';
            }
        }
        
        return 'À venir';
    }
    
    /**
     * Calcule le montant déjà payé pour une période
     */
    private function calculerMontantDejaPaye($credit, Carbon $date, string $periode): float
    {
        $datesPeriode = $this->getDatesPeriode($date, $periode);
        
        if ($credit->type_credit === 'individuel') {
            return PaiementCredit::where('credit_id', $credit->id)
                ->whereBetween('date_paiement', [$datesPeriode['debut'], $datesPeriode['fin']])
                ->sum('montant_paye');
        } else {
            return PaiementCredit::where('credit_groupe_id', $credit->id)
                ->whereBetween('date_paiement', [$datesPeriode['debut'], $datesPeriode['fin']])
                ->sum('montant_paye');
        }
    }
    
    /**
     * Détermine les dates d'une période
     */
    private function getDatesPeriode(Carbon $date, string $periode): array
    {
        switch ($periode) {
            case 'jour':
                return [
                    'debut' => $date->copy()->startOfDay(),
                    'fin' => $date->copy()->endOfDay()
                ];
            case 'semaine':
                return [
                    'debut' => $date->copy()->startOfWeek(),
                    'fin' => $date->copy()->endOfWeek()
                ];
            case 'mois':
                return [
                    'debut' => $date->copy()->startOfMonth(),
                    'fin' => $date->copy()->endOfMonth()
                ];
            default:
                return [
                    'debut' => $date->copy()->startOfWeek(),
                    'fin' => $date->copy()->endOfWeek()
                ];
        }
    }
    
    /**
     * Calcule les totaux
     */
    public function calculerTotaux(Collection $remboursements): array
    {
        if ($remboursements->isEmpty()) {
            return [
                'total_remboursement' => 0,
                'total_capital' => 0,
                'total_interets' => 0,
                'nombre_periodes' => 0,
                'nombre_credits' => 0,
                'moyenne_capital' => 0,
                'moyenne_interets' => 0,
            ];
        }
        
        $creditsIds = $remboursements->pluck('credit_id')->unique();
        
        return [
            'total_remboursement' => $remboursements->sum('montant_total'),
            'total_capital' => $remboursements->sum('capital'),
            'total_interets' => $remboursements->sum('interets'),
            'nombre_periodes' => $remboursements->count(),
            'nombre_credits' => $creditsIds->count(),
            'moyenne_capital' => $remboursements->avg('pourcentage_capital'),
            'moyenne_interets' => $remboursements->avg('pourcentage_interets'),
        ];
    }
}