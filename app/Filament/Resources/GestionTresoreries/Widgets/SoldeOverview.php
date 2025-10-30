<?php

namespace App\Filament\Resources\GestionTresorerieResource\Widgets;

use App\Models\CashRegister;
use App\Models\Caisse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SoldeOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCoffres = CashRegister::where('statut', 'actif')->sum('solde_actuel');
        $totalCaisses = Caisse::where('statut', 'actif')->sum('solde_actuel');
        $totalGeneral = $totalCoffres + $totalCaisses;

        return [
            Stat::make('Total Coffres', number_format($totalCoffres, 2) . ' USD')
                ->description('Solde total des coffres')
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color('success'),

            Stat::make('Total Caisses', number_format($totalCaisses, 2) . ' USD')
                ->description('Solde total des caisses')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Trésorerie Générale', number_format($totalGeneral, 2) . ' USD')
                ->description('Solde global de trésorerie')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }
}