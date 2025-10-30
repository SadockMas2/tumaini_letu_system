<?php

namespace App\Filament\Resources\HistoriqueCompteSpecials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HistoriqueCompteSpecialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cycle_id')
                    ->required()
                    ->numeric(),
                TextInput::make('montant')
                    ->required()
                    ->numeric(),
                TextInput::make('devise')
                    ->required()
                    ->default('CDF'),
            ]);
    }
}
