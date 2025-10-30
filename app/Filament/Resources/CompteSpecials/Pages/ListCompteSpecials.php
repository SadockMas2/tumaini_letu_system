<?php

namespace App\Filament\Resources\CompteSpecials\Pages;

use App\Filament\Resources\CompteSpecials\CompteSpecialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompteSpecials extends ListRecords
{
    protected static string $resource = CompteSpecialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

      protected function canCreate(): bool
    {
        return false;
    }
       protected function canDelete(): bool
    {
        return false;
    }
}
