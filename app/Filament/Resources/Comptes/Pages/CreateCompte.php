<?php

namespace App\Filament\Resources\Comptes\Pages;

use App\Filament\Resources\Comptes\CompteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompte extends CreateRecord
{
    protected static string $resource = CompteResource::class;
        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
