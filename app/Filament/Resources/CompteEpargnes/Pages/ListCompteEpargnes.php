<?php

namespace App\Filament\Resources\CompteEpargnes\Pages;

use App\Filament\Resources\CompteEpargnes\CompteEpargneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompteEpargnes extends ListRecords
{
    protected static string $resource = CompteEpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
