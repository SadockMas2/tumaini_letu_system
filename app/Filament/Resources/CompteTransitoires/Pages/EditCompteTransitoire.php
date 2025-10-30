<?php

namespace App\Filament\Resources\CompteTransitoires\Pages;

use App\Filament\Resources\CompteTransitoires\CompteTransitoireResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompteTransitoire extends EditRecord
{
    protected static string $resource = CompteTransitoireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
