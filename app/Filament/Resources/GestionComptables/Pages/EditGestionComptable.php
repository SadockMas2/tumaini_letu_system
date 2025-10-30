<?php

namespace App\Filament\Resources\GestionComptables\Pages;

use App\Filament\Resources\GestionComptables\GestionComptableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGestionComptable extends EditRecord
{
    protected static string $resource = GestionComptableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
