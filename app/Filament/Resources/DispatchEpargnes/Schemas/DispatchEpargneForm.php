<?php

namespace App\Filament\Resources\DispatchEpargnes\Schemas;

use App\Models\Client;
use App\Models\Epargne;
use App\Models\GroupeSolidaire;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DispatchEpargneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations du Groupe')
                    ->schema([
                       Select::make('groupe_solidaire_id')
                            ->label('Groupe Solidaire')
                            ->options(GroupeSolidaire::all()->pluck('nom_groupe', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $groupe = GroupeSolidaire::find($state);
                                    if ($groupe) {
                                        $set('groupe_nom', $groupe->nom_groupe);
                                        
                                        // Calculer le total des épargnes en attente pour ce groupe aujourd'hui
                                        $totalEpargnes = Epargne::where('groupe_solidaire_id', $state)
                                            ->where('statut', 'en_attente_dispatch')
                                            ->whereDate('created_at', today())
                                            ->sum('montant');
                                        
                                        $set('montant_total', $totalEpargnes);
                                        $set('devise', 'USD');
                                    }
                                }
                            }),
                            
                        TextInput::make('groupe_nom')
                            ->label('Nom du Groupe')
                            ->disabled(),
                            
                     TextInput::make('montant_total')
                            ->label('Montant Total à Dispatcher')
                            ->numeric()
                            ->disabled(),
                            
                        TextInput::make('devise')
                            ->label('Devise')
                            ->default('USD')
                            ->disabled(),
                    ])
                    ->columns(2),
                    
                Section::make('Répartition entre les Membres')
                    ->schema([
                       Repeater::make('repartition')
                            ->schema([
                               Select::make('membre_id')
                                    ->label('Membre')
                                    ->options(function (callable $get) {
                                        $groupeId = $get('../../groupe_solidaire_id');
                                        if (!$groupeId) return [];
                                        
                                        $groupe = GroupeSolidaire::find($groupeId);
                                        return $groupe ? $groupe->membres->pluck('nom_complet', 'id')->toArray() : [];
                                    })
                                    ->required()
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $client = Client::find($state);
                                            if ($client) {
                                                $set('membre_nom', $client->nom_complet);
                                            }
                                        }
                                    }),
                                    
                                TextInput::make('membre_nom')
                                    ->label('Nom du Membre')
                                    ->disabled(),
                               TextInput::make('montant')
                                    ->label('Montant Attribué')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        self::calculerTotalRepartition($get, $set);
                                    }),
                            ])
                            ->columns(3)
                            ->required()
                            ->minItems(1)
                            ->itemLabel(fn (array $state): ?string => $state['membre_nom'] ?? null),
                            
                        TextInput::make('total_reparti')
                            ->label('Total Réparti')
                            ->numeric()
                            ->disabled(),
                            
                        TextInput::make('reste_a_repartir')
                            ->label('Reste à Répartir')
                            ->numeric()
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * Calculer le total de la répartition
     */
    private static function calculerTotalRepartition(callable $get, callable $set): void
    {
        $repartitions = $get('../../repartition') ?? [];
        $total = collect($repartitions)->sum('montant');
        $montantTotal = $get('../../montant_total') ?? 0;
        
        $set('../../total_reparti', $total);
        $set('../../reste_a_repartir', $montantTotal - $total);
    }
}