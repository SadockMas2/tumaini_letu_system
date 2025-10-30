<?php

namespace App\Filament\Resources\TypeComptes\Pages;

use App\Filament\Resources\TypeComptes\TypeCompteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTypeComptes extends ListRecords
{
    protected static string $resource = TypeCompteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
