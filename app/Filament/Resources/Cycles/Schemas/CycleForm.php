<?php

namespace App\Filament\Resources\Cycles\Schemas;

use App\Models\Client;
use App\Models\GroupeSolidaire;
use App\Models\Cycle;
use App\Models\User;
use App\Models\CompteTransitoire;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Type de Cycle')
                    ->schema([
                        Select::make('type_cycle')
                            ->label('Type de Cycle')
                            ->options([
                                'individuel' => 'Cycle Individuel',
                                'groupe_solidaire' => 'Cycle Groupe Solidaire',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // Réinitialiser les champs lorsqu'on change le type
                                $set('client_id', null);
                                $set('groupe_solidaire_id', null);
                                $set('client_nom', '');
                                $set('numero_cycle', '');
                            }),
                    ])
                    ->columns(1),

                Section::make('Sélection du Membre ou Groupe')
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->options(function () {
                                return Client::all()->mapWithKeys(function ($client) {
                                    $nomComplet = trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom);
                                    return [$client->id => $nomComplet ?: 'Client #' . $client->id];
                                })->toArray();
                            })
                            ->searchable()
                            ->required(fn ($get) => $get('type_cycle') === 'individuel')
                            ->visible(fn ($get) => $get('type_cycle') === 'individuel')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $client = Client::find($state);
                                    if ($client) {
                                        $nomComplet = trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom);
                                        $set('client_nom', $nomComplet ?: 'Client #' . $client->id);
                                        
                                        // Calculer le prochain numéro de cycle
                                        $dernierCycle = Cycle::where('client_id', $state)
                                            ->where('type_cycle', 'individuel')
                                            ->latest('numero_cycle')
                                            ->first();
                                        $set('numero_cycle', $dernierCycle ? $dernierCycle->numero_cycle + 1 : 1);
                                    }
                                }
                            }),

                        Select::make('groupe_solidaire_id')
                            ->label('Groupe Solidaire')
                            ->options(function () {
                                return GroupeSolidaire::all()->mapWithKeys(function ($groupe) {
                                    return [$groupe->id => $groupe->nom_groupe ?: 'Groupe #' . $groupe->id];
                                })->toArray();
                            })
                            ->searchable()
                            ->required(fn ($get) => $get('type_cycle') === 'groupe_solidaire')
                            ->visible(fn ($get) => $get('type_cycle') === 'groupe_solidaire')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $groupe = GroupeSolidaire::find($state);
                                    if ($groupe) {
                                        $set('client_nom', $groupe->nom_groupe ?: 'Groupe #' . $groupe->id);
                                        
                                        // Calculer le prochain numéro de cycle
                                        $dernierCycle = Cycle::where('groupe_solidaire_id', $state)
                                            ->where('type_cycle', 'groupe_solidaire')
                                            ->latest('numero_cycle')
                                            ->first();
                                        $set('numero_cycle', $dernierCycle ? $dernierCycle->numero_cycle + 1 : 1);
                                    }
                                }
                            }),

                        TextInput::make('client_nom')
                            ->label('Nom Membre/Groupe')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('numero_cycle')
                            ->label('Numéro du Cycle')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                // AJOUT : Section pour la sélection de l'agent
                Section::make('Agent Collecteur')
                    ->schema([
                        Select::make('user_id')
                            ->label('Agent')
                            ->options(User::whereHas('roles', function ($query) {
                                $query->where('name', 'AgentCollecteur');
                            })->get()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('agent_nom', $user->name);
                                    }
                                }
                            }),

                        TextInput::make('agent_nom')
                            ->label('Nom de l\'Agent')
                            ->disabled()
                            ->dehydrated(),

                        // AJOUT : Affichage du solde du compte transitoire
                        TextInput::make('solde_agent_display')
                            ->label('Solde Compte Transitoire')
                            ->disabled()
                            ->dehydrated(false)
                            ->default('Sélectionnez un agent')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $userId = $get('user_id');
                                $devise = $get('devise') ?? 'CDF';
                                
                                if ($userId) {
                                    $compteTransitoire = CompteTransitoire::where('user_id', $userId)
                                        ->where('devise', $devise)
                                        ->first();
                                    
                                    $solde = $compteTransitoire ? number_format($compteTransitoire->solde, 2) . ' ' . $devise : '0.00 ' . $devise;
                                    $set('solde_agent_display', $solde);
                                }
                            }),
                    ])
                    ->columns(2),

                Section::make('Paramètres du Cycle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('solde_initial')
                                    ->label('Solde Initial')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Vérifier si le solde est suffisant
                                        $userId = $get('user_id');
                                        $devise = $get('devise') ?? 'CDF';
                                        $montant = (float) $state;
                                        
                                        if ($userId && $montant > 0) {
                                            $compteTransitoire = CompteTransitoire::where('user_id', $userId)
                                                ->where('devise', $devise)
                                                ->first();
                                            
                                            if ($compteTransitoire && $montant > $compteTransitoire->solde) {
                                                $set('solde_initial', $compteTransitoire->solde);
                                            }
                                        }
                                    }),

                                Select::make('devise')
                                    ->options([
                                        'USD' => 'USD', 
                                        'CDF' => 'CDF'
                                    ])
                                    ->required()
                                    ->default('CDF')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Mettre à jour l'affichage du solde
                                        $userId = $get('user_id');
                                        if ($userId) {
                                            $compteTransitoire = CompteTransitoire::where('user_id', $userId)
                                                ->where('devise', $state)
                                                ->first();
                                            
                                            $solde = $compteTransitoire ? number_format($compteTransitoire->solde, 2) . ' ' . $state : '0.00 ' . $state;
                                            $set('solde_agent_display', $solde);
                                        }
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('date_debut')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('date_fin')
                                    ->required(),
                            ]),

                        Select::make('statut')
                            ->options([
                                'ouvert' => 'Ouvert', 
                                'clôturé' => 'Clôturé'
                            ])
                            ->default('ouvert')
                            ->required(),
                    ]),

                // Champs cachés pour la logique
                Hidden::make('type_cycle')
                    ->default('individuel'),
            ]);
    }
}