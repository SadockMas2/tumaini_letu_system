<?php

namespace App\Filament\Resources\CompteSpecials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompteSpecialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('solde')
                    ->label('Solde total')
                    ->numeric()
                    ->disabled()
                    ->required(),
                TextInput::make('devise')
                    ->label('Devise')
                    ->disabled()
                    ->required()
            ]);
    }
}
