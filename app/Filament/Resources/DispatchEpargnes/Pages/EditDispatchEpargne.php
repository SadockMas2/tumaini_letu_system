<?php

namespace App\Filament\Resources\DispatchEpargnes\Pages;

use App\Filament\Resources\DispatchEpargnes\DispatchEpargneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDispatchEpargne extends EditRecord
{
    protected static string $resource = DispatchEpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
