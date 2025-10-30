<?php

namespace App\Filament\Resources\HistoriqueCompteSpecials\Pages;

use App\Filament\Resources\HistoriqueCompteSpecials\HistoriqueCompteSpecialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHistoriqueCompteSpecial extends EditRecord
{
    protected static string $resource = HistoriqueCompteSpecialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
