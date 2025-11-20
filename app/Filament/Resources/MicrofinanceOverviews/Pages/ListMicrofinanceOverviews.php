<?php

namespace App\Filament\Resources\MicrofinanceOverviews\Pages;

use App\Filament\Resources\MicrofinanceOverviews\MicrofinanceOverviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMicrofinanceOverviews extends ListRecords
{
    protected static string $resource = MicrofinanceOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
