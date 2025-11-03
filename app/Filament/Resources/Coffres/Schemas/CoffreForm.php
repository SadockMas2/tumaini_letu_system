<?php

namespace App\Filament\Resources\Coffres\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CoffreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nom')
                    ->required()
                    ->label('Nom du Coffre'),
                Select::make('devise')
                    ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                    ->required()
                    ->default('USD'),
                TextInput::make('solde_actuel')
                    ->numeric()
                    ->required()
                    ->label('Solde Actuel'),
                Select::make('responsable_id')
                    ->relationship('responsable', 'name')
                    ->required()
                    ->label('Responsable'),
                Select::make('agence_id')
                    ->relationship('agence', 'nom_agence')
                    ->required()
                    ->label('Agence'),
            ]);
    }
}
