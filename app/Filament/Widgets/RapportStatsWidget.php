<?php

namespace App\Filament\Widgets;

use App\Helpers\CurrencyHelper;
use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\PaiementCredit;
use Filament\Notifications\Notification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RapportStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        // Récupérer la route actuelle
        $route = request()->route();
        
        // Vérifier si nous sommes sur la page RapportsMicrofinance
        if ($route && $route->getName()) {
            return str_contains($route->getName(), 'rapports-microfinance') || 
                   str_contains($route->getName(), 'microfinance-overviews.rapports');
        }
        
        return false;
    }
    
    protected function getStats(): array
    {
        // Récupérer les crédits approuvés
        $creditsIndividuels = Credit::where('statut_demande', 'approuve')->get();
        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')->get();
        
        // Calculer les totaux à partir des méthodes de calcul de la page
        $totalCapitalRembourse = 0;
        $totalInteretsPayes = 0;
        
        // 1. Calcul pour les crédits individuels
         foreach ($creditsIndividuels as $credit) {
        $capitalRembourse = \App\Helpers\CreditRepartitionHelper::calculerCapitalDejaRembourse($credit);
        $interetsPayes = \App\Helpers\CreditRepartitionHelper::calculerInteretsDejaPayes($credit);
        
        $totalCapitalRembourse += $capitalRembourse;
        $totalInteretsPayes += $interetsPayes;
    }

    
        // 2. Calcul pour les crédits groupe (utiliser la structure factice)
        foreach ($creditsGroupe as $creditGroupe) {
            // Créer un modèle Credit factice pour les groupes
            $credit = new Credit();
            $credit->id = $creditGroupe->id + 100000;
            $credit->type_credit = 'groupe';
            $credit->montant_accorde = $creditGroupe->montant_accorde;
            $credit->montant_total = $creditGroupe->montant_total;
            $credit->remboursement_hebdo = $creditGroupe->remboursement_hebdo_total ?? ($creditGroupe->montant_total / 16);
            
            $capitalRembourse = $this->calculerCapitalDejaRembourse($credit);
            $interetsPayes = $this->calculerInteretsDejaPayes($credit);
            
            $totalCapitalRembourse += $capitalRembourse;
            $totalInteretsPayes += $interetsPayes;
        }
        
        // 3. Calculer les autres totaux
        $totalCapitalAccordeIndividuel = $creditsIndividuels->sum('montant_accorde');
        $totalMontantTotalIndividuel = $creditsIndividuels->sum('montant_total');
        $totalCapitalAccordeGroupe = $creditsGroupe->sum('montant_accorde');
        $totalMontantTotalGroupe = $creditsGroupe->sum('montant_total');
        
        // Nouveau : Intérêts totaux attendus
        $totalInteretsAttendus = ($totalMontantTotalIndividuel - $totalCapitalAccordeIndividuel) + 
                                 ($totalMontantTotalGroupe - $totalCapitalAccordeGroupe);
        
        $totalCreditsIndividuels = $creditsIndividuels->count();
        $totalCreditsGroupe = $creditsGroupe->count();
        $totalCredits = $totalCreditsIndividuels + $totalCreditsGroupe;
        $totalCapitalAccorde = $totalCapitalAccordeIndividuel + $totalCapitalAccordeGroupe;
        $totalMontantTotal = $totalMontantTotalIndividuel + $totalMontantTotalGroupe;
        
        // 4. Montant total remboursé (capital + intérêts)
        $totalPaiements = $totalCapitalRembourse + $totalInteretsPayes;
        
        // 5. NOUVEAU : Portefeuille Actuel (Capital Total Accordé - Capital Déjà Remboursé)
        $portefeuilleActuel = max(0, $totalCapitalAccorde - $totalCapitalRembourse);
        
        // 6. NOUVEAU : Montant total du remboursement (Capital Total Accordé + Intérêts Totaux)
        $montantTotalRemboursement = $totalCapitalAccorde + $totalInteretsAttendus;
        
        // 7. Calcul des restes et taux
        $montantRestantTotal = max(0, $totalMontantTotal - $totalPaiements);
        $interetsRestants = max(0, $totalInteretsAttendus - $totalInteretsPayes);
        
        $tauxRemboursement = $totalMontantTotal > 0 
            ? round(($totalPaiements / $totalMontantTotal) * 100, 2) 
            : 0;
        
        $tauxCapitalRembourse = $totalCapitalAccorde > 0
            ? round(($totalCapitalRembourse / $totalCapitalAccorde) * 100, 2)
            : 0;
        
        $tauxPortefeuilleActuel = $totalCapitalAccorde > 0
            ? round(($portefeuilleActuel / $totalCapitalAccorde) * 100, 2)
            : 0;

        return [
            // SECTION CRÉDITS
            Stat::make('Crédits Individuels', $totalCreditsIndividuels)
                ->description('Capital: ' . CurrencyHelper::format($totalCapitalAccordeIndividuel))
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),
            
            Stat::make('Crédits Groupe', $totalCreditsGroupe)
                ->description('Capital: ' . CurrencyHelper::format($totalCapitalAccordeGroupe))
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            
            // SECTION CAPITAL
            Stat::make('Capital Total Accordé', CurrencyHelper::format($totalCapitalAccorde))
                ->description('Montant initial prêté')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            
            // NOUVEAU : Portefeuille Actuel
            Stat::make('Portefeuille Actuel', CurrencyHelper::format($portefeuilleActuel))
                ->description($tauxPortefeuilleActuel . '% du capital restant à recouvrer')
                ->color($portefeuilleActuel > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-wallet'),
            
            Stat::make('Capital Déjà Remboursé', CurrencyHelper::format($totalCapitalRembourse))
                ->description($tauxCapitalRembourse . '% du capital total')
                ->color($tauxCapitalRembourse >= 100 ? 'success' : ($tauxCapitalRembourse >= 50 ? 'warning' : 'info'))
                ->icon('heroicon-o-currency-dollar'),
            
            // NOUVEAU : Montant Total du Remboursement
            Stat::make('Montant Total du Remboursement', CurrencyHelper::format($montantTotalRemboursement))
                ->description(CurrencyHelper::format($totalCapitalAccorde) . ' capital + ' . 
                             CurrencyHelper::format($totalInteretsAttendus) . ' intérêts')
                ->color('warning')
                ->icon('heroicon-o-scale'),
            
            // SECTION INTÉRÊTS
            Stat::make('Intérêts Totaux Attendus', CurrencyHelper::format($totalInteretsAttendus))
                ->description('Revenus potentiels')
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
            
            Stat::make('Intérêts Déjà Payés', CurrencyHelper::format($totalInteretsPayes))
                ->description(round(($totalInteretsPayes / $totalInteretsAttendus * 100), 2) . '% des intérêts' . 
                             ($totalInteretsAttendus > 0 ? '' : ' (N/A)'))
                ->color('warning')
                ->icon('heroicon-o-credit-card'),
            
            // SECTION REMBOURSEMENTS
            Stat::make('Total Remboursé', CurrencyHelper::format($totalPaiements))
                ->description(CurrencyHelper::format($totalCapitalRembourse) . ' capital + ' . 
                             CurrencyHelper::format($totalInteretsPayes) . ' intérêts')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            
            // SECTION RESTANTS
            Stat::make('Portefeuille Restant', CurrencyHelper::format($montantRestantTotal))
                ->description(CurrencyHelper::format($portefeuilleActuel) . ' capital + ' . 
                             CurrencyHelper::format($interetsRestants) . ' intérêts')
                ->color($montantRestantTotal > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),
            
            Stat::make('Intérêts Restants', CurrencyHelper::format($interetsRestants))
                ->description('Intérêts à recouvrer')
                ->color($interetsRestants > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),
            
            // SECTION TAUX
            Stat::make('Taux Remboursement Global', $tauxRemboursement . '%')
                ->description('Progression globale (capital + intérêts)')
                ->color($tauxRemboursement >= 80 ? 'success' : ($tauxRemboursement >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-bar'),
        ];
    }

    /**
     * Calcule le capital déjà remboursé pour un crédit
     * Même méthode que dans RapportsMicrofinance
     */
    private function calculerCapitalDejaRembourse($credit): float
    {
        if ($credit->type_credit === 'individuel') {
            $paiements = PaiementCredit::where('credit_id', $credit->id)
                ->where('type_paiement', '!=', \App\Enums\TypePaiement::GROUPE->value)
                ->get();
            
            if ($paiements->isEmpty()) {
                return 0;
            }
            
            // Si capital_rembourse est stocké, l'utiliser
            if ($paiements->first()->capital_rembourse !== null) {
                return $paiements->sum('capital_rembourse');
            }
            
            // Sinon calculer
            return $this->calculerCapitalInteretsPrecis($credit, $paiements->sum('montant_paye'))['capital'];
            
        } else {
            // Pour les groupes
            $groupeId = $credit->id - 100000;
            $paiements = PaiementCredit::where('credit_groupe_id', $groupeId)
                ->where('type_paiement', \App\Enums\TypePaiement::GROUPE->value)
                ->get();
            
            if ($paiements->isEmpty()) {
                return 0;
            }
            
            // Si capital_rembourse est stocké, l'utiliser
            if ($paiements->first()->capital_rembourse !== null) {
                return $paiements->sum('capital_rembourse');
            }
            
            // Sinon calculer
            return $this->calculerCapitalInteretsPrecis($credit, $paiements->sum('montant_paye'))['capital'];
        }
    }

    /**
     * Calcule les intérêts déjà payés pour un crédit
     * Même méthode que dans RapportsMicrofinance
     */
    private function calculerInteretsDejaPayes($credit): float
    {
        if ($credit->type_credit === 'individuel') {
            $paiements = PaiementCredit::where('credit_id', $credit->id)
                ->where('type_paiement', '!=', \App\Enums\TypePaiement::GROUPE->value)
                ->get();
            
            if ($paiements->isEmpty()) {
                return 0;
            }
            
            // Si interets_payes est stocké, l'utiliser
            if ($paiements->first()->interets_payes !== null) {
                return $paiements->sum('interets_payes');
            }
            
            // Sinon calculer
            return $this->calculerCapitalInteretsPrecis($credit, $paiements->sum('montant_paye'))['interets'];
            
        } else {
            // Pour les groupes
            $groupeId = $credit->id - 100000;
            $paiements = PaiementCredit::where('credit_groupe_id', $groupeId)
                ->where('type_paiement', \App\Enums\TypePaiement::GROUPE->value)
                ->get();
            
            if ($paiements->isEmpty()) {
                return 0;
            }
            
            // Si interets_payes est stocké, l'utiliser
            if ($paiements->first()->interets_payes !== null) {
                return $paiements->sum('interets_payes');
            }
            
            // Sinon calculer
            return $this->calculerCapitalInteretsPrecis($credit, $paiements->sum('montant_paye'))['interets'];
        }
    }

    /**
     * Calcul précis du capital et intérêts déjà payés
     * Même méthode que dans RapportsMicrofinance
     */
    private function calculerCapitalInteretsPrecis($credit, $montantPaye): array
    {
        if ($montantPaye <= 0) {
            return ['capital' => 0, 'interets' => 0];
        }
        
        $montantAccorde = $credit->montant_accorde;
        $montantTotal = $credit->montant_total;
        
        // Calculer les valeurs par échéance
        $capitalParEcheance = $montantAccorde / 16;
        $remboursementHebdo = $montantTotal / 16;
        $interetParEcheance = $remboursementHebdo - $capitalParEcheance;
        
        // Nombre d'échéances complètes
        $nombreEcheancesCompletes = floor($montantPaye / $remboursementHebdo);
        
        // Capital des échéances complètes
        $capitalComplet = $nombreEcheancesCompletes * $capitalParEcheance;
        $interetsComplets = $nombreEcheancesCompletes * $interetParEcheance;
        
        // Reste à répartir
        $reste = $montantPaye - ($nombreEcheancesCompletes * $remboursementHebdo);
        
        // Pour le reste : priorité aux intérêts
        $interetsReste = min($reste, $interetParEcheance);
        $capitalReste = max(0, $reste - $interetsReste);
        
        // Totaux
        $capitalTotal = $capitalComplet + $capitalReste;
        $interetsTotal = $interetsComplets + $interetsReste;
        
        // Vérification et ajustement si nécessaire
        $totalCalcule = $capitalTotal + $interetsTotal;
        if (abs($totalCalcule - $montantPaye) > 0.01) {
            $interetsTotal = $montantPaye - $capitalTotal;
        }
        
        return [
            'capital' => round($capitalTotal, 2),
            'interets' => round($interetsTotal, 2)
        ];
    }

    /**
     * Script de correction des données erronées
     */
    public function corrigerDonneesErronees()
    {
        try {
            DB::beginTransaction();
            
            $credits = Credit::where('statut_demande', 'approuve')->get();
            
            foreach ($credits as $credit) {
                if ($credit->montant_total < $credit->montant_accorde) {
                    Log::warning('Correction automatique montant_total', [
                        'credit_id' => $credit->id,
                        'ancien_montant_total' => $credit->montant_total,
                        'montant_accorde' => $credit->montant_accorde,
                        'nouveau_montant_total' => $credit->montant_accorde
                    ]);
                    
                    $credit->montant_total = $credit->montant_accorde;
                    $credit->save();
                }
                
                // Recalculer selon vos formules
                $montantAccorde = $credit->montant_accorde;
                
                if ($montantAccorde >= 100 && $montantAccorde <= 500) {
                    $montantTotal = $montantAccorde * 0.308666 * 4;
                } elseif ($montantAccorde >= 501 && $montantAccorde <= 1000) {
                    $montantTotal = $montantAccorde * 0.3019166667 * 4;
                } elseif ($montantAccorde >= 1001 && $montantAccorde <= 1599) {
                    $montantTotal = $montantAccorde * 0.30866 * 4;
                } elseif ($montantAccorde >= 2000 && $montantAccorde <= 5000) {
                    $montantTotal = $montantAccorde * 0.2985666667 * 4;
                } else {
                    $montantTotal = $montantAccorde * 0.30 * 4;
                }
                
                $remboursementHebdo = $montantTotal / 16;
                
                if (abs($credit->remboursement_hebdo - $remboursementHebdo) > 0.01) {
                    Log::warning('Correction automatique remboursement_hebdo', [
                        'credit_id' => $credit->id,
                        'ancien_remboursement_hebdo' => $credit->remboursement_hebdo,
                        'nouveau_remboursement_hebdo' => $remboursementHebdo
                    ]);
                    
                    $credit->remboursement_hebdo = $remboursementHebdo;
                    $credit->montant_total = $montantTotal;
                    $credit->save();
                }
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Données corrigées')
                ->body('Les données des crédits ont été vérifiées et corrigées selon vos formules.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur correction données: ' . $e->getMessage());
        }
    }
}