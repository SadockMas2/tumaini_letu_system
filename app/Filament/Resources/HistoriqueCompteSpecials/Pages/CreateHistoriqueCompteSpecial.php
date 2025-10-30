<?php

namespace App\Filament\Resources\HistoriqueCompteSpecials\Pages;

use App\Filament\Resources\HistoriqueCompteSpecials\HistoriqueCompteSpecialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHistoriqueCompteSpecial extends CreateRecord
{
    protected static string $resource = HistoriqueCompteSpecialResource::class;

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
