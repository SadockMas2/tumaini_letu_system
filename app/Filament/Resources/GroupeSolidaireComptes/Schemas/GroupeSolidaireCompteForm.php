<?php

namespace App\Filament\Resources\GroupeSolidaireComptes\Schemas;

use App\Models\Compte;
use App\Models\GroupeSolidaire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GroupeSolidaireCompteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Section::make('Informations du Groupe')
                    ->schema([
                        Select::make('groupe_solidaire_id')
                            ->label('Groupe Solidaire')
                            ->relationship('groupeSolidaire', 'nom_groupe')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $groupe = GroupeSolidaire::find($state);
                                    if ($groupe) {
                                        $set('nom', $groupe->nom_groupe);
                                    }
                                }
                            }),

                        TextInput::make('nom')
                            ->label('Nom du Groupe')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(2),

                Section::make('Informations du Compte')
                    ->schema([
                        // Champ caché pour afficher le numéro de compte généré (en lecture seule)
                        TextInput::make('numero_compte')
                            ->label('Numéro de Compte')
                            ->default('Génération automatique...')
                            ->disabled()
                            ->dehydrated(false) // Ne pas sauvegarder cette valeur
                            ->afterStateHydrated(function (TextInput $component, ?Compte $record) {
                                if ($record && $record->numero_compte) {
                                    $component->state($record->numero_compte);
                                } else {
                                    $component->state('Génération automatique...');
                                }
                            }),

                        Select::make('devise')
                            ->options([
                                'USD' => 'USD',
                                'CDF' => 'CDF',
                            ])
                            ->required()
                            ->default('USD'),

                        TextInput::make('solde')
                            ->label('Solde')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),

                        Select::make('statut')
                            ->options([
                                'actif' => 'Actif',
                                'inactif' => 'Inactif',
                                'suspendu' => 'Suspendu',
                            ])
                            ->default('actif')
                            ->required(),
                    ])
                    ->columns(2),
            
            ]);
    }
}
