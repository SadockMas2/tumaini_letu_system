<?php

namespace App\Filament\Resources\MicrofinanceOverviewResource\Pages;

use App\Filament\Resources\MicrofinanceOverviews\MicrofinanceOverviewResource;
use App\Filament\Widgets\MicrofinanceStatsOverview;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use App\Models\Compte;
use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\CompteEpargne;
use App\Models\PaiementCredit;

class ManageMicrofinanceOverview extends ListRecords
{
    protected static string $resource = MicrofinanceOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rapports')
                ->label('Rapports Avancés')
                ->url(static::$resource::getUrl('rapports'))
                ->color('success')
                ->icon('heroicon-m-chart-bar'),
                
            Action::make('statistiques')
                ->label('Voir Statistiques')
                ->color('primary')
                ->icon('heroicon-m-chart-pie')
                ->action(fn () => $this->redirectToStats()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [

            
                MicrofinanceStatsOverview::class,
        ];
    }

    private function redirectToStats()
    {
        return redirect()->route('filament.pages.dashboard');
    }
}