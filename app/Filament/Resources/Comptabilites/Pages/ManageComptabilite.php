<?php
// app/Filament/Resources/ComptabiliteResource/Pages/ManageComptabilite.php

namespace App\Filament\Resources\ComptabiliteResource\Pages;

use App\Filament\Resources\Comptabilites\ComptabiliteResource;
use App\Models\Caisse;
use App\Models\JournalComptable;
use App\Services\ComptabilityService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;

class ManageComptabilite extends ManageRecords
{
    protected static string $resource = ComptabiliteResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // Dans ManageComptabilite.php - Ajouter dans getHeaderActions()

Action::make('retour_coffres')
    ->label('Retour aux Coffres')
    ->icon('heroicon-o-arrow-left-circle')
    ->color('primary')
    ->schema([
        Select::make('devise_retour')
            ->label('Devise du Retour')
            ->options(['USD' => 'USD', 'CDF' => 'CDF'])
            ->required()
            ->default('USD')
            ->live()
            ->afterStateUpdated(function ($set, $state) {
                if ($state) {
                    $soldeDisponible = app(ComptabilityService::class)
                        ->getSoldeCompte('511100', $state); // Compte transit
                    $set('solde_disponible_display', number_format($soldeDisponible, 2) . ' ' . $state);
                }
            }),
        
        TextInput::make('solde_disponible_display')
            ->label('Solde Disponible en Comptabilité')
            ->disabled()
            ->dehydrated(false)
            ->default('0.00 USD'),
            
        Select::make('coffre_destination_id')
            ->label('Coffre Destination')
            ->options(\App\Models\CashRegister::pluck('nom', 'id'))
            ->required()
            ->live()
            ->afterStateUpdated(function ($set, $state) {
                if ($state) {
                    $coffre = \App\Models\CashRegister::find($state);
                    if ($coffre) {
                        $set('solde_coffre_display', number_format($coffre->solde_actuel, 2) . ' ' . $coffre->devise);
                    }
                }
            }),
            
        TextInput::make('solde_coffre_display')
            ->label('Solde Actuel du Coffre')
            ->disabled()
            ->dehydrated(false)
            ->default('0.00 USD'),
            
        TextInput::make('montant_retour')
            ->label('Montant à Retourner')
            ->numeric()
            ->required()
            ->minValue(0.01)
            ->suffix(function ($get) {
                return $get('devise_retour');
            })
            ->rules([
                function ($get) {
                    return function ($attribute, $value, $fail) use ($get) {
                        $soldeDisponible = app(ComptabilityService::class)
                            ->getSoldeCompte('511100', $get('devise_retour'));
                        
                        if ($value > $soldeDisponible) {
                            $fail("Solde insuffisant en comptabilité. Maximum: " . number_format($soldeDisponible, 2));
                        }
                    };
                }
            ]),
            
        Textarea::make('motif_retour')
            ->label('Motif du Retour')
            ->required()
            ->placeholder('Ex: Retour aux coffres pour besoins opérationnels'),
    ])
    ->action(function (array $data) {
        try {
            DB::transaction(function () use ($data) {
                $comptabilityService = app(ComptabilityService::class);
                $coffreService = app(\App\Services\CoffreService::class);
                
                $reference = 'RETOUR-COFFRE-' . now()->format('Ymd-His');
                
                // 1. Créer le mouvement physique vers le coffre
                $mouvement = $coffreService->alimenterCoffre(
                    $data['coffre_destination_id'],
                    $data['montant_retour'],
                    'comptabilite',
                    $reference,
                    $data['devise_retour'],
                    $data['motif_retour']
                );
                
                // 2. Enregistrer l'écriture comptable
                $comptabilityService->enregistrerRetourVersCoffre(
                    $mouvement->id,
                    $reference
                );
                
                Notification::make()
                    ->title('Retour aux coffres réussi')
                    ->body("{$data['montant_retour']} {$data['devise_retour']} transférés vers le coffre")
                    ->success()
                    ->send();
            });
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de retour')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }),

            
            // 0. Initialiser les journaux (Action de débogage)
            Action::make('initialiser_journaux')
                ->label('Initialiser Journaux')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->action(function () {
                    try {
                        $comptabilityService = app(ComptabilityService::class);
                        
                        // Utiliser la réflexion pour appeler la méthode privée
                        $reflection = new \ReflectionClass($comptabilityService);
                        $method = $reflection->getMethod('initialiserJournaux');
                        $method->setAccessible(true);
                        $method->invoke($comptabilityService);
                        
                        $journaux = JournalComptable::all();
                        $message = "✅ **Journaux initialisés avec succès!**\n\n";
                        foreach ($journaux as $journal) {
                            $message .= "- {$journal->code_journal}: {$journal->libelle_journal}\n";
                        }

                        Notification::make()
                            ->title('Journaux initialisés')
                            ->body($message)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // 1. Voir l'état complet des comptes
            Action::make('etat_comptes')
                ->label('État des Comptes')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(function () {
                    try {
                        $comptabilityService = app(ComptabilityService::class);
                        
                        $etatComptes = $comptabilityService->getEtatComptes();

                        $message = "📊 **État des Comptes**\n\n";
                        $message .= "**Compte Transit (511100):**\n";
                        $message .= "USD: {$etatComptes['transit_usd']} | CDF: {$etatComptes['transit_cdf']}\n\n";
                        $message .= "**Coffre Fort (571200):**\n";
                        $message .= "USD: {$etatComptes['coffre_usd']} | CDF: {$etatComptes['coffre_cdf']}\n\n";
                        $message .= "**Banque (521100):**\n";
                        $message .= "USD: {$etatComptes['banque_usd']} | CDF: {$etatComptes['banque_cdf']}";

                        Notification::make()
                            ->title('État des Comptes')
                            ->body($message)
                            ->info()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

          // 2. Distribution aux Caisses
Action::make('distribuer_caisses')
    ->label('Distribution aux Caisses')
    ->icon('heroicon-o-share')
    ->color('success')
    ->schema([
        Select::make('devise')
            ->options(['USD' => 'USD', 'CDF' => 'CDF'])
            ->required()
            ->default('USD')
            ->reactive()
            ->afterStateUpdated(function ($set, $state) {
                // Récupérer les caisses disponibles pour la devise sélectionnée
                $caisses = Caisse::where('devise', $state)->get();
                
                $infoCaisses = "**Caisses disponibles en {$state}:**\n";
                foreach ($caisses as $caisse) {
                    $type = $caisse->type_caisse === 'petite_caisse' ? 'Petite Caisse' : 'Grande Caisse';
                    $plafondRestant = $caisse->plafond - $caisse->solde;
                    $infoCaisses .= "- {$caisse->nom} ({$type}): Solde: {$caisse->solde} {$caisse->devise}, Plafond: {$caisse->plafond} {$caisse->devise}, Restant: {$plafondRestant} {$caisse->devise}\n";
                }
                
                $set('info_caisses', $infoCaisses);
            }),
        
        TextInput::make('info_caisses')
            ->label('Caisses Disponibles')
            ->disabled()
            ->dehydrated(false)
            ->extraAttributes(['class' => 'bg-gray-50 border-gray-200'])
            ->columnSpanFull(),
        
        Select::make('grande_caisse_id')
            ->label('Grande Caisse')
            ->options(function ($get) {
                $devise = $get('devise');
                return $devise ? 
                    Caisse::where('devise', $devise)
                          ->where('type_caisse', 'grande_caisse')
                          ->pluck('nom', 'id')
                          ->toArray() : [];
            })
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($set, $state) {
                if ($state) {
                    $caisse = Caisse::find($state);
                    if ($caisse) {
                        $plafondRestant = $caisse->plafond - $caisse->solde;
                        $set('montant_grande_caisse_max', $plafondRestant);
                        $set('montant_grande_caisse_info', "Plafond: {$caisse->plafond} {$caisse->devise}, Solde actuel: {$caisse->solde} {$caisse->devise}");
                    }
                }
            }),
        
        TextInput::make('montant_grande_caisse')
            ->numeric()
            ->required()
            ->minValue(0)
            ->label('Montant Grande Caisse')
            ->suffix(function ($get) {
                return $get('devise');
            })
            ->reactive()
            ->afterStateUpdated(function ($set, $state, $get) {
                $max = $get('montant_grande_caisse_max');
                if ($state > $max) {
                    $set('montant_grande_caisse', $max);
                }
                
                // Calculer le nouveau solde
                $grandeCaisseId = $get('grande_caisse_id');
                if ($grandeCaisseId) {
                    $caisse = Caisse::find($grandeCaisseId);
                    if ($caisse) {
                        $nouveauSolde = $caisse->solde + $state;
                        $set('nouveau_solde_grande_caisse', $nouveauSolde);
                    }
                }
            }),
        
        TextInput::make('montant_grande_caisse_max')
            ->label('Plafond restant Grande Caisse')
            ->disabled()
            ->dehydrated(false)
            ->suffix(function ($get) {
                return $get('devise');
            })
            ->extraAttributes(['class' => 'bg-blue-50 border-blue-200']),
            
        TextInput::make('montant_grande_caisse_info')
            ->label('Informations Grande Caisse')
            ->disabled()
            ->dehydrated(false)
            ->extraAttributes(['class' => 'bg-gray-50 border-gray-200'])
            ->columnSpanFull(),
            
        TextInput::make('nouveau_solde_grande_caisse')
            ->label('Nouveau solde Grande Caisse')
            ->disabled()
            ->dehydrated(false)
            ->suffix(function ($get) {
                return $get('devise');
            })
            ->extraAttributes(['class' => 'bg-green-50 border-green-200']),
        
        Select::make('petite_caisse_id')
            ->label('Petite Caisse')
            ->options(function ($get) {
                $devise = $get('devise');
                return $devise ? 
                    Caisse::where('devise', $devise)
                          ->where('type_caisse', 'petite_caisse')
                          ->pluck('nom', 'id')
                          ->toArray() : [];
            })
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($set, $state) {
                if ($state) {
                    $caisse = Caisse::find($state);
                    if ($caisse) {
                        $plafondRestant = $caisse->plafond - $caisse->solde;
                        $set('montant_petite_caisse_max', $plafondRestant);
                        $set('montant_petite_caisse_info', "Plafond: {$caisse->plafond} {$caisse->devise}, Solde actuel: {$caisse->solde} {$caisse->devise}");
                    }
                }
            }),
        
        TextInput::make('montant_petite_caisse')
            ->numeric()
            ->required()
            ->minValue(0)
            ->label('Montant Petite Caisse')
            ->suffix(function ($get) {
                return $get('devise');
            })
            ->reactive()
            ->afterStateUpdated(function ($set, $state, $get) {
                $max = $get('montant_petite_caisse_max');
                if ($state > $max) {
                    $set('montant_petite_caisse', $max);
                }
                
                // Calculer le nouveau solde
                $petiteCaisseId = $get('petite_caisse_id');
                if ($petiteCaisseId) {
                    $caisse = Caisse::find($petiteCaisseId);
                    if ($caisse) {
                        $nouveauSolde = $caisse->solde + $state;
                        $set('nouveau_solde_petite_caisse', $nouveauSolde);
                    }
                }
            }),
        
        TextInput::make('montant_petite_caisse_max')
            ->label('Plafond restant Petite Caisse')
            ->disabled()
            ->dehydrated(false)
            ->suffix(function ($get) {
                return $get('devise');
            })
            ->extraAttributes(['class' => 'bg-blue-50 border-blue-200']),
            
        TextInput::make('montant_petite_caisse_info')
            ->label('Informations Petite Caisse')
            ->disabled()
            ->dehydrated(false)
            ->extraAttributes(['class' => 'bg-gray-50 border-gray-200'])
            ->columnSpanFull(),
            
        TextInput::make('nouveau_solde_petite_caisse')
            ->label('Nouveau solde Petite Caisse')
            ->disabled()
            ->dehydrated(false)
            ->suffix(function ($get) {
                return $get('devise');
            })
            ->extraAttributes(['class' => 'bg-green-50 border-green-200']),
        
        TextInput::make('reference')
            ->required()
            ->label('Référence')
            ->columnSpanFull(),
    ])
    ->action(function (array $data) {
        try {
            $comptabilityService = app(ComptabilityService::class);
            
            // Vérifier que les caisses sélectionnées existent
            $grandeCaisse = Caisse::find($data['grande_caisse_id']);
            $petiteCaisse = Caisse::find($data['petite_caisse_id']);
            
            if (!$grandeCaisse || !$petiteCaisse) {
                throw new \Exception("❌ Une ou plusieurs caisses sélectionnées n'existent pas.");
            }

            // VÉRIFICATION DES PLAFONDS AVEC LES DONNÉES RÉELLES
            $nouveauSoldeGrandeCaisse = $grandeCaisse->solde + $data['montant_grande_caisse'];
            $nouveauSoldePetiteCaisse = $petiteCaisse->solde + $data['montant_petite_caisse'];
            
            if ($nouveauSoldeGrandeCaisse > $grandeCaisse->plafond) {
                throw new \Exception("❌ Le plafond de la grande caisse '{$grandeCaisse->nom}' serait dépassé. \nPlafond: {$grandeCaisse->plafond} {$grandeCaisse->devise}, \nNouveau solde: {$nouveauSoldeGrandeCaisse} {$grandeCaisse->devise}");
            }
            
            if ($nouveauSoldePetiteCaisse > $petiteCaisse->plafond) {
                throw new \Exception("❌ Le plafond de la petite caisse '{$petiteCaisse->nom}' serait dépassé. \nPlafond: {$petiteCaisse->plafond} {$petiteCaisse->devise}, \nNouveau solde: {$nouveauSoldePetiteCaisse} {$petiteCaisse->devise}");
            }

            // Vérifier les fonds disponibles dans la trésorerie
            $totalDistribution = $data['montant_grande_caisse'] + $data['montant_petite_caisse'];
            $fondsDisponibles = $comptabilityService->getFondsDisponiblesTresorerie($data['devise']);
            
            if ($totalDistribution > $fondsDisponibles) {
                throw new \Exception("❌ Fonds insuffisants dans le compte de transit.\n\nDisponible: {$fondsDisponibles} {$data['devise']}\nDemandé: {$totalDistribution} {$data['devise']}\n\n💡 **Solution:** Transférez d'abord des fonds depuis le coffre vers la comptabilité.");
            }

            DB::transaction(function () use ($data, $comptabilityService, $grandeCaisse, $petiteCaisse) {
                $distributions = [
                    $data['grande_caisse_id'] => $data['montant_grande_caisse'],
                    $data['petite_caisse_id'] => $data['montant_petite_caisse'],
                ];

                // Distribution aux caisses avec écritures comptables
                $totalDistribue = $comptabilityService->distribuerAuxCaisses(
                    $distributions,
                    $data['reference'],
                    $data['devise']
                );

                // Récupérer les caisses mises à jour
                $grandeCaisse->refresh();
                $petiteCaisse->refresh();
                
                $message = "✅ **Distribution réussie!**\n\n";
                $message .= "**Total distribué:** {$totalDistribue} {$data['devise']}\n\n";
                $message .= "**Caisses alimentées:**\n";
                $message .= "- {$grandeCaisse->nom}: {$grandeCaisse->solde} {$grandeCaisse->devise}\n";
                $message .= "- {$petiteCaisse->nom}: {$petiteCaisse->solde} {$petiteCaisse->devise}";

                Notification::make()
                    ->title('Distribution terminée')
                    ->body($message)
                    ->success()
                    ->send();
            });

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de distribution')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }),

        ];
    }
}