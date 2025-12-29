<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditGroupe;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RemboursementDirectService
{
// Ajoutez des valeurs par défaut pour maintenir la compatibilité
public function getRemboursementsDirects($periode, $dateDebut, $dateFin, $typeCredit, $agentId = null, $superviseurId = null)
{
    $remboursements = new Collection();
    
    // Log des paramètres
    Log::info('📊 PARAMÈTRES DE FILTRAGE', [
        'periode' => $periode,
        'dateDebut' => $dateDebut,
        'dateFin' => $dateFin,
        'typeCredit' => $typeCredit,
        'agentId' => $agentId,
        'superviseurId' => $superviseurId
    ]);
    
    // Crédits individuels
    if ($typeCredit === 'all' || $typeCredit === 'individuel') {
        $query = Credit::where('statut_demande', 'approuve')
            ->with(['compte', 'paiements', 'agent', 'superviseur']);
            
        // Filtrer par agent si spécifié et différent de 'all'
        if ($agentId && $agentId !== 'all') {
            $query->where('agent_id', $agentId);
            Log::info('🔍 FILTRE AGENT APPLIQUÉ', ['agent_id' => $agentId]);
        }
        
        // Filtrer par superviseur si spécifié et différent de 'all'
        if ($superviseurId && $superviseurId !== 'all') {
            $query->where('superviseur_id', $superviseurId);
            Log::info('🔍 FILTRE SUPERVISEUR APPLIQUÉ', ['superviseur_id' => $superviseurId]);
        }
        
        $credits = $query->get();
        
        Log::info('📋 CRÉDITS INDIVIDUELS TROUVÉS', ['count' => $credits->count()]);
                
        foreach ($credits as $credit) {
            $remboursements = $remboursements->merge(
                $this->genererEcheancesCreditIndividuel($credit, $dateDebut, $dateFin, $periode)
            );
        }
    }
    
    // Crédits groupe
    if ($typeCredit === 'all' || $typeCredit === 'groupe') {
        $query = CreditGroupe::where('statut_demande', 'approuve')
            ->with(['compte', 'agent', 'superviseur']);
            
        // Filtrer par agent si spécifié et différent de 'all'
        if ($agentId && $agentId !== 'all') {
            $query->where('agent_id', $agentId);
        }
        
        // Filtrer par superviseur si spécifié et différent de 'all'
        if ($superviseurId && $superviseurId !== 'all') {
            $query->where('superviseur_id', $superviseurId);
        }
        
        $creditsGroupe = $query->get();
        
        Log::info('📋 CRÉDITS GROUPE TROUVÉS', ['count' => $creditsGroupe->count()]);
                
        foreach ($creditsGroupe as $credit) {
            $remboursements = $remboursements->merge(
                $this->genererEcheancesCreditGroupe($credit, $dateDebut, $dateFin, $periode)
            );
        }
    }
    
    // Filtrer par période
    $resultat = $remboursements->filter(function ($item) use ($dateDebut, $dateFin) {
        return $item['date_periode']->between($dateDebut, $dateFin);
    })->sortBy('date_periode');
    
    Log::info('✅ REMBOURSEMENTS FILTRÉS', ['count' => $resultat->count()]);
    
    return $resultat;
}
    
private function genererEcheancesCreditIndividuel($credit, $dateDebut, $dateFin, $periode)
{
    $echeances = [];
    
    if (!$credit->date_octroi) {
        Log::warning('Crédit sans date octroi', ['credit_id' => $credit->id]);
        return collect();
    }
    
    $dateDebutRemboursement = $credit->date_octroi->copy()->addWeeks(2);
    
    for ($semaine = 1; $semaine <= 16; $semaine++) {
        $dateEcheance = $dateDebutRemboursement->copy()->addWeeks($semaine - 1);
        
        if ($dateEcheance->between($dateDebut, $dateFin)) {
            $repartition = $this->calculerRepartitionPourcentageIndividuel($credit, $semaine);
            
            if (!$repartition) {
                continue;
            }
            
            $echeances[] = [
                'periode' => "Semaine {$semaine}",
                'date_periode' => $dateEcheance,
                'numero_compte' => $credit->compte->numero_compte ?? 'N/A',
                'type_credit' => 'individuel',
                'nom_complet' => ($credit->compte->nom ?? '') . ' ' . ($credit->compte->prenom ?? ''),
                'montant_total' => $repartition['montant_total'] ?? 0,
                'capital' => $repartition['capital'] ?? 0,
                'interets' => $repartition['interets'] ?? 0,
                'pourcentage_capital' => $repartition['pourcentage_capital'] ?? 0,
                'pourcentage_interets' => $repartition['pourcentage_interets'] ?? 0,
                'agent_nom' => $credit->agent ? $credit->agent->name : ($credit->agent_id ? 'Agent #' . $credit->agent_id : 'N/A'),
                'agent_id' => $credit->agent_id,
                'statut' => $this->determinerStatutIndividuel($dateEcheance, $credit, $semaine),
            ];
        }
    }
    
    return collect($echeances);
}

private function genererEcheancesCreditGroupe($creditGroupe, $dateDebut, $dateFin, $periode)
{
    $echeances = [];
    
    if (!$creditGroupe->date_octroi) {
        Log::warning('Crédit groupe sans date octroi', ['groupe_id' => $creditGroupe->id]);
        return collect();
    }
    
    $dateDebutRemboursement = $creditGroupe->date_octroi->copy()->addWeeks(2);
    
    for ($semaine = 1; $semaine <= 16; $semaine++) {
        $dateEcheance = $dateDebutRemboursement->copy()->addWeeks($semaine - 1);
        
        if ($dateEcheance->between($dateDebut, $dateFin)) {
            $repartition = $this->calculerRepartitionPourcentageGroupe($creditGroupe, $semaine);
            
            if (!$repartition) {
                continue;
            }
            
            $echeances[] = [
                'periode' => "Semaine {$semaine}",
                'date_periode' => $dateEcheance,
                'numero_compte' => $creditGroupe->compte->numero_compte ?? 'GS' . $creditGroupe->id,
                'type_credit' => 'groupe',
                'nom_complet' => $creditGroupe->compte->nom ?? 'Groupe ' . $creditGroupe->id,
                'montant_total' => $repartition['montant_total'] ?? 0,
                'capital' => $repartition['capital'] ?? 0,
                'interets' => $repartition['interets'] ?? 0,
                'pourcentage_capital' => $repartition['pourcentage_capital'] ?? 0,
                'pourcentage_interets' => $repartition['pourcentage_interets'] ?? 0,
                'agent_nom' => $creditGroupe->agent ? $creditGroupe->agent->name : ($creditGroupe->agent_id ? 'Agent #' . $creditGroupe->agent_id : 'N/A'),
                'agent_id' => $creditGroupe->agent_id,
                'statut' => $this->determinerStatutGroupe($dateEcheance, $creditGroupe, $semaine),
            ];
        }
    }
    
    return collect($echeances);
}
    
    // NOUVELLE MÉTHODE POUR LES GROUPES
    // private function genererEcheancesCreditGroupe($creditGroupe, $dateDebut, $dateFin, $periode)
    // {
    //     $echeances = [];
        
    //     // Vérifier si la date d'octroi existe
    //     if (!$creditGroupe->date_octroi) {
    //         Log::warning('Crédit groupe sans date octroi', ['groupe_id' => $creditGroupe->id]);
    //         return collect();
    //     }
        
    //     $dateDebutRemboursement = $creditGroupe->date_octroi->copy()->addWeeks(2);
        
    //     for ($semaine = 1; $semaine <= 16; $semaine++) {
    //         $dateEcheance = $dateDebutRemboursement->copy()->addWeeks($semaine - 1);
            
    //         // Ne générer que si dans la période demandée
    //         if ($dateEcheance->between($dateDebut, $dateFin)) {
    //             $repartition = $this->calculerRepartitionPourcentageGroupe($creditGroupe, $semaine);
                
    //             if (!$repartition) {
    //                 continue;
    //             }
                
    //             $echeances[] = [
    //                 'periode' => "Semaine {$semaine}",
    //                 'date_periode' => $dateEcheance,
    //                 'numero_compte' => $creditGroupe->compte->numero_compte ?? 'GS' . $creditGroupe->id,
    //                 'type_credit' => 'groupe',
    //                 'nom_complet' => $creditGroupe->compte->nom ?? 'Groupe ' . $creditGroupe->id,
    //                 'montant_total' => $repartition['montant_total'] ?? 0,
    //                 'capital' => $repartition['capital'] ?? 0,
    //                 'interets' => $repartition['interets'] ?? 0,
    //                 'pourcentage_capital' => $repartition['pourcentage_capital'] ?? 0,
    //                 'pourcentage_interets' => $repartition['pourcentage_interets'] ?? 0,
    //                 'statut' => $this->determinerStatutGroupe($dateEcheance, $creditGroupe, $semaine),
    //             ];
    //         }
    //     }
        
    //     return collect($echeances);
    // }
    
    private function calculerRepartitionPourcentageIndividuel($credit, $semaine)
    {
        $montantHebdo = $credit->remboursement_hebdo ?? 0;
        
        if ($montantHebdo <= 0) {
            Log::warning('Crédit sans remboursement hebdo', ['credit_id' => $credit->id]);
            return null;
        }
        
        // Pourcentages d'intérêts
        $pourcentageInterets = [
            14.4154589019438, 12.5668588386971, 11.5077233695784, 10.4164781434722,
            9.292636648909, 9.13522586294972, 8.94327276265538, 6.71531781361745,
            4.45038799289693, 3.14751027755479, 2.80571164465202, 1.80571164465202,
            1.80571164465202, 1.40571164465202, 1.30571164465202, 0.280571164465202
        ];
        
        if (!isset($pourcentageInterets[$semaine - 1])) {
            Log::warning('Pourcentage non trouvé pour semaine', ['semaine' => $semaine, 'credit_id' => $credit->id]);
            return null;
        }
        
        $totalInterets = ($credit->montant_total ?? 0) - ($credit->montant_accorde ?? 0);
        
        if ($totalInterets <= 0) {
            // Si pas d'intérêts, tout est capital
            return [
                'montant_total' => $montantHebdo,
                'capital' => $montantHebdo,
                'interets' => 0,
                'pourcentage_capital' => 100,
                'pourcentage_interets' => 0,
            ];
        }
        
        $interetSemaine = ($totalInterets * $pourcentageInterets[$semaine - 1]) / 100;
        $capitalSemaine = $montantHebdo - $interetSemaine;
        
        // S'assurer que les valeurs sont positives
        $capitalSemaine = max(0, $capitalSemaine);
        $interetSemaine = max(0, $interetSemaine);
        
        // Ajuster si nécessaire
        if ($capitalSemaine + $interetSemaine != $montantHebdo) {
            $ajustement = $montantHebdo - ($capitalSemaine + $interetSemaine);
            $interetSemaine += $ajustement;
        }
        
        return [
            'montant_total' => $montantHebdo,
            'capital' => $capitalSemaine,
            'interets' => $interetSemaine,
            'pourcentage_capital' => ($montantHebdo > 0) ? ($capitalSemaine / $montantHebdo) * 100 : 0,
            'pourcentage_interets' => ($montantHebdo > 0) ? ($interetSemaine / $montantHebdo) * 100 : 0,
        ];
    }
    
    // NOUVELLE MÉTHODE POUR LES GROUPES
    private function calculerRepartitionPourcentageGroupe($creditGroupe, $semaine)
    {
        $montantHebdo = $creditGroupe->remboursement_hebdo_total ?? 0;
        
        if ($montantHebdo <= 0) {
            Log::warning('Crédit groupe sans remboursement hebdo', ['groupe_id' => $creditGroupe->id]);
            return null;
        }
        
        // Mêmes pourcentages que pour les crédits individuels
        $pourcentageInterets = [
            14.4154589019438, 12.5668588386971, 11.5077233695784, 10.4164781434722,
            9.292636648909, 9.13522586294972, 8.94327276265538, 6.71531781361745,
            4.45038799289693, 3.14751027755479, 2.80571164465202, 1.80571164465202,
            1.80571164465202, 1.40571164465202, 1.30571164465202, 0.280571164465202
        ];
        
        if (!isset($pourcentageInterets[$semaine - 1])) {
            Log::warning('Pourcentage non trouvé pour semaine (groupe)', ['semaine' => $semaine, 'groupe_id' => $creditGroupe->id]);
            return null;
        }
        
        $totalInterets = ($creditGroupe->montant_total ?? 0) - ($creditGroupe->montant_accorde ?? 0);
        
        if ($totalInterets <= 0) {
            // Si pas d'intérêts, tout est capital
            return [
                'montant_total' => $montantHebdo,
                'capital' => $montantHebdo,
                'interets' => 0,
                'pourcentage_capital' => 100,
                'pourcentage_interets' => 0,
            ];
        }
        
        $interetSemaine = ($totalInterets * $pourcentageInterets[$semaine - 1]) / 100;
        $capitalSemaine = $montantHebdo - $interetSemaine;
        
        // S'assurer que les valeurs sont positives
        $capitalSemaine = max(0, $capitalSemaine);
        $interetSemaine = max(0, $interetSemaine);
        
        // Ajuster si nécessaire
        if ($capitalSemaine + $interetSemaine != $montantHebdo) {
            $ajustement = $montantHebdo - ($capitalSemaine + $interetSemaine);
            $interetSemaine += $ajustement;
        }
        
        return [
            'montant_total' => $montantHebdo,
            'capital' => $capitalSemaine,
            'interets' => $interetSemaine,
            'pourcentage_capital' => ($montantHebdo > 0) ? ($capitalSemaine / $montantHebdo) * 100 : 0,
            'pourcentage_interets' => ($montantHebdo > 0) ? ($interetSemaine / $montantHebdo) * 100 : 0,
        ];
    }
    
   private function determinerStatutIndividuel($dateEcheance, $credit, $semaine)
{
    $aujourdhui = now();
    
    // Si la date d'échéance est dans le futur
    if ($aujourdhui->lt($dateEcheance)) {
        return 'À venir';
    }
    
    // Si c'est le jour même
    if ($aujourdhui->isSameDay($dateEcheance)) {
        // Vérifier si payé
        $paiements = $credit->paiements ?? collect();
        $montantTotalPaye = $paiements->sum('montant_paye');
        $montantTotalAttendu = ($semaine) * ($credit->remboursement_hebdo ?? 0);
        
        return ($montantTotalPaye >= $montantTotalAttendu) ? 'Payé' : 'En cours';
    }
    
    // Si la date est dépassée
    // Vérifier si payé
    $paiements = $credit->paiements ?? collect();
    $montantTotalPaye = $paiements->sum('montant_paye');
    $montantTotalAttendu = ($semaine) * ($credit->remboursement_hebdo ?? 0);
    
    return ($montantTotalPaye >= $montantTotalAttendu) ? 'Payé' : 'En retard';
}
    
    // NOUVELLE MÉTHODE POUR LES GROUPES
private function determinerStatutGroupe($dateEcheance, $creditGroupe, $semaine)
{
    $aujourdhui = now();
    
    // Si la date d'échéance est dans le futur
    if ($aujourdhui->lt($dateEcheance)) {
        return 'À venir';
    }
    
    // Vérifier le paiement
    $totalPaye = $creditGroupe->getTotalDejaPayeAttribute();
    $montantHebdo = $creditGroupe->remboursement_hebdo_total ?? 0;
    
    if ($montantHebdo <= 0) {
        return 'À venir';
    }
    
    $nombreEcheancesCompletes = floor($totalPaye / $montantHebdo);
    
    // Si cette échéance est déjà payée
    if ($semaine <= $nombreEcheancesCompletes) {
        return 'Payé';
    }
    
    // Vérifier s'il y a un paiement partiel pour cette échéance
    $reste = $totalPaye - ($nombreEcheancesCompletes * $montantHebdo);
    
    // Si c'est exactement cette échéance
    if ($semaine == $nombreEcheancesCompletes + 1) {
        // Calculer le montant attendu
        $montantAttendu = ($semaine == 16) 
            ? min($creditGroupe->montant_total - ($nombreEcheancesCompletes * $montantHebdo), $montantHebdo)
            : $montantHebdo;
        
        // Vérifier si payé en totalité
        if ($reste >= $montantAttendu || abs($reste - $montantAttendu) <= 0.01) {
            return 'Payé';
        }
        
        // Vérifier si partiellement payé
        if ($reste > 0) {
            // Si c'est le jour même de l'échéance
            if ($aujourdhui->isSameDay($dateEcheance)) {
                return 'En cours';
            }
            // Si c'est après le jour de l'échéance
            return 'En retard';
        }
        
        // Si non payé
        if ($aujourdhui->isSameDay($dateEcheance)) {
            return 'En cours';
        }
        
        return 'En retard';
    }
    
    // Si cette échéance est déjà dépassée sans paiement
    if ($semaine < $nombreEcheancesCompletes + 1) {
        return 'En retard';
    }
    
    // Par défaut
    if ($aujourdhui->isSameDay($dateEcheance)) {
        return 'En cours';
    }
    
    return 'En retard';
}
    public function calculerTotaux($remboursements)
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
        
        return [
            'total_remboursement' => round($remboursements->sum('montant_total'), 2),
            'total_capital' => round($remboursements->sum('capital'), 2),
            'total_interets' => round($remboursements->sum('interets'), 2),
            'nombre_periodes' => $remboursements->count(),
            'nombre_credits' => $remboursements->unique('numero_compte')->count(),
            'moyenne_capital' => round($remboursements->avg('pourcentage_capital'), 2),
            'moyenne_interets' => round($remboursements->avg('pourcentage_interets'), 2),
        ];
    }
}