<?php

namespace App\Filament\Resources\GroupeSolidaireComptes\Pages;

use App\Filament\Resources\GroupeSolidaireComptes\GroupeSolidaireCompteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGroupeSolidaireComptes extends ListRecords
{
    protected static string $resource = GroupeSolidaireCompteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
