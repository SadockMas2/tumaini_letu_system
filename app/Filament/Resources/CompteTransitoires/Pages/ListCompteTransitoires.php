<?php

namespace App\Filament\Resources\CompteTransitoires\Pages;

use App\Filament\Resources\CompteTransitoires\CompteTransitoireResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompteTransitoires extends ListRecords
{
    protected static string $resource = CompteTransitoireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
