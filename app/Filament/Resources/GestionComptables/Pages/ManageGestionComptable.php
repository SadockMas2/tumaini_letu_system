<?php

namespace App\Filament\Resources\GestionComptableResource\Pages;


use App\Filament\Resources\GestionComptables\GestionComptableResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGestionComptable extends ManageRecords
{
    protected static string $resource = GestionComptableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle Écriture'),
        ];
    }
}