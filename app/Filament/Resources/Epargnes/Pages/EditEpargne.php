<?php

namespace App\Filament\Resources\Epargnes\Pages;

use App\Filament\Resources\Epargnes\EpargneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEpargne extends EditRecord
{
    protected static string $resource = EpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
