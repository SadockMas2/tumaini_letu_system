<?php

namespace App\Filament\Resources\Comptes\Pages;

use App\Filament\Resources\Comptes\CompteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompte extends EditRecord
{
    protected static string $resource = CompteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
