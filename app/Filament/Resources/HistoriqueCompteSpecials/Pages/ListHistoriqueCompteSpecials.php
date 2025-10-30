<?php

namespace App\Filament\Resources\HistoriqueCompteSpecials\Pages;

use App\Filament\Resources\HistoriqueCompteSpecials\HistoriqueCompteSpecialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHistoriqueCompteSpecials extends ListRecords
{
    protected static string $resource = HistoriqueCompteSpecialResource::class;

     protected function canCreate(): bool
    {
        return false;
    }

    protected function canDelete(): bool
    {
        return false;
    }
}
