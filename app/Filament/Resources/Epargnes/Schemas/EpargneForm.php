<?php

namespace App\Filament\Resources\Epargnes\Schemas;

use App\Models\Client;
use App\Models\GroupeSolidaire;
use App\Models\Cycle;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EpargneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Type d\'Épargne')
                    ->schema([
                        Select::make('type_epargne')
                            ->label('Type d\'Épargne')
                            ->options([
                                'individuel' => 'Épargne Individuelle',
                                'groupe_solidaire' => 'Épargne Groupe Solidaire',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('client_id', null);
                                $set('groupe_solidaire_id', null);
                                $set('client_nom', null);
                                $set('cycle_id', null);
                                $set('montant', null);
                                $set('devise', null);
                            }),
                    ])
                    ->columns(1),

                Section::make('Sélection du Membre ou Groupe')
                    ->schema([
                        Select::make('client_id')
                            ->label('Membre')
                            ->options(function () {
                                return Client::all()->mapWithKeys(function ($client) {
                                    $nomComplet = trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom);
                                    return [$client->id => $nomComplet ?: 'Inconnu'];
                                })->toArray();
                            })
                            ->required(fn ($get) => $get('type_epargne') === 'individuel')
                            ->visible(fn ($get) => $get('type_epargne') === 'individuel')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('client_nom', null);
                                $set('cycle_id', null);
                                $set('montant', null);
                                $set('devise', null);

                                $client = Client::find($state);
                                if ($client) {
                                    $set('client_nom', trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom));
                                    self::trouverCycle($set, $get, $state, 'client_id');
                                }
                            }),

                        Select::make('groupe_solidaire_id')
                            ->label('Groupe Solidaire')
                            ->options(function () {
                                return GroupeSolidaire::all()->mapWithKeys(function ($groupe) {
                                    return [$groupe->id => $groupe->nom_groupe ?: 'Groupe #' . $groupe->id];
                                })->toArray();
                            })
                            ->required(fn ($get) => $get('type_epargne') === 'groupe_solidaire')
                            ->visible(fn ($get) => $get('type_epargne') === 'groupe_solidaire')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('client_nom', null);
                                $set('cycle_id', null);
                                $set('montant', null);
                                $set('devise', null);

                                $groupe = GroupeSolidaire::find($state);
                                if ($groupe) {
                                    $set('client_nom', $groupe->nom_groupe);
                                    self::trouverCycle($set, $get, $state, 'groupe_solidaire_id');
                                }
                            }),

                        TextInput::make('client_nom')
                            ->label('Nom Membre/Groupe')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Hidden::make('agent_nom')
                    ->default(fn () => Auth::user()->name ?? 'Agent'),

                Section::make('Détails de l\'Épargne')
                    ->schema([
                        Select::make('devise')
                            ->label('Devise')
                            ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('cycle_id', null);
                                $set('montant', null);

                                $typeEpargne = $get('type_epargne');
                                $clientId = $get('client_id');
                                $groupeId = $get('groupe_solidaire_id');

                                if ($typeEpargne === 'individuel' && $clientId) {
                                    self::trouverCycle($set, $get, $clientId, 'client_id');
                                } elseif ($typeEpargne === 'groupe_solidaire' && $groupeId) {
                                    self::trouverCycle($set, $get, $groupeId, 'groupe_solidaire_id');
                                }
                            }),

                        Select::make('cycle_id')
                            ->label('Cycle')
                            ->options(function ($get) {
                                $typeEpargne = $get('type_epargne');
                                $clientId = $get('client_id');
                                $groupeId = $get('groupe_solidaire_id');
                                $devise = $get('devise');

                                if (!$devise) return [];

                                if ($typeEpargne === 'individuel' && $clientId) {
                                    return Cycle::where('client_id', $clientId)
                                        ->where('devise', $devise)
                                        ->get()
                                        ->mapWithKeys(function ($cycle) {
                                            $statut = $cycle->statut === 'ouvert' ? ' (Ouvert)' : ' (Clôturé)';
                                            return [$cycle->id => $cycle->numero_cycle . $statut];
                                        })
                                        ->toArray();
                                } elseif ($typeEpargne === 'groupe_solidaire' && $groupeId) {
                                    return Cycle::where('groupe_solidaire_id', $groupeId)
                                        ->where('devise', $devise)
                                        ->get()
                                        ->mapWithKeys(function ($cycle) {
                                            $statut = $cycle->statut === 'ouvert' ? ' (Ouvert)' : ' (Clôturé)';
                                            return [$cycle->id => $cycle->numero_cycle . $statut];
                                        })
                                        ->toArray();
                                }

                                return [];
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state) {
                                    $cycle = Cycle::find($state);
                                    if ($cycle) {
                                        $set('devise', $cycle->devise);
                                        $set('montant', $cycle->solde_initial);
                                        

                                         // Vérifier la limite
            $currentCount = $cycle->epargnes()->count();
            $max = $cycle->nombre_max_epargnes ?? 30;
            
            if ($currentCount >= $max) {
                Notification::make()
                    ->title('Cycle complet')
                    ->body("Ce cycle a déjà {$max} épargnes. Veuillez sélectionner un autre cycle ou en créer un nouveau.")
                    ->danger()
                    ->send();
            } elseif ($currentCount >= ($max - 5)) {
                $remaining = $max - $currentCount;
                Notification::make()
                    ->title('Limite proche')
                    ->body("Il reste seulement {$remaining} épargnes disponibles dans ce cycle.")
                    ->warning()
                    ->send();
            }
                                        // Avertissement si le cycle est clôturé
                                        if ($cycle->statut === 'clôturé') {
                                            Notification::make()
                                                ->title('Cycle clôturé')
                                                ->body('Attention : Ce cycle est clôturé. L\'épargne sera enregistrée mais ne pourra pas être utilisée pour de nouveaux crédits.')
                                                ->warning()
                                                ->send();
                                        }
                                    }
                                } else {
                                    $set('montant', null);
                                }
                            }),

                        // AFFICHAGE DES INFORMATIONS DU CYCLE - Version Filament v4
                 
