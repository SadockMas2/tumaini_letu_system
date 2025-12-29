<?php

namespace App\Helpers;

use App\Models\Credit;
use App\Models\CreditGroupe;

class CreditRepartitionHelper
{
    /**
     * Pourcentages de remboursement pour 16 semaines
     */
    private static array $pourcentageInterets = [
        14.4154589019438, 12.5668588386971, 11.5077233695784, 10.4164781434722,
        9.292636648909, 9.13522586294972, 8.94327276265538, 6.71531781361745,
        4.45038799289693, 3.14751027755479, 2.80571164465202, 1.80571164465202,
        1.80571164465202, 1.40571164465202, 1.30571164465202, 0.280571164465202
    ];

    /**
     * Calcule la répartition capital/intérêts selon les pourcentages
     */
    public static function calculerRepartition($credit, $montantPaiement, $numeroEcheance = null): array
    {
        // Si pas d'échéance spécifiée, calculer l'échéance courante
        if ($numeroEcheance === null) {
            $numeroEcheance = self::determinerEcheanceCourante($credit);
        }
        
        // Limiter à 16 échéances
        $numeroEcheance = min($numeroEcheance, 16);
        
        // Calculer le pourcentage pour cette échéance
        $pourcentage = self::$pourcentageInterets[$numeroEcheance - 1] ?? 0;
        
        // Pour un crédit individuel
        if ($credit instanceof Credit && $credit->type_credit === 'individuel') {
            $montantAccorde = $credit->montant_accorde;
            $montantTotal = $credit->montant_total;
            $totalInterets = $montantTotal - $montantAccorde;
            
            // Intérêts basés sur le pourcentage
            $interetsTheoriques = ($totalInterets * $pourcentage) / 100;
            $capitalTheorique = $credit->remboursement_hebdo - $interetsTheoriques;
            
            // Répartir le paiement
            $interetsAPayer = min($montantPaiement, $interetsTheoriques);
            $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
            
            // Ajuster pour la dernière échéance
            if ($numeroEcheance == 16) {
                $resteCapital = $montantAccorde - self::calculerCapitalDejaRembourseSansPaiement($credit);
                if ($capitalAPayer > $resteCapital) {
                    $capitalAPayer = $resteCapital;
                    $interetsAPayer = $montantPaiement - $capitalAPayer;
                }
            }
            
            return [
                'capital' => round($capitalAPayer, 2),
                'interets' => round($interetsAPayer, 2),
                'numero_echeance' => $numeroEcheance,
                'pourcentage_utilise' => $pourcentage
            ];
        }
        
        // Pour un crédit groupe
        if ($credit instanceof CreditGroupe) {
            $montantAccorde = $credit->montant_accorde;
            $montantTotal = $credit->montant_total;
            $totalInterets = $montantTotal - $montantAccorde;
            
            $interetsTheoriques = ($totalInterets * $pourcentage) / 100;
            $capitalTheorique = ($montantAccorde / 16); // Capital fixe par échéance
            
            // Pour groupe, priorité aux intérêts
            $interetsAPayer = min($montantPaiement, $interetsTheoriques);
            $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
            
            return [
                'capital' => round($capitalAPayer, 2),
                'interets' => round($interetsAPayer, 2),
                'numero_echeance' => $numeroEcheance,
                'pourcentage_utilise' => $pourcentage
            ];
        }
        
        // Fallback pour ancienne méthode
        return [
            'capital' => round($montantPaiement * 0.7, 2),
            'interets' => round($montantPaiement * 0.3, 2),
            'numero_echeance' => $numeroEcheance,
            'pourcentage_utilise' => 0
        ];
    }

    /**
     * Détermine l'échéance courante basée sur la date d'octroi
     */
    private static function determinerEcheanceCourante($credit): int
    {
        if (!$credit->date_octroi) {
            return 1;
        }

        $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
        
        if (now()->lt($dateDebut)) {
            return 1;
        }
        
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        return min($semainesEcoulees + 1, 16);
    }

    /**
     * Calcule le capital déjà remboursé selon les pourcentages
     */
    public static function calculerCapitalDejaRembourse($credit): float
    {
        $paiements = self::getPaiementsCredit($credit);
        
        if ($paiements->isEmpty()) {
            return 0;
        }
        
        // Si capital_rembourse est stocké, l'utiliser
        if ($paiements->first()->capital_rembourse !== null) {
            return $paiements->sum('capital_rembourse');
        }
        
        // Sinon recalculer selon les pourcentages
        return self::recalculerCapitalSelonPourcentages($credit, $paiements->sum('montant_paye'));
    }

