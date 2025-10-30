<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Client;
use App\Models\TypeCompte;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Utilisateurs', User::count())
                ->description('Total des agents')
                ->descriptionIcon('heroicon-s-users')
                ->color('success'),

            Stat::make('Membres', Client::count())
                ->description('Total des membres enregistrés')
                ->descriptionIcon('heroicon-s-user-group')
                ->color('primary'),

                
            Stat::make('Types de compte', TypeCompte::count())
                ->description('Total des comptes enregistrés')
                ->descriptionIcon('heroicon-s-user-group')
                ->color('primary'),
        ];
    }
}
