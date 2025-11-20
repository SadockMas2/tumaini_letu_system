<?php

namespace App\Filament\Resources\Credits\Pages;

use App\Filament\Resources\Credits\CreditResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCredit extends EditRecord
{
    protected static string $resource = CreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
