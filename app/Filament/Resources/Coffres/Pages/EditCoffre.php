<?php

namespace App\Filament\Resources\Coffres\Pages;

use App\Filament\Resources\Coffres\CoffreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoffre extends EditRecord
{
    protected static string $resource = CoffreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
