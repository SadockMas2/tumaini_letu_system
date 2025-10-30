<?php

namespace App\Filament\Resources\TypeComptes\Pages;

use App\Filament\Resources\TypeComptes\TypeCompteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTypeCompte extends CreateRecord
{
    protected static string $resource = TypeCompteResource::class;
    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
