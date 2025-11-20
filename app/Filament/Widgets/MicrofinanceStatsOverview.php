<?php

namespace App\Filament\Widgets;

use App\Helpers\CurrencyHelper;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Compte;
use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\CompteEpargne;

class MicrofinanceStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        // N'afficher ce widget que sur la page microfinance
        return request()->routeIs('filament.admin.resources.microfinance-overviews.*');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Portefeuille Crédits', $this->getTotalPortefeuilleCredits())
                ->description('Crédits en cours')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Épargnes', $this->getTotalEpargnes())
                ->description('Épargnes collectées')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Comptes Actifs', $this->getComptesActifs())
                ->description('Clients actifs')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }

    private function getTotalPortefeuilleCredits(): string
    {
        $creditsIndividuelsUSD = Credit::where('statut_demande', 'approuve')->sum('montant_total');
        $creditsGroupesUSD = CreditGroupe::where('statut_demande', 'approuve')->sum('montant_total');

        
        // Convertir CDF en USD pour avoir un total cohérent
        $totalUSD = $creditsIndividuelsUSD + $creditsGroupesUSD;

        
        $total = $totalUSD;
        return CurrencyHelper::format($total, 'USD');
    }

    private function getTotalEpargnes(): string
    {
        $epargnesUSD = CompteEpargne::where('devise', 'USD')->sum('solde');
        $epargnesCDF = CompteEpargne::where('devise', 'CDF')->sum('solde');
        
        // Convertir CDF en USD
        $totalCDFConverted = CurrencyHelper::convert($epargnesCDF, 'CDF', 'USD');
        $total = $epargnesUSD + $totalCDFConverted;
        
        return CurrencyHelper::format($total, 'USD');
    }

    private function getComptesActifs(): int
    {
        return Compte::where('statut', 'actif')->count();
    }
}