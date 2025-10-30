<?php

namespace App\Filament\Resources\TypeComptes\Pages;

use App\Filament\Resources\TypeComptes\TypeCompteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTypeCompte extends EditRecord
{
    protected static string $resource = TypeCompteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
