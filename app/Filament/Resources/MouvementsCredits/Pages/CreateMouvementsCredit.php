<?php

namespace App\Filament\Resources\MouvementsCredits\Pages;

use App\Filament\Resources\MouvementsCredits\MouvementsCreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMouvementsCredit extends CreateRecord
{
    protected static string $resource = MouvementsCreditResource::class;

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
