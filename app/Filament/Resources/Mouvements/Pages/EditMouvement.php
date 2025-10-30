<?php

namespace App\Filament\Resources\Mouvements\Pages;

use App\Filament\Resources\Mouvements\MouvementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMouvement extends EditRecord
{
    protected static string $resource = MouvementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
