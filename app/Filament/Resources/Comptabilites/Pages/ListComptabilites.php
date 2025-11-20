<?php

namespace App\Filament\Resources\Comptabilites\Pages;

use App\Filament\Resources\Comptabilites\ComptabiliteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComptabilites extends ListRecords
{
    protected static string $resource = ComptabiliteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    
   
    
}
