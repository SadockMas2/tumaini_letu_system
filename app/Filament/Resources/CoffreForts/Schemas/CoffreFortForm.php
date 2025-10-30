<?php

namespace App\Filament\Resources\CoffreForts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CoffreFortForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du Coffre')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nom_coffre')
                                ->required()
                                ->maxLength(255),
                            Select::make('devise')
                                ->options([
                                    'USD' => 'USD',
                                    'CDF' => 'CDF',
                                    'EUR' => 'EUR'
                                ])
                                ->default('USD')
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('responsable_id')
                                ->relationship('responsable', 'name')
                                ->required(),
                            TextInput::make('agence')
                                ->required()
                                ->maxLength(255),
                        ]),
                        TextInput::make('solde_actuel')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->default(0),
                        Toggle::make('est_actif')
                            ->default(true)
                            ->required(),
                    ])
            ]);
    }
}
