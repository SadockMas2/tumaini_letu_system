<?php

namespace App\Filament\Resources\Epargnes\Actions;

use App\Models\Cycle;
use App\Models\Epargne;
use App\Models\Client;
use App\Models\GroupeSolidaire;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateMultipleEpargnesAction
{
    public static function make(): Action
    {
        return Action::make('create_multiple')
            ->label('Créer des épargnes en masse')
            ->icon('heroicon-o-document-plus')
            ->schema([
                Section::make('Configuration de la création en masse')
                    ->schema([
                        ToggleButtons::make('type_epargne')
                            ->label('Type d\'épargne')
                            ->options([
                                'individuel' => 'Épargne Individuelle',
                                // 'groupe_solidaire' => 'Épargne Groupe Solidaire',
                            ])
                            ->default('individuel')
                            ->inline()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('client_id', null);
                                $set('cycle_id', null);
                                $set('devise', null);
                                $set('montant', null);
                                $set('nombre_epargnes', 1);
                            }),
                        
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
                                
                                if ($typeEpargne === 'individuel' && $clientId) {
                                    self::trouverCycleClient($set, $get, $clientId);
                                }
                            }),
                    ])
                    ->columns(2),
                
                Section::make('Sélection du Membre et Quantité')
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
                                $set('cycle_id', null);
                                $set('montant', null);
                                $set('devise', null);
                                
                                if ($state) {
                                    $client = Client::find($state);
                                    if ($client) {
                                        self::trouverCycleClient($set, $get, $state);
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
                            ->required(fn ($get) => $get('type_epargne') === 'groupe_solidaire')
                            ->visible(fn ($get) => $get('type_epargne') === 'groupe_solidaire')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('cycle_id', null);
                                $set('montant', null);
                                $set('devise', null);
                                
                                if ($state) {
                                    $groupe = GroupeSolidaire::find($state);
                                    if ($groupe) {
                                        self::trouverCycleGroupe($set, $get, $state);
                                    }
                                }
                            }),
                        
                        // AJOUTER CE CHAMP POUR MULTIPLIER LES ÉPARGNES
                        TextInput::make('nombre_epargnes')
                            ->label('Nombre d\'épargnes à créer')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->helperText(function ($get) {
                                $cycleId = $get('cycle_id');
                                if (!$cycleId) return 'Sélectionnez un cycle d\'abord';
                                
                                $cycle = Cycle::find($cycleId);
                                $currentCount = $cycle->epargnes()->count();
                                $max = $cycle->nombre_max_epargnes ?? 30;
                                $remaining = max(0, $max - $currentCount);
                                
                                return "Ce cycle a {$currentCount}/{$max} épargnes. Vous pouvez encore créer jusqu'à {$remaining} épargnes.";
                            })
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $cycleId = $get('cycle_id');
                                if ($cycleId && $state) {
                                    $cycle = Cycle::find($cycleId);
                                    $currentCount = $cycle->epargnes()->count();
                                    $max = $cycle->nombre_max_epargnes ?? 30;
                                    $remaining = max(0, $max - $currentCount);
                                    
                                    if ($state > $remaining) {
                                        Notification::make()
                                            ->title('Limite dépassée')
                                            ->body("Vous ne pouvez créer que {$remaining} épargnes supplémentaires.")
                                            ->warning()
                                            ->send();
                                        $set('nombre_epargnes', $remaining);
                                    }
                                }
                            }),
                    ])
                    ->columns(2),
                
                Section::make('Détails du Cycle')
                    ->schema([
                        Select::make('cycle_id')
                            ->label('Cycle')
                            ->options(function ($get) {
                                $typeEpargne = $get('type_epargne');
                                $devise = $get('devise');
                                
                                if (!$devise) return [];
                                
                                if ($typeEpargne === 'individuel') {
                                    $clientId = $get('client_id');
                                    if (!$clientId) return [];
                                    
                                    return Cycle::where('client_id', $clientId)
                                        ->where('devise', $devise)
                                        ->get()
                                        ->mapWithKeys(function ($cycle) {
                                            $statut = $cycle->statut === 'ouvert' ? ' (Ouvert)' : ' (Clôturé)';
                                            $currentCount = $cycle->epargnes()->count();
                                            $max = $cycle->nombre_max_epargnes ?? 30;
                                            $remaining = max(0, $max - $currentCount);
                                            
                                            return [
                                                $cycle->id => sprintf(
                                                    "%s - %s (%s/%s restants)%s",
                                                    $cycle->numero_cycle,
                                                    $cycle->client_nom,
                                                    $remaining,
                                                    $max,
                                                    $statut
                                                )
                                            ];
                                        })
                                        ->toArray();
                                    
                                } elseif ($typeEpargne === 'groupe_solidaire') {
                                    $groupeId = $get('groupe_solidaire_id');
                                    if (!$groupeId) return [];
                                    
                                    return Cycle::where('groupe_solidaire_id', $groupeId)
                                        ->where('devise', $devise)
                                        ->get()
                                        ->mapWithKeys(function ($cycle) {
                                            $statut = $cycle->statut === 'ouvert' ? ' (Ouvert)' : ' (Clôturé)';
                                            $currentCount = $cycle->epargnes()->count();
                                            $max = $cycle->nombre_max_epargnes ?? 30;
                                            $remaining = max(0, $max - $currentCount);
                                            
                                            return [
                                                $cycle->id => sprintf(
                                                    "%s - %s (%s/%s restants)%s",
                                                    $cycle->numero_cycle,
                                                    $cycle->client_nom,
                                                    $remaining,
                                                    $max,
                                                    $statut
                                                )
                                            ];
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
                                        $nombreEpargnes = $get('nombre_epargnes') ?? 1;
                                        
                                        if ($currentCount >= $max) {
                                            Notification::make()
                                                ->title('Cycle complet')
                                                ->body("Ce cycle a déjà {$max} épargnes. Veuillez sélectionner un autre cycle ou en créer un nouveau.")
                                                ->danger()
                                                ->persistent()
                                                ->send();
                                        } elseif (($currentCount + $nombreEpargnes) > $max) {
                                            $remaining = $max - $currentCount;
                                            Notification::make()
                                                ->title('Limite insuffisante')
                                                ->body("Ce cycle ne peut accepter que {$remaining} épargnes supplémentaires, mais vous voulez en créer {$nombreEpargnes}.")
                                                ->warning()
                                                ->persistent()
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
                            ->helperText('Ce montant correspond au solde initial du cycle sélectionné'),
                    ])
                    ->columns(2),
                
                Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
                
                Hidden::make('agent_nom')
                    ->default(fn () => Auth::user()->name ?? 'Agent'),
                
                Hidden::make('date_apport')
                    ->default(now()),
                
                Hidden::make('statut')
                    ->default('en_attente_dispatch'),
                
                Hidden::make('premiere_mise')
                    ->default(false),
            ])
            ->action(function (array $data) {
                $cycle = Cycle::find($data['cycle_id']);
                if (!$cycle) {
                    Notification::make()
                        ->title('Erreur')
                        ->body('Cycle non trouvé.')
                        ->danger()
                        ->send();
                    return;
                }
                
                $currentCount = $cycle->epargnes()->count();
                $max = $cycle->nombre_max_epargnes ?? 30;
                $nombreEpargnes = $data['nombre_epargnes'] ?? 1;
                
                // Vérifier la limite
                if ($currentCount >= $max) {
                    Notification::make()
                        ->title('Limite atteinte')
                        ->body("Ce cycle a déjà atteint la limite de {$max} épargnes. Veuillez créer un nouveau cycle.")
                        ->danger()
                        ->send();
                    return;
                }
                
                $remaining = $max - $currentCount;
                
                if ($nombreEpargnes > $remaining) {
                    Notification::make()
                        ->title('Limite dépassée')
                        ->body("Vous ne pouvez créer que {$remaining} épargnes supplémentaires. Ce cycle atteindra sa limite de {$max}.")
                        ->warning()
                        ->send();
                    $nombreEpargnes = $remaining;
                }
                
                $created = 0;
                $failed = 0;
                
                if ($data['type_epargne'] === 'individuel') {
                    $clientId = $data['client_id'];
                    $client = Client::find($clientId);
                    
                    for ($i = 1; $i <= $nombreEpargnes; $i++) {
                        try {
                            Epargne::create([
                                'type_epargne' => 'individuel',
                                'cycle_id' => $cycle->id,
                                'client_id' => $clientId,
                                'groupe_solidaire_id' => null,
                                'montant' => $data['montant'],
                                'devise' => $cycle->devise,
                                'date_apport' => $data['date_apport'] ?? now(),
                                'statut' => 'en_attente_dispatch',
                                'user_id' => Auth::id(),
                                'client_nom' => $client ? trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom) : 'Inconnu',
                                'agent_nom' => Auth::user()->name,
                                'premiere_mise' => false,
                            ]);
                            
                            $created++;
                        } catch (\Exception $e) {
                            $failed++;
                            Log::error("Erreur création épargne {$i} pour client {$clientId}: " . $e->getMessage());
                        }
                    }
                } elseif ($data['type_epargne'] === 'groupe_solidaire') {
                    try {
                        $groupe = GroupeSolidaire::find($data['groupe_solidaire_id']);
                        
                        Epargne::create([
                            'type_epargne' => 'groupe_solidaire',
                            'cycle_id' => $cycle->id,
                            'client_id' => null,
                            'groupe_solidaire_id' => $data['groupe_solidaire_id'],
                            'montant' => $data['montant'],
                            'devise' => $cycle->devise,
                            'date_apport' => $data['date_apport'] ?? now(),
                            'statut' => 'en_attente_dispatch',
                            'user_id' => Auth::id(),
                            'client_nom' => $groupe ? $groupe->nom_groupe : 'Groupe Inconnu',
                            'agent_nom' => Auth::user()->name,
                            'premiere_mise' => false,
                        ]);
                        
                        $created++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error("Erreur création épargne groupe: " . $e->getMessage());
                    }
                }
                
                // Vérifier si le cycle doit être clôturé
                $cycle->refresh();
                $newCount = $cycle->epargnes()->count();
                
                if ($newCount >= $max) {
                    $cycle->update(['statut' => 'clôturé']);
                    Notification::make()
                        ->title('Cycle clôturé')
                        ->body("Le cycle a atteint la limite de {$max} épargnes et a été automatiquement clôturé.")
                        ->success()
                        ->send();
                }
                
                // Notification de résultat
                if ($created > 0) {
                    Notification::make()
                        ->title('Épargnes créées avec succès')
                        ->body("{$created} épargnes ont été créées dans le cycle {$cycle->numero_cycle}.")
                        ->success()
                        ->send();
                }
                
                if ($failed > 0) {
                    Notification::make()
                        ->title('Certaines épargnes ont échoué')
                        ->body("{$failed} épargnes n'ont pas pu être créées.")
                        ->warning()
                        ->send();
                }
            });
    }
    
    /**
     * Trouver un cycle pour un client individuel
     */
    private static function trouverCycleClient($set, $get, int $clientId): void
    {
        $devise = $get('devise');
        
        if (empty($devise)) {
            // Essayer avec les deux devises
            $devises = ['USD', 'CDF'];
            foreach ($devises as $devise) {
                $cycle = Cycle::where('client_id', $clientId)
                    ->where('devise', $devise)
                    ->latest('id')
                    ->first();
                
                if ($cycle) {
                    $set('devise', $cycle->devise);
                    $set('cycle_id', $cycle->id);
                    $set('montant', $cycle->solde_initial);
                    
                    // Vérifier la limite
                    $currentCount = $cycle->epargnes()->count();
                    $max = $cycle->nombre_max_epargnes ?? 30;
                    if ($currentCount >= $max) {
                        Notification::make()
                            ->title('Cycle complet')
                            ->body("Ce cycle a déjà {$max} épargnes.")
                            ->danger()
                            ->send();
                    }
                    
                    return;
                }
            }
            
            $set('cycle_id', null);
            $set('montant', null);
            
        } else {
            $cycle = Cycle::where('client_id', $clientId)
                ->where('devise', $devise)
                ->latest('id')
                ->first();
            
            if ($cycle) {
                $set('cycle_id', $cycle->id);
                $set('montant', $cycle->solde_initial);
                
                // Vérifier la limite
                $currentCount = $cycle->epargnes()->count();
                $max = $cycle->nombre_max_epargnes ?? 30;
                if ($currentCount >= $max) {
                    Notification::make()
                        ->title('Cycle complet')
                        ->body("Ce cycle a déjà {$max} épargnes.")
                        ->danger()
                        ->send();
                }
            } else {
                $set('cycle_id', null);
                $set('montant', null);
            }
        }
    }
    
    /**
     * Trouver un cycle pour un groupe
     */
    private static function trouverCycleGroupe($set, $get, int $groupeId): void
    {
        $devise = $get('devise');
        
        if (empty($devise)) {
            // Essayer avec les deux devises
            $devises = ['USD', 'CDF'];
            foreach ($devises as $devise) {
                $cycle = Cycle::where('groupe_solidaire_id', $groupeId)
                    ->where('devise', $devise)
                    ->latest('id')
                    ->first();
                
                if ($cycle) {
                    $set('devise', $cycle->devise);
                    $set('cycle_id', $cycle->id);
                    $set('montant', $cycle->solde_initial);
                    
                    // Vérifier la limite
                    $currentCount = $cycle->epargnes()->count();
                    $max = $cycle->nombre_max_epargnes ?? 30;
                    if ($currentCount >= $max) {
                        Notification::make()
                            ->title('Cycle complet')
                            ->body("Ce cycle a déjà {$max} épargnes.")
                            ->danger()
                            ->send();
                    }
                    
                    return;
                }
            }
            
            $set('cycle_id', null);
            $set('montant', null);
            
        } else {
            $cycle = Cycle::where('groupe_solidaire_id', $groupeId)
                ->where('devise', $devise)
                ->latest('id')
                ->first();
            
            if ($cycle) {
                $set('cycle_id', $cycle->id);
                $set('montant', $cycle->solde_initial);
                
                // Vérifier la limite
                $currentCount = $cycle->epargnes()->count();
                $max = $cycle->nombre_max_epargnes ?? 30;
                if ($currentCount >= $max) {
                    Notification::make()
                        ->title('Cycle complet')
                        ->body("Ce cycle a déjà {$max} épargnes.")
                        ->danger()
                        ->send();
                }
            } else {
                $set('cycle_id', null);
                $set('montant', null);
            }
        }
    }
}