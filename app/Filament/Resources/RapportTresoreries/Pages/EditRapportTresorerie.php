<?php

namespace App\Filament\Resources\RapportTresoreries\Pages;

use App\Filament\Resources\RapportTresoreries\RapportTresorerieResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRapportTresorerie extends EditRecord
{
    protected static string $resource = RapportTresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
