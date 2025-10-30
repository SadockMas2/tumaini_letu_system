<?php

namespace App\Filament\Resources\CoffreForts\Pages;

use App\Filament\Resources\CoffreForts\CoffreFortResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoffreForts extends ListRecords
{
    protected static string $resource = CoffreFortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
