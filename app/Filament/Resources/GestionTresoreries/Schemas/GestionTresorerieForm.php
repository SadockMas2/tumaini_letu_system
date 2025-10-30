<?php

namespace App\Filament\Resources\GestionTresoreries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class GestionTresorerieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
             ->components([
               TextInput::make('nom')
                    ->required()
                    ->label('Nom du Coffre'),
                TextInput::make('solde_actuel')
                    ->numeric()
                    ->required()
                    ->label('Solde Actuel (USD)'),
                Select::make('responsable_id')
                    ->relationship('responsable', 'name')
                    ->required()
                    ->label('Responsable'),
                TextInput::make('plafond_journalier')
                    ->numeric()
                    ->label('Plafond Journalier (USD)'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(2),
            ]);
    }
}
