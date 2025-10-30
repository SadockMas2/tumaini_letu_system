<?php

namespace App\Filament\Resources\MouvementsCredits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MouvementsCreditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Select::make('credit_id')
                ->label('Crédit')
                ->relationship('credit', 'id')
                ->required(),

            Select::make('role_source')
                ->label('Rôle source')
                ->options([
                    'coffre' => 'Coffre',
                    'comptable' => 'Comptable',
                    'caissier' => 'Caissier',
                ])
                ->required(),

            Select::make('role_dest')
                ->label('Rôle destination')
                ->options([
                    'comptable' => 'Comptable',
                    'caissier' => 'Caissier',
                    'membre' => 'Membre',
                ])
                ->required(),

            TextInput::make('montant')
                ->label('Montant')
                ->numeric()
                ->required(),

            Select::make('statut')
                ->label('Statut')
                ->options([
                    'en_attente' => 'En attente',
                    'valide' => 'Validé',
                    'rejete' => 'Rejeté',
                ])
                ->default('en_attente')
                ->required(),
            ]);
    }
}
