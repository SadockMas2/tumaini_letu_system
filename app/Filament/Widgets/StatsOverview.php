<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Client;
use App\Models\TypeCompte;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected  ?string $pollingInterval = '10s';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Utilisateurs', User::count())
                ->description('Total des agents')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Optionnel : données pour graphique
                ->extraAttributes(['class' => 'bg-success-50']),

            Stat::make('Membres', Client::count())
                ->description('Total des membres')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->extraAttributes(['class' => 'bg-primary-50']),

            Stat::make('Types de compte', TypeCompte::count())
                ->description('Types de comptes')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning')
                ->extraAttributes(['class' => 'bg-warning-50']),
        ];
    }
}