<?php

namespace App\Filament\Resources\CompteSpecials\Pages;

use App\Filament\Resources\CompteSpecials\CompteSpecialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompteSpecial extends EditRecord
{
    protected static string $resource = CompteSpecialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
