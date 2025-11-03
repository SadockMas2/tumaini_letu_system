<?php

namespace App\Filament\Resources\Tresoreries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TresorerieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
           Select::make('type_caisse')
                    ->options([
                        'petite_caisse' => 'Petite Caisse (< 100 USD)',
                        'grande_caisse' => 'Grande Caisse',
                    ])
                    ->required(),
            Select::make('devise')
                    ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                    ->required()
                    ->default('USD'),
                TextInput::make('solde')
                    ->numeric()
                    ->required()
                    ->label('Solde Actuel')
                    ->default(0),
                TextInput::make('plafond')
                    ->numeric()
                    ->required()
                    ->label('Plafond'),
                TextInput::make('nom') // CHANGÉ : description → nom
                    ->label('Nom/Description')
                    ->required()
                    ->maxLength(255),
                Select::make('statut')
                    ->options([
                        'actif' => 'Actif',
                        'bloque' => 'bloque'
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
