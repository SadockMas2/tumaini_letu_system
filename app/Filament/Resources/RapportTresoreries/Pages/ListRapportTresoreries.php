<?php

namespace App\Filament\Resources\RapportTresoreries\Pages;

use App\Filament\Resources\RapportTresoreries\RapportTresorerieResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRapportTresoreries extends ListRecords
{
    protected static string $resource = RapportTresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
