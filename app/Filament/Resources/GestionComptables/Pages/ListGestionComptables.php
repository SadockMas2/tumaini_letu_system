<?php

namespace App\Filament\Resources\GestionComptables\Pages;

use App\Filament\Resources\GestionComptables\GestionComptableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGestionComptables extends ListRecords
{
    protected static string $resource = GestionComptableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
