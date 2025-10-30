<?php

namespace App\Filament\Resources\CompteEpargnes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompteEpargneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Section::make('Informations du compte')
                    ->schema([
                        Select::make('type_compte')
                            ->options([
                                'individuel' => 'Individuel',
                                'groupe_solidaire' => 'Groupe Solidaire',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('client_id', null))
                            ->afterStateUpdated(fn ($state, callable $set) => $set('groupe_solidaire_id', null)),
                            
                        Select::make('client_id')
                            ->label('Client')
                            ->options(\App\Models\Client::all()->pluck('nom_complet', 'id'))
                            ->searchable()
                            ->visible(fn (callable $get) => $get('type_compte') === 'individuel')
                            ->required(fn (callable $get) => $get('type_compte') === 'individuel'),
                            
                        Select::make('groupe_solidaire_id')
                            ->label('Groupe Solidaire')
                            ->options(\App\Models\GroupeSolidaire::all()->pluck('nom_groupe', 'id'))
                            ->searchable()
                            ->visible(fn (callable $get) => $get('type_compte') === 'groupe_solidaire')
                            ->required(fn (callable $get) => $get('type_compte') === 'groupe_solidaire'),
                            
                        Select::make('devise')
                            ->options([
                                'USD' => 'USD',
                                'CDF' => 'CDF',
                            ])
                            ->default('USD')
                            ->required(),
                            
                        TextInput::make('taux_interet')
                            ->label('Taux d\'intérêt (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(0),
                            
                        TextInput::make('solde_minimum')
                            ->label('Solde minimum')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                            
                        Select::make('statut')
                            ->options([
                                'actif' => 'Actif',
                                'inactif' => 'Inactif',
                                'suspendu' => 'Suspendu',
                            ])
                            ->default('actif')
                            ->required(),
                            
                        Textarea::make('conditions')
                            ->label('Conditions particulières')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }
}
