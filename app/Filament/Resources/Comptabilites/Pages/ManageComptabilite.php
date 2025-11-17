<?php
// app/Filament/Resources/ComptabiliteResource/Pages/ManageComptabilite.php

namespace App\Filament\Resources\ComptabiliteResource\Pages;

use App\Filament\Resources\Comptabilites\ComptabiliteResource;
use App\Models\Caisse;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Services\ComptabilityService;
// use Filament\Actions\Action;
// use Filament\Forms\Components\Hidden;
// use Filament\Forms\Components\Select;
// use Filament\Forms\Components\Textarea;
// use Filament\Forms\Components\TextInput;
// use Filament\Notifications\Notification;
// use Filament\Resources\Pages\ManageRecords;
// use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageComptabilite extends ManageRecords
{
    protected static string $resource = ComptabiliteResource::class;

    protected function getHeaderActions(): array
    {
        return [

//             // Dans getHeaderActions() - Ajoutez temporairement
// Action::make('ajuster_force_compte_transit')
//     ->label('🔧 Ajuster Forcément Compte Transit à 630 USD')
//     ->color('warning')
//     ->icon('heroicon-o-adjustments-horizontal')
//     ->requiresConfirmation()
//     ->modalHeading('Ajustement Forcé du Compte Transit')
//     ->modalDescription('Cette action va forcer le compte transit à avoir exactement 630 USD. Cela va créer des écritures de régularisation. Continuer ?')
//     ->action(function () {
//         try {
//             $service = app(ComptabilityService::class);
//             $result = $service->forcerExactement630USD();
            
//             Notification::make()
//                 ->title('Ajustement forcé réussi')
//                 ->body(
//                     "Compte transit ajusté avec succès!\n\n" .
//                     "Ancien solde: {$result['ancien_solde']} USD\n" .
//                     "Nouveau solde: {$result['nouveau_solde']} USD\n" .
//                     "Montant forcé: {$result['montant_force']} USD\n" .
//                     "Référence: {$result['reference']}"
//                 )
//                 ->success()
//                 ->send();
                
//         } catch (\Exception $e) {
//             Notification::make()
//                 ->title('Erreur d\'ajustement')
//                 ->body("Erreur: " . $e->getMessage())
//                 ->danger()
//                 ->send();
//         }
//     }),
            // 1. Distribution aux Caisses (GRANDE ET PETITE)
            Action::make('distribuer_caisses')
                ->label('Distribution aux Caisses')
                ->icon('heroicon-o-share')
                ->color('success')
                ->schema([
                    Select::make('devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required()
                        ->default('USD')
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                // Mettre à jour les informations des caisses en temps réel
                                self::mettreAJourInfosCaisses($set, $state);
                            }
                        }),

                    // Affichage du solde de la petite caisse en temps réel
                    Section::make('Solde Petite Caisse')
                        ->schema([
                            TextInput::make('solde_petite_caisse_live')
                                ->label('Solde Actuel Petite Caisse')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                                ->extraAttributes(['class' => 'bg-blue-50 border-blue-200 font-bold']),
                        ])
                        ->visible(fn ($get) => !empty($get('devise'))),

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
                            
                            // Mettre à jour le total
                            self::mettreAJourTotal($set, $get);
                        }),

                    TextInput::make('montant_grande_caisse_max')
                        ->label('Plafond restant Grande Caisse')
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix(function ($get) {
                            return $get('devise');
                        })
                        ->extraAttributes(['class' => 'bg-blue-50 border-blue-200']),

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
                        ->afterStateUpdated(function ($set, $state, $get) {
                            if ($state) {
                                $caisse = Caisse::find($state);
                                if ($caisse) {
                                    $plafondRestant = $caisse->plafond - $caisse->solde;
                                    $set('montant_petite_caisse_max', $plafondRestant);
                                    $set('montant_petite_caisse_info', "Plafond: {$caisse->plafond} {$caisse->devise}, Solde actuel: {$caisse->solde} {$caisse->devise}");
                                    
                                    // Mettre à jour le solde en temps réel
                                    $set('solde_petite_caisse_live', number_format($caisse->solde, 2) . ' ' . $caisse->devise);
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
                            
                            // Mettre à jour le total
                            self::mettreAJourTotal($set, $get);
                        }),

                    TextInput::make('montant_petite_caisse_max')
                        ->label('Plafond restant Petite Caisse')
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix(function ($get) {
                            return $get('devise');
                        })
                        ->extraAttributes(['class' => 'bg-blue-50 border-blue-200']),

                    // Total de la distribution
                    TextInput::make('total_distribution')
                        ->label('Total Distribution')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00 USD')
                        ->extraAttributes(['class' => 'bg-green-50 border-green-200 font-bold']),

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

            // 2. Opérations Comptables (bouton unique)
            Action::make('operations_comptables')
                ->label('Opérations Comptables')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->schema([
                    Select::make('type_operation')
                        ->label('Type d\'Opération')
                        ->options([
                            'paiement_salaire' => 'Paiement Salaire/Charges',
                            'depense_diverse' => 'Dépenses Diverses',
                        ])
                        ->required()
                        ->default('paiement_salaire')
                        ->live()
                        ->afterStateUpdated(function ($set, $get) {
                            $set('compte_numero', null);
                            $set('compte_id', null);
                            $set('petite_caisse_id', null);
                            $set('devise', 'USD');
                            
                            // Mettre à jour le solde si dépense diverse
                            if ($get('type_operation') === 'depense_diverse' && $get('devise_depense')) {
                                self::mettreAJourSoldePetiteCaisse($set, $get('devise_depense'));
                            }
                        }),

                    // Section pour Paiement Salaire/Charges
                    Section::make('Informations Paiement Salaire')
                        ->schema([
                            TextInput::make('compte_numero')
                                ->label('Numéro de Compte à Créditer')
                                ->required(fn ($get) => $get('type_operation') === 'paiement_salaire')
                                ->placeholder('Ex: C0001 ou GS00001')
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        // Rechercher le compte
                                        $compte = \App\Models\Compte::where('numero_compte', $state)->first();
                                        if ($compte) {
                                            $set('solde_compte_display', number_format($compte->solde, 2) . ' ' . $compte->devise);
                                            $set('compte_id', $compte->id);
                                            $set('devise', $compte->devise);
                                            $set('nom_titulaire', $compte->nom_complet ?? $compte->nom);
                                        } else {
                                            $set('solde_compte_display', 'Compte non trouvé');
                                            $set('compte_id', null);
                                            $set('nom_titulaire', '');
                                        }
                                    }
                                })
                                ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),
                            
                            TextInput::make('nom_titulaire')
                                ->label('Nom du Titulaire')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('')
                                ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),
                            
                            TextInput::make('solde_compte_display')
                                ->label('Solde Actuel du Compte')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                                ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),
                            
                            Select::make('type_charge')
                                ->label('Type de Charge')
                                ->options([
                                    'salaire' => 'Salaire',
                                    'transport' => 'Frais de Transport', 
                                    'communication' => 'Frais de Communication',
                                    'prime' => 'Prime',
                                    'autres' => 'Autres Charges',
                                ])
                                ->required(fn ($get) => $get('type_operation') === 'paiement_salaire')
                                ->default('salaire')
                                ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),
                            
                            TextInput::make('periode')
                                ->label('Période')
                                ->required(fn ($get) => $get('type_operation') === 'paiement_salaire')
                                ->placeholder('Ex: Novembre 2024')
                                ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),
                        ])
                        ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),

                    // Section pour Dépenses Diverses avec solde en temps réel
                    Section::make('Informations Dépense')
                        ->schema([
                            Select::make('type_depense')
                                ->label('Type de Dépense')
                                ->options([
                                    'frais_bureau' => 'Frais de Bureau',
                                    'transport' => 'Transport',
                                    'communication' => 'Communication',
                                    'entretien' => 'Entretien',
                                    'fournitures' => 'Fournitures',
                                    'autres' => 'Autres Dépenses',
                                ])
                                ->required(fn ($get) => $get('type_operation') === 'depense_diverse')
                                ->default('frais_bureau')
                                ->visible(fn ($get) => $get('type_operation') === 'depense_diverse'),
                            
                            Select::make('devise_depense')
                                ->label('Devise')
                                ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                                ->required(fn ($get) => $get('type_operation') === 'depense_diverse')
                                ->default('USD')
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        self::mettreAJourSoldePetiteCaisse($set, $state);
                                    }
                                })
                                ->visible(fn ($get) => $get('type_operation') === 'depense_diverse'),
                            
                            // Affichage du solde en temps réel
                            TextInput::make('solde_petite_caisse_temps_reel')
                                ->label('Solde Actuel Petite Caisse')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                                ->extraAttributes(['class' => 'bg-yellow-50 border-yellow-200 font-bold'])
                                ->visible(fn ($get) => $get('type_operation') === 'depense_diverse'),
                        ])
                        ->visible(fn ($get) => $get('type_operation') === 'depense_diverse'),

                    // Champs communs aux deux opérations
                    TextInput::make('montant')
                        ->label(fn ($get) => $get('type_operation') === 'paiement_salaire' ? 'Montant à Créditer' : 'Montant')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->suffix(function ($get) {
                            if ($get('type_operation') === 'paiement_salaire') {
                                return $get('devise') ?? 'USD';
                            } else {
                                return $get('devise_depense') ?? 'USD';
                            }
                        })
                        ->rules([
                            function ($get) {
                                return function ($attribute, $value, $fail) use ($get) {
                                    if ($get('type_operation') === 'depense_diverse') {
                                        $petiteCaisseId = $get('petite_caisse_id');
                                        if ($petiteCaisseId) {
                                            $petiteCaisse = Caisse::find($petiteCaisseId);
                                            if ($petiteCaisse && $value > $petiteCaisse->solde) {
                                                $fail("Solde insuffisant dans la petite caisse. Maximum: " . number_format($petiteCaisse->solde, 2) . " {$petiteCaisse->devise}");
                                            }
                                        }
                                    }
                                };
                            }
                        ]),
                    
                    TextInput::make('beneficiaire')
                        ->label('Bénéficiaire')
                        ->required()
                        ->placeholder('Nom du bénéficiaire'),
                    
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->placeholder(fn ($get) => $get('type_operation') === 'paiement_salaire' 
                            ? 'Description du paiement' 
                            : 'Description de la dépense'),

                    Hidden::make('compte_id'),
                    Hidden::make('devise'),
                    Hidden::make('petite_caisse_id'),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            $comptabilityService = app(ComptabilityService::class);
                            
                            if ($data['type_operation'] === 'paiement_salaire') {
                                // Logique pour Paiement Salaire/Charges
                                $compte = \App\Models\Compte::find($data['compte_id']);
                                
                                if (!$compte) {
                                    throw new \Exception('Compte non trouvé');
                                }
                                
                                // CRÉDITER le compte (DÉPÔT)
                                $ancienSolde = $compte->solde;
                                $compte->solde += $data['montant'];
                                $compte->save();
                                
                                // Enregistrer le mouvement (type DÉPÔT)
                                $mouvement = Mouvement::create([
                                    'compte_id' => $compte->id,
                                    'type' => 'depot',
                                    'type_mouvement' => 'paiement_salaire_charge',
                                    'montant' => $data['montant'],
                                    'solde_avant' => $ancienSolde,
                                    'solde_apres' => $compte->solde,
                                    'description' => $data['description'] . " - " . $data['type_charge'],
                                    'nom_deposant' => $data['beneficiaire'],
                                    'devise' => $data['devise'],
                                    'operateur_id' => Auth::id(),
                                    'numero_compte' => $compte->numero_compte,
                                    'client_nom' => $data['beneficiaire'],
                                    'date_mouvement' => now()
                                ]);
                                
                                // Enregistrer l'écriture comptable
                                $comptabilityService->enregistrerPaiementSalaireCharge(
                                    $mouvement,
                                    $compte,
                                    $data['type_charge'],
                                    $data['description'],
                                    $data['beneficiaire']
                                );
                                
                                Notification::make()
                                    ->title('Paiement enregistré')
                                    ->body("Paiement de {$data['montant']} {$data['devise']} crédité sur le compte {$compte->numero_compte}. Nouveau solde: {$compte->solde} {$compte->devise}")
                                    ->success()
                                    ->send();
                                
                            } else {
                                // Logique pour Dépenses Diverses
                                $petiteCaisse = Caisse::find($data['petite_caisse_id']);
                                
                                if (!$petiteCaisse) {
                                    throw new \Exception('Petite caisse non trouvée');
                                }
                                
                                if ($data['montant'] > $petiteCaisse->solde) {
                                    throw new \Exception('Solde insuffisant dans la petite caisse');
                                }
                                
                                // Débiter la petite caisse
                                $ancienSolde = $petiteCaisse->solde;
                                $petiteCaisse->solde -= $data['montant'];
                                $petiteCaisse->save();
                                
                                // Enregistrer le mouvement
                                $mouvement = Mouvement::create([
                                    'caisse_id' => $petiteCaisse->id,
                                    'type' => 'retrait',
                                    'type_mouvement' => 'depense_diverse_comptabilite',
                                    'montant' => $data['montant'],
                                    'solde_avant' => $ancienSolde,
                                    'solde_apres' => $petiteCaisse->solde,
                                    'description' => $data['description'] . " - " . $data['type_depense'],
                                    'nom_deposant' => $data['beneficiaire'],
                                    'devise' => $data['devise_depense'],
                                    'operateur_id' => Auth::id(),
                                    'numero_compte' => 'DEPENSE-DIVERSE',
                                    'client_nom' => $data['beneficiaire'],
                                    'date_mouvement' => now()
                                ]);
                                
                                // Enregistrer l'écriture comptable
                                $comptabilityService->enregistrerDepenseDiverse(
                                    $petiteCaisse->id,
                                    $data['montant'],
                                    $data['devise_depense'],
                                    self::getCompteChargeDepense($data['type_depense']),
                                    $data['description'],
                                    $data['beneficiaire']
                                );
                                
                                Notification::make()
                                    ->title('Dépense enregistrée')
                                    ->body("Dépense de {$data['montant']} {$data['devise_depense']} effectuée depuis la petite caisse")
                                    ->success()
                                    ->send();
                            }
                        });
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // 3. Délaistage Petite Caisse avec solde en temps réel
            Action::make('gestion_depenses')
                ->label('Délaistage Petite Caisse')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->schema([
                    Select::make('devise_delaisage')
                        ->label('Devise du Délaistage')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required()
                        ->default('USD')
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                self::mettreAJourSoldePetiteCaisseDelaisage($set, $state);
                            }
                        }),
                    
                    // Affichage du solde en temps réel
                    TextInput::make('solde_petite_caisse_delaisage')
                        ->label('Solde à Transférer')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00 USD')
                        ->extraAttributes(['class' => 'bg-orange-50 border-orange-200 font-bold']),
                        
                    Textarea::make('motif_delaisage')
                        ->label('Motif du Délaistage')
                        ->required()
                        ->placeholder('Ex: Délaistage quotidien de la petite caisse')
                        ->default('Délaistage automatique de la petite caisse vers comptabilité'),
                        
                    Hidden::make('petite_caisse_id'),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            $comptabilityService = app(ComptabilityService::class);
                            
                            $petiteCaisse = Caisse::find($data['petite_caisse_id']);
                            
                            if (!$petiteCaisse) {
                                throw new \Exception('Petite caisse non trouvée');
                            }
                            
                            if ($petiteCaisse->solde <= 0) {
                                throw new \Exception('Aucun solde à transférer');
                            }
                            
                            $montantTransfert = $petiteCaisse->solde;
                            $reference = 'DELAISAGE-PETITE-' . now()->format('Ymd-His');
                            
                            // Enregistrer le mouvement de sortie
                            Mouvement::create([
                                'caisse_id' => $petiteCaisse->id,
                                'type' => 'retrait',
                                'type_mouvement' => 'delaisage_comptabilite',
                                'montant' => $montantTransfert,
                                'solde_avant' => $petiteCaisse->solde,
                                'solde_apres' => 0,
                                'description' => $data['motif_delaisage'] . " - Transfert vers comptabilité",
                                'nom_deposant' => 'Système Délaistage',
                                'devise' => $data['devise_delaisage'],
                                'operateur_id' => Auth::id(),
                                'numero_compte' => $petiteCaisse->type_caisse,
                                'client_nom' => 'Transfert comptabilité',
                                'date_mouvement' => now()
                            ]);
                            
                            // Réinitialiser le solde de la petite caisse
                            $petiteCaisse->solde = 0;
                            $petiteCaisse->save();
                            
                            // Générer l'écriture comptable
                            $comptabilityService->enregistrerDelaisagePetiteCaisse(
                                $montantTransfert, 
                                $data['devise_delaisage'], 
                                $reference, 
                                $data['motif_delaisage']
                            );
                            
                            Notification::make()
                                ->title('Délaistage réussi')
                                ->body("{$montantTransfert} {$data['devise_delaisage']} transférés depuis la petite caisse vers la comptabilité")
                                ->success()
                                ->send();
                        });
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur de délaistage')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Délaistage Petite Caisse')
                ->modalDescription('Êtes-vous sûr de vouloir transférer le solde de la petite caisse vers la comptabilité ?'),

            // 4. Retour aux Coffres
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
                        ->minValue(0.0)
                        ->step(0.01)
                        ->suffix(function ($get) {
                            return $get('devise_retour');
                        })
                        ->rules([
                            function ($get) {
                                return function ($attribute, $value, $fail) use ($get) {
                                    $soldeDisponible = app(ComptabilityService::class)
                                        ->getSoldeCompte('511100', $get('devise_retour'));
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

        //  etat_comptes

// Dans ManageComptabilite.php - Modifier l'action etat_comptes

Action::make('etat_comptes')
    ->label('État des Comptes')
    ->icon('heroicon-o-chart-bar')
    ->color('info')
    ->action(function () {
        try {
            $comptabilityService = app(ComptabilityService::class);
            
            // Récupérer l'état des comptes (maintenant avec soldes physiques pour caisses)
            $etatComptes = $comptabilityService->getEtatComptes();
            
            $message = "📊 **État des Comptes - Soldes Réels**\n\n";
            
            $message .= "**💵 COMPTE TRANSIT (Fonds disponibles):**\n";
            $message .= "USD: " . number_format($etatComptes['transit_usd'], 2) . " | ";
            $message .= "CDF: " . number_format($etatComptes['transit_cdf'], 2) . "\n\n";
            
            $message .= "**🏦 BANQUE:**\n";
            $message .= "USD: " . number_format($etatComptes['banque_usd'], 2) . " | ";
            $message .= "CDF: " . number_format($etatComptes['banque_cdf'], 2) . "\n\n";
            
            $message .= "**💰 COFFRE FORT:**\n";
            $message .= "USD: " . number_format($etatComptes['coffre_usd'], 2) . " | ";
            $message .= "CDF: " . number_format($etatComptes['coffre_cdf'], 2) . "\n\n";
            
            $message .= "**📦 GRANDE CAISSE (Solde Physique Réel):**\n";
            $message .= "USD: " . number_format($etatComptes['grande_caisse_usd'], 2) . " | ";
            $message .= "CDF: " . number_format($etatComptes['grande_caisse_cdf'], 2) . "\n\n";
            
            $message .= "**💼 PETITE CAISSE (Solde Physique Réel):**\n";
            $message .= "USD: " . number_format($etatComptes['petite_caisse_usd'], 2) . " | ";
            $message .= "CDF: " . number_format($etatComptes['petite_caisse_cdf'], 2) . "\n\n";
            
            $message .= "**💰💰 TOTAL GÉNÉRAL:**\n";
            $message .= "USD: **" . number_format($etatComptes['total_usd'], 2) . "** | ";
            $message .= "CDF: **" . number_format($etatComptes['total_cdf'], 2) . "**\n\n";
            
            // Vérifier la cohérence (optionnel)
            // $coherence = $comptabilityService->verifierCohérenceSoldes();
            // if (!$coherence['coherent']) {
            //     $message .= "⚠️ **NOTE:** Des écarts existent entre certains soldes comptables et physiques.\n";
            // } else {
            //     $message .= "✅ **COHÉRENCE:** Tous les soldes sont cohérents.\n";
            // }

            Notification::make()
                ->title('État des Comptes - Soldes Réels')
                ->body($message)
                ->info()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body("Erreur lors du calcul de l'état des comptes: " . $e->getMessage())
                ->danger()
                ->send();
        }
    }),
        ];
    }

    // Méthodes helper pour la mise à jour en temps réel
    private static function mettreAJourInfosCaisses($set, $devise)
    {
        self::mettreAJourSoldePetiteCaisseGenerique($set, $devise, 'solde_petite_caisse_live');
    }

    private static function mettreAJourSoldePetiteCaisse($set, $devise)
    {
        self::mettreAJourSoldePetiteCaisseGenerique($set, $devise, 'solde_petite_caisse_temps_reel');
    }

    private static function mettreAJourSoldePetiteCaisseDelaisage($set, $devise)
    {
        self::mettreAJourSoldePetiteCaisseGenerique($set, $devise, 'solde_petite_caisse_delaisage');
    }

    private static function mettreAJourSoldePetiteCaisseGenerique($set, $devise, $fieldName)
    {
        $petiteCaisse = Caisse::where('type_caisse', 'petite_caisse')
                              ->where('devise', $devise)
                              ->first();
        if ($petiteCaisse) {
            $set($fieldName, number_format($petiteCaisse->solde, 2) . ' ' . $devise);
            $set('petite_caisse_id', $petiteCaisse->id);
        } else {
            $set($fieldName, 'Aucune petite caisse trouvée');
            $set('petite_caisse_id', null);
        }
    }

    private static function mettreAJourTotal($set, $get)
    {
        $montantGrande = (float) ($get('montant_grande_caisse') ?? 0);
        $montantPetite = (float) ($get('montant_petite_caisse') ?? 0);
        $total = $montantGrande + $montantPetite;
        $devise = $get('devise') ?? 'USD';
        
        $set('total_distribution', number_format($total, 2) . ' ' . $devise);
    }

    private static function getCompteChargeDepense(string $typeDepense): string
    {
        return match($typeDepense) {
            'frais_bureau' => '613100',
            'transport' => '613200', 
            'communication' => '613300',
            'entretien' => '613400',
            'fournitures' => '613500',
            'salaire' => '661100', // Compte salaires
            default => '613600' // autres
        };
    }
}