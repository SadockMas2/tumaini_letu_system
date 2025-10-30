<?php

namespace App\Filament\Resources\DispatchEpargneResource\Pages;

use App\Filament\Resources\DispatchEpargnes\DispatchEpargneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispatchEpargnes extends ListRecords
{
    protected static string $resource = DispatchEpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pas de création depuis cette page
        ];
    }
}