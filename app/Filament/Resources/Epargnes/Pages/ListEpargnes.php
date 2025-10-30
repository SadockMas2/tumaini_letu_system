<?php

namespace App\Filament\Resources\Epargnes\Pages;

use App\Filament\Resources\Epargnes\EpargneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEpargnes extends ListRecords
{
    protected static string $resource = EpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
