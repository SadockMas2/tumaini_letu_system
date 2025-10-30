<?php

namespace App\Filament\Resources\GroupeSolidaireComptes\Pages;

use App\Filament\Resources\GroupeSolidaireComptes\GroupeSolidaireCompteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGroupeSolidaireCompte extends CreateRecord
{
    protected static string $resource = GroupeSolidaireCompteResource::class;

       protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
