<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DateTimeWidget extends BaseWidget
{
    protected  ?string $pollingInterval = '1s'; // Rafraîchissement plus rapide pour l'heure

    protected function getStats(): array
    {
        $currentTime = now();
        
        return [
            Stat::make("Date", $currentTime->format('d/m/Y'))
                ->description($currentTime->translatedFormat('l'))
                ->color('primary')
                ->icon('heroicon-m-calendar')
                ->extraAttributes(['class' => 'bg-primary-50']),
                
            Stat::make('Heure', $currentTime->format('H:i:s'))
                ->description(config('app.timezone'))
                ->color('success')
                ->icon('heroicon-m-clock')
                ->extraAttributes(['class' => 'bg-success-50']),
                
            Stat::make('Semaine', 'S' . $currentTime->week())
                ->description($currentTime->translatedFormat('F Y'))
                ->color('warning')
                ->icon('heroicon-m-calendar-days')
                ->extraAttributes(['class' => 'bg-warning-50']),
        ];
    }
}