<?php

namespace App\Filament\Resources\Comptes\Schemas;

use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label('Client')
                    ->options(Client::all()->pluck('nom', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive(),

                TextInput::make('numero_membre')
                    ->label('Numéro du membre')
                    ->disabled()
                    ->default(fn($get) => $get('client_id') ? Client::find($get('client_id'))->numero_membre : ''),

                Select::make('devise')
                    ->options([
                        'USD' => 'USD',
                        'CDF' => 'CDF',
                    ])
                    ->required(),

                TextInput::make('numero_compte')
                    ->label('Numéro du compte')
                    ->disabled()
                    ->default('') // sera rempli automatiquement via le modèle
            ]);
    }
}
