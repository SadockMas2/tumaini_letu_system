<?php

namespace App\Filament\Resources\GroupeSolidaireComptes\Pages;

use App\Filament\Resources\GroupeSolidaireComptes\GroupeSolidaireCompteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGroupeSolidaireCompte extends EditRecord
{
    protected static string $resource = GroupeSolidaireCompteResource::class;

        protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
