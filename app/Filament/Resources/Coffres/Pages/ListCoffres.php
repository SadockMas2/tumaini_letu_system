<?php

namespace App\Filament\Resources\Coffres\Pages;

use App\Filament\Resources\Coffres\CoffreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoffres extends ListRecords
{
    protected static string $resource = CoffreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
