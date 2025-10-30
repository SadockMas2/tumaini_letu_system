<?php

namespace App\Filament\Resources\MouvementsCredits\Pages;

use App\Filament\Resources\MouvementsCredits\MouvementsCreditResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMouvementsCredits extends ListRecords
{
    protected static string $resource = MouvementsCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
