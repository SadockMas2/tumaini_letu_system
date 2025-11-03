<?php

namespace App\Filament\Resources\Tresoreries\Pages;

use App\Filament\Resources\Tresoreries\TresorerieResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTresoreries extends ListRecords
{
    protected static string $resource = TresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
