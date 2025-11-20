<?php

namespace App\Filament\Resources\Credits\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                
                Select::make('compte_id')
                    ->relationship('compte', 'numero_compte')
                    ->required()
                    ->searchable()
                    ->preload(),
                
                Select::make('agent_id')
                    ->relationship('agent', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Agent de Crédit'),
                
                Select::make('superviseur_id')
                    ->relationship('superviseur', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Superviseur'),
                
                Select::make('type_credit')
                    ->options([
                        'individuel' => 'Individuel',
                        'groupe' => 'Groupe',
                    ])
                    ->required(),
                
                TextInput::make('montant_demande')
                    ->numeric()
                    ->required()
                    ->prefix('USD'),
                
                TextInput::make('montant_accorde')
                    ->numeric()
                    ->prefix('USD'),
                
                TextInput::make('taux_interet')
                    ->numeric()
                    ->suffix('%'),
                
                Select::make('statut_demande')
                    ->options([
                        'en_attente' => 'En Attente',
                        'approuve' => 'Approuvé',
                        'rejete' => 'Rejeté',
                    ])
                    ->required(),
                
                DatePicker::make('date_demande'),
                DatePicker::make('date_octroi'),
                DatePicker::make('date_echeance'),
                        
            ]);
    }
}
