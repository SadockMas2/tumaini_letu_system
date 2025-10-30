<?php

namespace App\Filament\Resources\CompteTransitoires\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompteTransitoireForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Agent')
                    ->relationship('user', 'name') // montre seulement le nom
                    ->required(),
                Select::make('devise')
                    ->label('Devise')
                    ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                    ->required(),
                TextInput::make('solde')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('statut')
                    ->required()
                    ->default('actif'),
            ]);
    }
}
