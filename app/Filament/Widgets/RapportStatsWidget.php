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
            // Le nom de la route est généralement quelque chose comme :
            // 'filament.pages.rapports-microfinance' ou 'filament.resources.microfinance-overviews.rapports'
            return str_contains($route->getName(), 'rapports-microfinance') || 
                   str_contains($route->getName(), 'microfinance-overviews.rapports');
        }
        
        return false;
    }
 protected function getStats(): array
{
    // 1. CRÉDITS INDIVIDUELS
    $totalCreditsIndividuels = Credit::where('statut_demande', 'approuve')->count();
    $totalCapitalAccordeIndividuel = Credit::where('statut_demande', 'approuve')->sum('montant_accorde');
    $totalMontantTotalIndividuel = Credit::where('statut_demande', 'approuve')->sum('montant_total');
    
    // 2. CRÉDITS GROUPE
    $totalCreditsGroupe = CreditGroupe::where('statut_demande', 'approuve')->count();
    $totalCapitalAccordeGroupe = CreditGroupe::where('statut_demande', 'approuve')->sum('montant_accorde');
    $totalMontantTotalGroupe = CreditGroupe::where('statut_demande', 'approuve')->sum('montant_total');
    
    // 3. TOTAUX COMBINÉS
    $totalCredits = $totalCreditsIndividuels + $totalCreditsGroupe;
    $totalCapitalAccorde = $totalCapitalAccordeIndividuel + $totalCapitalAccordeGroupe;
    $totalMontantTotal = $totalMontantTotalIndividuel + $totalMontantTotalGroupe;
    
    // 4. PAIEMENTS (NE TOUCHENT PAS AU CAPITAL ACCORDÉ)
    $totalPaiements = PaiementCredit::sum('montant_paye');
    $totalCapitalRembourse = PaiementCredit::sum('capital_rembourse');
    $totalInteretsPayes = PaiementCredit::sum('interets_payes');
    
    // 5. CALCUL DES RESTES (pour suivi seulement)
    $montantRestantTotal = $totalMontantTotal - $totalPaiements;
    $interetsRestants = $totalMontantTotal - $totalCapitalAccorde - $totalInteretsPayes;
    
    // 6. TAUX DE REMBOURSEMENT
    $tauxRemboursement = $totalMontantTotal > 0 
        ? round(($totalPaiements / $totalMontantTotal) * 100, 2) 
        : 0;

    return [
        // SECTION CRÉDITS
        Stat::make('Crédits Individuels', $totalCreditsIndividuels)
            ->description('Capital: ' . CurrencyHelper::format($totalCapitalAccordeIndividuel))
            ->descriptionIcon('heroicon-m-user')
            ->color('primary')
            ->chart($this->getChartDataIndividuel()),
        
        Stat::make('Crédits Groupe', $totalCreditsGroupe)
            ->description('Capital: ' . CurrencyHelper::format($totalCapitalAccordeGroupe))
            ->descriptionIcon('heroicon-m-users')
            ->color('info')
            ->chart($this->getChartDataGroupe()),
        
        // SECTION CAPITAL (NE CHANGE JAMAIS)
        Stat::make('Capital Total Accordé', CurrencyHelper::format($totalCapitalAccorde))
            ->description('Fixe - Ne diminue jamais')
            ->color('success')
            ->icon('heroicon-o-banknotes'),
        
        Stat::make('Capital Déjà Remboursé', CurrencyHelper::format($totalCapitalRembourse))
            ->description(round(($totalCapitalRembourse / $totalCapitalAccorde * 100), 2) . '% du capital total')
            ->color('info')
            ->icon('heroicon-o-currency-dollar'),
        
        // SECTION REMBOURSEMENTS
        Stat::make('Total Remboursé', CurrencyHelper::format($totalPaiements))
            ->description(CurrencyHelper::format($totalCapitalRembourse) . ' capital + ' . 
                         CurrencyHelper::format($totalInteretsPayes) . ' intérêts')
            ->color('success')
            ->icon('heroicon-o-check-circle'),
        
        Stat::make('Portefeuille Total', CurrencyHelper::format($totalMontantTotal))
            ->description('Capital + Intérêts')
            ->color('warning')
            ->icon('heroicon-o-credit-card'),
        
        // SECTION RESTANTS
        Stat::make('Intérêts Restants', CurrencyHelper::format($interetsRestants))
            ->description('Intérêts à recouvrer')
            ->color($interetsRestants > 0 ? 'danger' : 'success')
            ->icon('heroicon-o-clock'),
        
        Stat::make('Taux Remboursement', $tauxRemboursement . '%')
            ->description('Progression globale')
            ->color($tauxRemboursement >= 80 ? 'success' : ($tauxRemboursement >= 50 ? 'warning' : 'danger'))
            ->icon('heroicon-o-chart-bar'),
    ];
}

/**
 * Script de correction des données erronées
 */
public function corrigerDonneesErronees()
{
    try {
        DB::beginTransaction();
        
        // 1. Pour tous les crédits, s'assurer que montant_total >= montant_accorde
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
            
            // 2. Recalculer le remboursement hebdo selon vos formules
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
    /**
     * Données de chart pour les crédits individuels (exemple)
     */
    protected function getChartDataIndividuel(): array
    {
        // Récupérer les derniers 6 mois de crédits individuels
        $data = DB::table('credits')
            ->select(
                DB::raw('MONTH(date_octroi) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(montant_accorde) as total')
            )
            ->where('statut_demande', 'approuve')
            ->where('type_credit', 'individuel')
            ->where('date_octroi', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $data->pluck('total')->toArray();
    }

    private function verifierCapitalFixe(Credit $credit)
{
    // Vérifier dans l'historique des paiements
    $totalCapitalRembourse = PaiementCredit::where('credit_id', $credit->id)
        ->sum('capital_rembourse');
    
    $montantAccorde = $credit->montant_accorde;
    
    if ($totalCapitalRembourse > $montantAccorde) {
        Log::error('ERREUR : Capital remboursé dépasse montant accordé!', [
            'credit_id' => $credit->id,
            'montant_accorde' => $montantAccorde,
            'total_capital_rembourse' => $totalCapitalRembourse,
            'depassement' => $totalCapitalRembourse - $montantAccorde
        ]);
        
        // Corriger en ajustant le dernier paiement
        $this->corrigerDepassementCapital($credit, $totalCapitalRembourse - $montantAccorde);
    }
    
    // Vérifier que montant_total >= montant_accorde
    if ($credit->montant_total < $credit->montant_accorde) {
        Log::warning('Correction montant_total inférieur à montant_accorde', [
            'credit_id' => $credit->id,
            'montant_accorde' => $credit->montant_accorde,
            'montant_total' => $credit->montant_total,
            'difference' => $credit->montant_accorde - $credit->montant_total
        ]);
        
        $credit->montant_total = $credit->montant_accorde;
        $credit->save();
    }
}

    /**
     * Données de chart pour les crédits groupe (exemple)
     */
    protected function getChartDataGroupe(): array
    {
        // Récupérer les derniers 6 mois de crédits groupe
        $data = DB::table('credit_groupes')
            ->select(
                DB::raw('MONTH(date_octroi) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(montant_accorde) as total')
            )
            ->where('statut_demande', 'approuve')
            ->where('date_octroi', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $data->pluck('total')->toArray();
    }
}