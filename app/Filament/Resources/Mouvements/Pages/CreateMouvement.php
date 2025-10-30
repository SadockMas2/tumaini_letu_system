<?php

namespace App\Filament\Resources\Mouvements\Pages;

use App\Filament\Resources\Mouvements\MouvementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMouvement extends CreateRecord
{
    protected static string $resource = MouvementResource::class;

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
