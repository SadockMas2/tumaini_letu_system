<?php

namespace App\Filament\Resources\Mouvements\Pages;

use App\Filament\Resources\Mouvements\MouvementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMouvements extends ListRecords
{
    protected static string $resource = MouvementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