    /**
     * Calcule les intérêts déjà payés selon les pourcentages
     */
    public static function calculerInteretsDejaPayes($credit): float
    {
        $paiements = self::getPaiementsCredit($credit);
        
        if ($paiements->isEmpty()) {
            return 0;
        }
        
        // Si interets_payes est stocké, l'utiliser
        if ($paiements->first()->interets_payes !== null) {
            return $paiements->sum('interets_payes');
        }
        
        // Sinon recalculer selon les pourcentages
        return self::recalculerInteretsSelonPourcentages($credit, $paiements->sum('montant_paye'));
    }

    /**
     * Récupère les paiements selon le type de crédit
     */
    private static function getPaiementsCredit($credit)
    {
        if ($credit instanceof Credit && $credit->type_credit === 'individuel') {
            return \App\Models\PaiementCredit::where('credit_id', $credit->id)
                ->where('type_paiement', '!=', \App\Enums\TypePaiement::GROUPE->value)
                ->get();
        } else {
            $groupeId = $credit->id - 100000; // Pour les modèles factices
            return \App\Models\PaiementCredit::where('credit_groupe_id', $groupeId)
                ->where('type_paiement', \App\Enums\TypePaiement::GROUPE->value)
                ->get();
        }
    }

    /**
     * Recalcule le capital selon les pourcentages
     */
    private static function recalculerCapitalSelonPourcentages($credit, $montantTotalPaye): float
    {
        $capitalTotal = 0;
        $resteAPayer = $montantTotalPaye;
        $echeance = 1;
        
        while ($resteAPayer > 0 && $echeance <= 16) {
            $montantEcheance = $credit->remboursement_hebdo ?? ($credit->montant_total / 16);
            $montantPourEcheance = min($resteAPayer, $montantEcheance);
            
            // Calculer la répartition pour cette échéance
            $repartition = self::calculerRepartition($credit, $montantPourEcheance, $echeance);
            
            $capitalTotal += $repartition['capital'];
            $resteAPayer -= $montantPourEcheance;
            $echeance++;
        }
        
        return round($capitalTotal, 2);
    }

    /**
     * Recalcule les intérêts selon les pourcentages
     */
    private static function recalculerInteretsSelonPourcentages($credit, $montantTotalPaye): float
    {
        $interetsTotal = 0;
        $resteAPayer = $montantTotalPaye;
        $echeance = 1;
        
        while ($resteAPayer > 0 && $echeance <= 16) {
            $montantEcheance = $credit->remboursement_hebdo ?? ($credit->montant_total / 16);
            $montantPourEcheance = min($resteAPayer, $montantEcheance);
            
            // Calculer la répartition pour cette échéance
            $repartition = self::calculerRepartition($credit, $montantPourEcheance, $echeance);
            
            $interetsTotal += $repartition['interets'];
            $resteAPayer -= $montantPourEcheance;
            $echeance++;
        }
        
        return round($interetsTotal, 2);
    }

    /**
     * Calcule le capital déjà remboursé sans tenir compte du paiement actuel
     */
    private static function calculerCapitalDejaRembourseSansPaiement(Credit $credit): float
    {
        $paiements = \App\Models\PaiementCredit::where('credit_id', $credit->id)
            ->where('type_paiement', '!=', \App\Enums\TypePaiement::GROUPE->value)
            ->get();
        
        $capitalTotal = 0;
        $montantTotalPaye = $paiements->sum('montant_paye');
        $resteAPayer = $montantTotalPaye;
        $echeance = 1;
        
        while ($resteAPayer > 0 && $echeance <= 16) {
            $montantEcheance = $credit->remboursement_hebdo;
            $montantPourEcheance = min($resteAPayer, $montantEcheance);
            
            // Calculer la répartition pour cette échéance
            $repartition = self::calculerRepartition($credit, $montantPourEcheance, $echeance);
            
            $capitalTotal += $repartition['capital'];
            $resteAPayer -= $montantPourEcheance;
            $echeance++;
        }
        
        return round($capitalTotal, 2);
    }
}