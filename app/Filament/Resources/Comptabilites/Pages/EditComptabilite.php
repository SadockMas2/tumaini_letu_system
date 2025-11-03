<?php

namespace App\Filament\Resources\Comptabilites\Pages;

use App\Filament\Resources\Comptabilites\ComptabiliteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComptabilite extends EditRecord
{
    protected static string $resource = ComptabiliteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
