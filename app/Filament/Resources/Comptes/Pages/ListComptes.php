<?php

namespace App\Filament\Resources\Comptes\Pages;

use App\Filament\Resources\Comptes\CompteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComptes extends ListRecords
{
    protected static string $resource = CompteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
