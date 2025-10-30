<?php

namespace App\Filament\Resources\GestionTresoreries\Pages;

use App\Filament\Resources\GestionTresoreries\GestionTresorerieResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGestionTresorerie extends EditRecord
{
    protected static string $resource = GestionTresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
