<?php

namespace App\Filament\Resources\CompteTransitoires\Pages;

use App\Filament\Resources\CompteTransitoires\CompteTransitoireResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompteTransitoire extends CreateRecord
{
    protected static string $resource = CompteTransitoireResource::class;
    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
