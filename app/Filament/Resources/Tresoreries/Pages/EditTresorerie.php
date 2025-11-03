<?php

namespace App\Filament\Resources\Tresoreries\Pages;

use App\Filament\Resources\Tresoreries\TresorerieResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTresorerie extends EditRecord
{
    protected static string $resource = TresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
