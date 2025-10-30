<?php

namespace App\Filament\Resources\GestionTresoreries\Pages;

use App\Filament\Resources\GestionTresoreries\GestionTresorerieResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGestionTresoreries extends ListRecords
{
    protected static string $resource = GestionTresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
