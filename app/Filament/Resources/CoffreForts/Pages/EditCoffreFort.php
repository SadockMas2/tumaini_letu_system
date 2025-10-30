<?php

namespace App\Filament\Resources\CoffreForts\Pages;

use App\Filament\Resources\CoffreForts\CoffreFortResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoffreFort extends EditRecord
{
    protected static string $resource = CoffreFortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
