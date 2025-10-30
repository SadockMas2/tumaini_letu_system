<?php

namespace App\Filament\Resources\CompteSpecials\Pages;

use App\Filament\Resources\CompteSpecials\CompteSpecialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompteSpecial extends CreateRecord
{
    protected static string $resource = CompteSpecialResource::class;
      protected function canCreate(): bool
    {
        return false;
    }

       protected function canDelete(): bool
    {
        return false;
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