TextInput::make('info_cycle')
    ->label('Statut du Cycle')
    ->disabled()
    ->dehydrated()
    ->default('Sélectionnez un cycle')
    ->helperText(function ($get) {
        $cycleId = $get('cycle_id');
        
        if (!$cycleId) {
            return 'Sélectionnez un cycle pour voir les informations';
        }
        
        $cycle = Cycle::find($cycleId);
        if (!$cycle) {
            return 'Cycle non trouvé';
        }
        
        // UTILISER LE VRAI COMPTEUR
        $epargnesExistantes = $cycle->getNombreEpargnesReelAttribute();
        $epargnesRestantes = $cycle->epargnes_restantes;
        
        return "Statut: " . ucfirst($cycle->statut) . 
               " | Épargnes réelles: {$epargnesExistantes}/{$cycle->nombre_max_epargnes}" .
               " | Restantes: {$epargnesRestantes}";
    }),
                        TextInput::make('montant')
                            ->label('Montant de l\'épargne')
                            ->numeric()
                            ->required()
                            ->default(null)
                            ->dehydrated()
                            //  ->disabled()
                           
                            ->helperText('Ce montant correspond au solde initial du cycle sélectionné'),

                        Select::make('statut')
                            ->options([
                                'en_attente_dispatch' => 'En attente dispatch',
                                'en_attente_validation' => 'En attente validation', 
                                'valide' => 'Validé',
                                'rejet' => 'Rejeté',
                            ])
                            ->default('en_attente_dispatch')
                            ->required()
                            ->disabled(fn ($get) => $get('type_epargne') === 'groupe_solidaire')
                            ->hidden(fn ($get) => $get('type_epargne') === 'groupe_solidaire'),

                        Hidden::make('user_id')
                            ->default(fn () => Auth::id()),

                        DateTimePicker::make('date_apport')
                            ->label('Date d\'apport')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Hidden::make('type_epargne')
                    ->default('individuel'),

                Hidden::make('premiere_mise')
                    ->default(false),
            ]);
    }

    /**
     * Méthode pour trouver un cycle (ouvert ou clôturé)
     */
    private static function trouverCycle($set, $get, $id, $typeField)
    {
        $devise = $get('devise');
        
        if (!$devise) {
            // Essayer avec les deux devises
            $devises = ['USD', 'CDF'];
            foreach ($devises as $devise) {
                $cycle = Cycle::where($typeField, $id)
                    ->where('devise', $devise)
                    ->latest('id')
                    ->first();
                
                if ($cycle) {
                    $set('devise', $cycle->devise);
                    $set('cycle_id', $cycle->id);
                    $set('montant', $cycle->solde_initial);
                    
                    // Avertissement si clôturé
                    if ($cycle->statut === 'clôturé') {
                        Notification::make()
                            ->title('Cycle clôturé sélectionné')
                            ->body('Le cycle sélectionné est clôturé. Vous pouvez quand même enregistrer l\'épargne.')
                            ->warning()
                            ->send();
                    }
                    return;
                }
            }
            
            // Aucun cycle trouvé
            $set('cycle_id', null);
            $set('montant', null);
            
        } else {
            // Recherche avec la devise spécifique
            $cycle = Cycle::where($typeField, $id)
                ->where('devise', $devise)
                ->latest('id')
                ->first();

            if ($cycle) {
                $set('cycle_id', $cycle->id);
                $set('montant', $cycle->solde_initial);
                
                // Avertissement si clôturé
                if ($cycle->statut === 'clôturé') {
                    Notification::make()
                        ->title('Cycle clôturé sélectionné')
                        ->body('Le cycle sélectionné est clôturé. Vous pouvez quand même enregistrer l\'épargne.')
                        ->warning()
                        ->send();
                }
            } else {
                $set('cycle_id', null);
                $set('montant', null);
            }
        }
    }
}