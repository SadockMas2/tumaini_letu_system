<?php

namespace App\Filament\Resources\GroupeSolidaires\Pages;

use App\Filament\Resources\GroupeSolidaires\GroupeSolidaireResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGroupeSolidaire extends EditRecord
{
    protected static string $resource = GroupeSolidaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
