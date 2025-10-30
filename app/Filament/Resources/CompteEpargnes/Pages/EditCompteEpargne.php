<?php

namespace App\Filament\Resources\CompteEpargnes\Pages;

use App\Filament\Resources\CompteEpargnes\CompteEpargneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompteEpargne extends EditRecord
{
    protected static string $resource = CompteEpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
