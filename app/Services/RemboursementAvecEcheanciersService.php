<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\PaiementCredit;
use App\Models\Compte;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RemboursementAvecEcheanciersService
{
    /**
     * Pourcentages EXACTS des intérêts hebdomadaires (16 semaines)
     * Même que dans vos échéanciers
     */
    private const POURCENTAGES_INTERETS = [
        1  => 14.4154589019438,   // Semaine 1
        2  => 12.5668588386971,   // Semaine 2
        3  => 11.5077233695784,   // Semaine 3
        4  => 10.4164781434722,   // Semaine 4
        5  => 9.292636648909,     // Semaine 5
        6  => 9.13522586294972,   // Semaine 6
        7  => 8.94327276265538,   // Semaine 7
        8  => 6.71531781361745,   // Semaine 8
        9  => 4.45038799289693,   // Semaine 9
        10 => 3.14751027755479,   // Semaine 10
        11 => 2.80571164465202,   // Semaine 11
        12 => 1.80571164465202,   // Semaine 12
        13 => 1.80571164465202,   // Semaine 13
        14 => 1.40571164465202,   // Semaine 14
        15 => 1.30571164465202,   // Semaine 15
        16 => 0.280571164465202,  // Semaine 16
    ];

    /**
     * Récupère les remboursements selon les échéanciers
     */
    public function getRemboursementsAvecEcheanciers(
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin,
        ?string $typeCredit = null
    ): Collection {
        Log::info('📅 DÉBUT CALCUL AVEC ÉCHÉANCIERS', [
            'periode' => $periode,
            'dates' => $dateDebut->format('Y-m-d') . ' au ' . $dateFin->format('Y-m-d'),
            'type_credit' => $typeCredit
        ]);

        $remboursements = collect();
        
        // 1. Crédits individuels
        if (!$typeCredit || $typeCredit === 'all' || $typeCredit === 'individuel') {
            $this->ajouterRemboursementsIndividuelsAvecEcheanciers(
                $remboursements, 
                $periode, 
                $dateDebut, 
                $dateFin
            );
        }
        
        // 2. Crédits groupe
        if (!$typeCredit || $typeCredit === 'all' || $typeCredit === 'groupe') {
            $this->ajouterRemboursementsGroupesAvecEcheanciers(
                $remboursements, 
                $periode, 
                $dateDebut, 
                $dateFin
            );
        }
        
        return $remboursements->sortBy('date_periode');
    }
    
    /**
     * Ajoute les remboursements individuels avec échéanciers
     */
    private function ajouterRemboursementsIndividuelsAvecEcheanciers(
        Collection &$remboursements,
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin
    ): void {
        $credits = Credit::where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->with(['compte', 'paiements'])
            ->get();
        
        Log::info('🔍 CRÉDITS INDIVIDUELS POUR ÉCHÉANCIERS', ['count' => $credits->count()]);
        
        foreach ($credits as $credit) {
            try {
                $this->calculerRemboursementsCreditAvecEcheancier(
                    $remboursements,
                    $credit,
                    'individuel',
                    $periode,
                    $dateDebut,
                    $dateFin
                );
            } catch (\Exception $e) {
                Log::error('Erreur crédit individuel: ' . $e->getMessage(), [
                    'credit_id' => $credit->id
                ]);
            }
        }
    }
    
    /**
     * Ajoute les remboursements groupe avec échéanciers
     */
    private function ajouterRemboursementsGroupesAvecEcheanciers(
        Collection &$remboursements,
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin
    ): void {
        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->with(['compte'])
            ->get();
        
        Log::info('🔍 CRÉDITS GROUPE POUR ÉCHÉANCIERS', ['count' => $creditsGroupe->count()]);
        
        foreach ($creditsGroupe as $creditGroupe) {
            try {
                $this->calculerRemboursementsGroupeAvecEcheancier(
                    $remboursements,
                    $creditGroupe,
                    $periode,
                    $dateDebut,
                    $dateFin
                );
            } catch (\Exception $e) {
                Log::error('Erreur crédit groupe: ' . $e->getMessage(), [
                    'groupe_id' => $creditGroupe->id
                ]);
            }
        }
    }
    
    /**
     * Calcule les remboursements pour un crédit individuel avec échéancier
     */
    private function calculerRemboursementsCreditAvecEcheancier(
        Collection &$remboursements,
        Credit $credit,
        string $typeCredit,
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin
    ): void {
        // Vérifier les dates
        if (!$credit->date_octroi) {
            Log::warning('Crédit sans date octroi', ['credit_id' => $credit->id]);
            return;
        }
        
        $dateOctroi = $credit->date_octroi;
        $datePremiereEcheance = $dateOctroi->copy()->addWeeks(2);
        
        // Si la date de fin est avant la première échéance, pas de remboursement
        if ($dateFin->lt($datePremiereEcheance)) {
            return;
        }
        
        // Calculer les valeurs de base
        $montantAccorde = floatval($credit->montant_accorde);
        $montantTotal = floatval($credit->montant_total);
        $remboursementHebdo = $credit->remboursement_hebdo ?? ($montantTotal / 16);
        $totalInterets = $montantTotal - $montantAccorde;
        
        Log::info('📊 CALCUL ÉCHÉANCIER CRÉDIT', [
            'credit_id' => $credit->id,
            'montant_accorde' => $montantAccorde,
            'montant_total' => $montantTotal,
            'remboursement_hebdo' => $remboursementHebdo,
            'total_interets' => $totalInterets,
            'date_octroi' => $dateOctroi->format('Y-m-d'),
            'premiere_echeance' => $datePremiereEcheance->format('Y-m-d')
        ]);
        
        // Pour chaque semaine (1 à 16)
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $datePremiereEcheance->copy()->addWeeks($semaine - 1);
            
            // Vérifier si la date d'échéance est dans la période demandée
            if ($dateEcheance->between($dateDebut, $dateFin)) {
                // Calculer l'intérêt selon le pourcentage de la semaine
                $pourcentageInteret = self::POURCENTAGES_INTERETS[$semaine] ?? 0;
                $montantInteret = round(($totalInterets * $pourcentageInteret) / 100, 2);
                
                // Calculer le capital (remboursement - intérêt)
                $montantCapital = round($remboursementHebdo - $montantInteret, 2);
                
                // Pour la dernière semaine, ajuster pour équilibrer
                if ($semaine == 16) {
                    // Pour s'assurer que le total capital = montant accordé
                    $capitalTotalCalcule = $this->calculerCapitalTotalPourCredit($credit, $semaine - 1);
                    $montantCapital = round($montantAccorde - $capitalTotalCalcule, 2);
                    $montantInteret = round($remboursementHebdo - $montantCapital, 2);
                    
                    // Assurer que l'intérêt n'est pas négatif
                    if ($montantInteret < 0) {
                        $montantInteret = 0;
                        $montantCapital = $remboursementHebdo;
                    }
                }
                
                // Statut
                $statut = $this->determinerStatutAvecEcheance($credit, $dateEcheance, $remboursementHebdo);
                
                $remboursements->push([
                    'credit_id' => $credit->id,
                    'type_credit' => $typeCredit,
                    'numero_compte' => $credit->compte->numero_compte ?? 'N/A',
                    'nom_complet' => $this->getNomCompletCredit($credit),
                    'periode' => $this->formatPeriodeAvecEcheance($dateEcheance, $periode, $semaine),
                    'date_periode' => $dateEcheance->copy(),
                    'montant_total' => $remboursementHebdo,
                    'capital' => $montantCapital,
                    'interets' => $montantInteret,
                    'pourcentage_capital' => $remboursementHebdo > 0 ? 
                        round(($montantCapital / $remboursementHebdo) * 100, 2) : 0,
                    'pourcentage_interets' => $remboursementHebdo > 0 ? 
                        round(($montantInteret / $remboursementHebdo) * 100, 2) : 0,
                    'statut' => $statut,
                    'numero_echeance' => $semaine,
                    'pourcentage_interet_applique' => $pourcentageInteret,
                ]);
            }
        }
    }
    
    /**
     * Calcule les remboursements pour un crédit groupe avec échéancier
     */
    private function calculerRemboursementsGroupeAvecEcheancier(
        Collection &$remboursements,
        CreditGroupe $creditGroupe,
        string $periode,
        Carbon $dateDebut,
        Carbon $dateFin
    ): void {
        // Vérifier les dates
        if (!$creditGroupe->date_octroi) {
            Log::warning('Crédit groupe sans date octroi', ['groupe_id' => $creditGroupe->id]);
            return;
        }
        
        $dateOctroi = $creditGroupe->date_octroi;
        $datePremiereEcheance = $dateOctroi->copy()->addWeeks(2);
        
        // Si la date de fin est avant la première échéance, pas de remboursement
        if ($dateFin->lt($datePremiereEcheance)) {
            return;
        }
        
        // Calculer les valeurs de base
        $montantAccorde = floatval($creditGroupe->montant_accorde);
        $montantTotal = floatval($creditGroupe->montant_total);
        $remboursementHebdo = $creditGroupe->remboursement_hebdo_total ?? ($montantTotal / 16);
        $totalInterets = $montantTotal - $montantAccorde;
        
        Log::info('📊 CALCUL ÉCHÉANCIER GROUPE', [
            'groupe_id' => $creditGroupe->id,
            'montant_accorde' => $montantAccorde,
            'montant_total' => $montantTotal,
            'remboursement_hebdo' => $remboursementHebdo,
            'total_interets' => $totalInterets
        ]);
        
        // Pour chaque semaine (1 à 16)
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $datePremiereEcheance->copy()->addWeeks($semaine - 1);
            
            // Vérifier si la date d'échéance est dans la période demandée
            if ($dateEcheance->between($dateDebut, $dateFin)) {
                // Calculer l'intérêt selon le pourcentage de la semaine
                $pourcentageInteret = self::POURCENTAGES_INTERETS[$semaine] ?? 0;
                $montantInteret = round(($totalInterets * $pourcentageInteret) / 100, 2);
                
                // Calculer le capital (remboursement - intérêt)
                $montantCapital = round($remboursementHebdo - $montantInteret, 2);
                
                // Pour la dernière semaine, ajuster pour équilibrer
                if ($semaine == 16) {
                    $capitalTotalCalcule = $this->calculerCapitalTotalPourGroupe($creditGroupe, $semaine - 1);
                    $montantCapital = round($montantAccorde - $capitalTotalCalcule, 2);
                    $montantInteret = round($remboursementHebdo - $montantCapital, 2);
                    
                    // Assurer que l'intérêt n'est pas négatif
                    if ($montantInteret < 0) {
                        $montantInteret = 0;
                        $montantCapital = $remboursementHebdo;
                    }
                }
                
                // Statut
                $statut = $this->determinerStatutGroupeAvecEcheance($creditGroupe, $dateEcheance, $remboursementHebdo);
                
                $remboursements->push([
                    'credit_id' => $creditGroupe->id,
                    'type_credit' => 'groupe',
                    'numero_compte' => $creditGroupe->compte->numero_compte ?? 'GS' . $creditGroupe->id,
                    'nom_complet' => $creditGroupe->compte->nom ?? 'Groupe ' . $creditGroupe->id,
                    'periode' => $this->formatPeriodeAvecEcheance($dateEcheance, $periode, $semaine),
                    'date_periode' => $dateEcheance->copy(),
                    'montant_total' => $remboursementHebdo,
                    'capital' => $montantCapital,
                    'interets' => $montantInteret,
                    'pourcentage_capital' => $remboursementHebdo > 0 ? 
                        round(($montantCapital / $remboursementHebdo) * 100, 2) : 0,
                    'pourcentage_interets' => $remboursementHebdo > 0 ? 
                        round(($montantInteret / $remboursementHebdo) * 100, 2) : 0,
                    'statut' => $statut,
                    'numero_echeance' => $semaine,
                    'pourcentage_interet_applique' => $pourcentageInteret,
                ]);
            }
        }
    }
    
    /**
     * Calcule le capital total déjà calculé pour un crédit
     */
    private function calculerCapitalTotalPourCredit(Credit $credit, int $semainesCalculees): float
    {
        $montantAccorde = floatval($credit->montant_accorde);
        $montantTotal = floatval($credit->montant_total);
        $remboursementHebdo = $credit->remboursement_hebdo ?? ($montantTotal / 16);
        $totalInterets = $montantTotal - $montantAccorde;
        
        $capitalTotal = 0;
        
        for ($semaine = 1; $semaine <= $semainesCalculees; $semaine++) {
            $pourcentageInteret = self::POURCENTAGES_INTERETS[$semaine] ?? 0;
            $montantInteret = ($totalInterets * $pourcentageInteret) / 100;
            $montantCapital = $remboursementHebdo - $montantInteret;
            $capitalTotal += $montantCapital;
        }
        
        return $capitalTotal;
    }
    
    /**
     * Calcule le capital total déjà calculé pour un groupe
     */
    private function calculerCapitalTotalPourGroupe(CreditGroupe $creditGroupe, int $semainesCalculees): float
    {
        $montantAccorde = floatval($creditGroupe->montant_accorde);
        $montantTotal = floatval($creditGroupe->montant_total);
        $remboursementHebdo = $creditGroupe->remboursement_hebdo_total ?? ($montantTotal / 16);
        $totalInterets = $montantTotal - $montantAccorde;
        
        $capitalTotal = 0;
        
        for ($semaine = 1; $semaine <= $semainesCalculees; $semaine++) {
            $pourcentageInteret = self::POURCENTAGES_INTERETS[$semaine] ?? 0;
            $montantInteret = ($totalInterets * $pourcentageInteret) / 100;
            $montantCapital = $remboursementHebdo - $montantInteret;
            $capitalTotal += $montantCapital;
        }
        
        return $capitalTotal;
    }
    
    /**
     * Formate la période avec échéance
     */
    private function formatPeriodeAvecEcheance(Carbon $date, string $periode, int $echeance): string
    {
        switch ($periode) {
            case 'jour':
                return "Éch. {$echeance} - " . $date->format('d/m/Y');
            case 'semaine':
                return "Semaine {$echeance} (" . $date->format('d/m/Y') . ")";
            case 'mois':
                return "Mois " . $date->format('m/Y') . " - Éch. {$echeance}";
            default:
                return "Échéance {$echeance}";
        }
    }
    
    /**
     * Récupère le nom complet pour un crédit
     */
    private function getNomCompletCredit(Credit $credit): string
    {
        if (!$credit->compte) {
            return 'Inconnu';
        }
        
        $nom = $credit->compte->nom ?? '';
        $prenom = $credit->compte->prenom ?? '';
        return trim($nom . ' ' . $prenom);
    }
    
    /**
     * Détermine le statut avec échéance pour crédit individuel
     */
    private function determinerStatutAvecEcheance(Credit $credit, Carbon $dateEcheance, float $montantAttendu): string
    {
        // Si la date est dans le passé
        if ($dateEcheance->lt(now())) {
            // Vérifier les paiements autour de cette date
            $montantPaye = $this->getMontantPayePourEcheance($credit, $dateEcheance);
            
            if ($montantPaye >= $montantAttendu * 0.99) {
                return 'Payé';
            } elseif ($montantPaye > 0) {
                return 'Partiel';
            } else {
                return 'En retard';
            }
        }
        
        // Si date aujourd'hui
        if ($dateEcheance->isToday()) {
            return 'À payer aujourd\'hui';
        }
        
        // Date dans le futur
        return 'À venir';
    }
    
    /**
     * Détermine le statut avec échéance pour groupe
     */
    private function determinerStatutGroupeAvecEcheance(CreditGroupe $creditGroupe, Carbon $dateEcheance, float $montantAttendu): string
    {
        // Si la date est dans le passé
        if ($dateEcheance->lt(now())) {
            // Vérifier les paiements autour de cette date
            $montantPaye = $this->getMontantPayePourEcheanceGroupe($creditGroupe, $dateEcheance);
            
            if ($montantPaye >= $montantAttendu * 0.99) {
                return 'Payé';
            } elseif ($montantPaye > 0) {
                return 'Partiel';
            } else {
                return 'En retard';
            }
        }
        
        // Si date aujourd'hui
        if ($dateEcheance->isToday()) {
            return 'À payer aujourd\'hui';
        }
        
        // Date dans le futur
        return 'À venir';
    }
    
    /**
     * Récupère le montant payé pour une échéance (crédit individuel)
     */
    private function getMontantPayePourEcheance(Credit $credit, Carbon $dateEcheance): float
    {
        // Rechercher les paiements autour de la date d'échéance (±1 semaine)
        $dateDebut = $dateEcheance->copy()->subWeek();
        $dateFin = $dateEcheance->copy()->addWeek();
        
        return PaiementCredit::where('credit_id', $credit->id)
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->sum('montant_paye');
    }
    
    /**
     * Récupère le montant payé pour une échéance (groupe)
     */
    private function getMontantPayePourEcheanceGroupe(CreditGroupe $creditGroupe, Carbon $dateEcheance): float
    {
        // Rechercher les paiements autour de la date d'échéance (±1 semaine)
        $dateDebut = $dateEcheance->copy()->subWeek();
        $dateFin = $dateEcheance->copy()->addWeek();
        
        return PaiementCredit::where('credit_groupe_id', $creditGroupe->id)
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->sum('montant_paye');
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
                'total_interets_calcules' => 0,
            ];
        }
        
        $creditsIds = $remboursements->pluck('credit_id')->unique();
        
        // Vérification des calculs
        $totalCapital = $remboursements->sum('capital');
        $totalInterets = $remboursements->sum('interets');
        $totalRemboursement = $remboursements->sum('montant_total');
        
        Log::info('✅ VÉRIFICATION CALCULS', [
            'total_remboursement' => $totalRemboursement,
            'total_capital' => $totalCapital,
            'total_interets' => $totalInterets,
            'difference' => $totalRemboursement - ($totalCapital + $totalInterets)
        ]);
        
        return [
            'total_remboursement' => round($totalRemboursement, 2),
            'total_capital' => round($totalCapital, 2),
            'total_interets' => round($totalInterets, 2),
            'nombre_periodes' => $remboursements->count(),
            'nombre_credits' => $creditsIds->count(),
            'moyenne_capital' => round($remboursements->avg('pourcentage_capital'), 2),
            'moyenne_interets' => round($remboursements->avg('pourcentage_interets'), 2),
            'total_interets_calcules' => round($totalInterets, 2),
        ];
    }
}