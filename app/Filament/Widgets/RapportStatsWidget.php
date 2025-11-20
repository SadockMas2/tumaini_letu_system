<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\PaiementCredit;

class RapportStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $creditsIndividuels = Credit::where('statut_demande', 'approuve')->get();
        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')->get();
        
        $totalCredits = $creditsIndividuels->count() + $creditsGroupe->count();
        $totalMontantAccorde = $creditsIndividuels->sum('montant_accorde') + $creditsGroupe->sum('montant_accorde');
        $totalMontantTotal = $creditsIndividuels->sum('montant_total') + $creditsGroupe->sum('montant_total');
        $totalInteretsAttendus = $totalMontantTotal - $totalMontantAccorde;
        $totalPaiements = PaiementCredit::sum('montant_paye');
        
        $tauxRemboursement = $totalMontantTotal > 0 ? round(($totalPaiements / $totalMontantTotal) * 100, 2) : 0;

        return [
            Stat::make('Crédits Individuels', $creditsIndividuels->count())
                ->description(\App\Helpers\CurrencyHelper::format($creditsIndividuels->sum('montant_accorde')) . ' accordés')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),

            Stat::make('Crédits Groupe', $creditsGroupe->count())
                ->description(\App\Helpers\CurrencyHelper::format($creditsGroupe->sum('montant_accorde')) . ' accordés')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Total Crédits', $totalCredits)
                ->description('Actifs')
                ->descriptionIcon('heroicon-m-document-currency-dollar')
                ->color('warning'),

            Stat::make('Portefeuille Capital', \App\Helpers\CurrencyHelper::format($totalMontantAccorde))
                ->description('Sans intérêts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Portefeuille Total', \App\Helpers\CurrencyHelper::format($totalMontantTotal))
                ->description('Capital + intérêts')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Intérêts Attendus', \App\Helpers\CurrencyHelper::format($totalInteretsAttendus))
                ->description('Revenus potentiels')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Taux Remboursement', $tauxRemboursement . '%')
                ->description('Taux de recouvrement')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($tauxRemboursement > 90 ? 'success' : ($tauxRemboursement > 70 ? 'warning' : 'danger')),

            Stat::make('Montant Collecté', \App\Helpers\CurrencyHelper::format($totalPaiements))
                ->description('Total des paiements')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}