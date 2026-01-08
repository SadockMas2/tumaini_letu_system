<?php

namespace App\Filament\Resources\ComptabiliteResource\Pages;

use App\Filament\Resources\Comptabilites\ComptabiliteResource;
use App\Models\Caisse;
use App\Models\EcritureComptable;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Services\ComptabilityService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageComptabilite extends ManageRecords
{
    protected static string $resource = ComptabiliteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Menu principal regroupant toutes les actions
            ActionGroup::make([
                // Sous-groupe pour les rapports
                ActionGroup::make([
                    Action::make('rapport_classe6_charges')
    ->label('Rapport Classe 6 (Charges)')
    ->icon('heroicon-o-exclamation-triangle')
    ->color('danger')
    ->schema([
        DatePicker::make('date_debut')
            ->label('Date de début')
            ->default(now()->startOfMonth())
            ->required(),
        DatePicker::make('date_fin')
            ->label('Date de fin')
            ->default(now()->endOfMonth())
            ->required(),
        Select::make('detail_niveau')
            ->label('Niveau de détail')
            ->options([
                'synthese' => 'Synthèse seulement',
                'par_compte' => 'Par compte',
                'complet' => 'Complet avec toutes les opérations'
            ])
            ->default('synthese'),
    ])
    ->action(function (array $data) {
        try {
            $comptabilityService = app(ComptabilityService::class);
            $rapport = $comptabilityService->rapportClasse6Charges(
                $data['date_debut'],
                $data['date_fin']
            );
            
            // Export HTML
            $html = view('pdf.rapport-classe6-charges', [
                'rapport' => $rapport,
                'detail_niveau' => $data['detail_niveau']
            ])->render();

            $filename = 'rapport-classe6-charges-' . $data['date_debut'] . '-a-' . $data['date_fin'] . '.html';
            
            return response()->streamDownload(function () use ($html) {
                echo $html;
            }, $filename);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body('Impossible de générer le rapport: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    })
    ->modalDescription('Générer un rapport détaillé des charges (Classe 6)'),
    
                    Action::make('rapport_instantanee_comptabilite')
                        ->label('Rapport Comptabilité Instantané')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->schema([
                            DatePicker::make('date_rapport')
                                ->label('Date du rapport')
                                ->default(now())
                                ->required(),
                            Toggle::make('inclure_details')
                                ->label('Inclure le détail des mouvements')
                                ->default(true),
                        ])
                        ->action(function (array $data) {
                            try {
                                $comptabilityService = app(ComptabilityService::class);
                                $rapport = $comptabilityService->rapportInstantaneeComptabilite($data['date_rapport']);
                                
                                // Export HTML
                                $html = view('pdf.rapport-comptabilite-instantanee', [
                                    'rapport' => $rapport,
                                    'inclure_details' => $data['inclure_details']
                                ])->render();

                                $filename = 'rapport-comptabilite-instantane-' . now()->format('Y-m-d-H-i') . '.html';
                                
                                return response()->streamDownload(function () use ($html) {
                                    echo $html;
                                }, $filename);
                                
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body('Impossible de générer le rapport: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('rapport_periode_comptabilite')
                        ->label('Rapport Comptabilité Période')
                        ->icon('heroicon-o-calendar')
                        ->color('warning')
                        ->schema([
                            DatePicker::make('date_debut')
                                ->label('Date de début')
                                ->default(now()->subDays(7))
                                ->required(),
                            DatePicker::make('date_fin')
                                ->label('Date de fin')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            try {
                                $comptabilityService = app(ComptabilityService::class);
                                $rapport = $comptabilityService->rapportPeriodeComptabilite($data['date_debut'], $data['date_fin']);
                                
                                // Export HTML
                                $html = view('pdf.rapport-comptabilite-periode', [
                                    'rapport' => $rapport
                                ])->render();

                                $filename = 'rapport-comptabilite-periode-' . $data['date_debut'] . '-a-' . $data['date_fin'] . '.html';
                                
                                return response()->streamDownload(function () use ($html) {
                                    echo $html;
                                }, $filename);
                                
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body('Impossible de générer le rapport: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->label('Rapports')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray'),

                // Sous-groupe pour les opérations de caisse
                ActionGroup::make([
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

                    Action::make('operations_comptables')
    ->label('Opérations Comptables')
    ->icon('heroicon-o-cog-6-tooth')
    ->color('primary')
    ->schema([
        Select::make('type_operation')
            ->label('Type d\'Opération')
            ->options([
                'paiement_salaire' => 'Paiement Salaire/Charges',
                'virement' => 'Virement vers Compte', // Nouvelle option identique
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
        Section::make('Informations Paiement')
            ->schema([
                TextInput::make('compte_numero')
                    ->label('Numéro de Compte à Créditer')
                    ->required(fn ($get) => in_array($get('type_operation'), ['paiement_salaire', 'virement']))
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
                    ->visible(fn ($get) => in_array($get('type_operation'), ['paiement_salaire', 'virement'])),
                
                TextInput::make('nom_titulaire')
                    ->label('Nom du Titulaire')
                    ->disabled()
                    ->dehydrated(false)
                    ->default('')
                    ->visible(fn ($get) => in_array($get('type_operation'), ['paiement_salaire', 'virement'])),
                
                TextInput::make('solde_compte_display')
                    ->label('Solde Actuel du Compte')
                    ->disabled()
                    ->dehydrated(false)
                    ->default('0.00 USD')
                    ->visible(fn ($get) => in_array($get('type_operation'), ['paiement_salaire', 'virement'])),
                
                // Pour paiement salaire uniquement
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
                
                // Pour virement uniquement
                Select::make('motif_virement')
                    ->label('Motif du Virement')
                    ->options([
                        'facture_client' => 'Paiement Facture Client',
                        'remboursement' => 'Remboursement',
                        'avance' => 'Avance',
                        'commission' => 'Commission',
                        'transfert' => 'Transfert de fonds',
                        'autres' => 'Autres',
                    ])
                    ->required(fn ($get) => $get('type_operation') === 'virement')
                    ->default('transfert')
                    ->visible(fn ($get) => $get('type_operation') === 'virement'),
                
                // Pour paiement salaire uniquement
                TextInput::make('periode')
                    ->label('Période')
                    ->required(fn ($get) => $get('type_operation') === 'paiement_salaire')
                    ->placeholder('Ex: Novembre 2024')
                    ->visible(fn ($get) => $get('type_operation') === 'paiement_salaire'),
            ])
            ->visible(fn ($get) => in_array($get('type_operation'), ['paiement_salaire', 'virement'])),

        // Section pour Dépenses Diverses
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

        // Champs communs aux trois opérations
        TextInput::make('montant')
            ->label(fn ($get) => match($get('type_operation')) {
                'paiement_salaire' => 'Montant à Créditer',
                'virement' => 'Montant du Virement',
                'depense_diverse' => 'Montant',
                default => 'Montant'
            })
            ->numeric()
            ->required()
            ->rules([
                'required',
                'numeric',
                'min:0.01',
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
            ])
            ->suffix(function ($get) {
                return match($get('type_operation')) {
                    'paiement_salaire', 'virement' => $get('devise') ?? 'USD',
                    'depense_diverse' => $get('devise_depense') ?? 'USD',
                    default => 'USD'
                };
            }),
        
        TextInput::make('beneficiaire')
            ->label('Bénéficiaire')
            ->required()
            ->placeholder('Nom du bénéficiaire'),
        
        Textarea::make('description')
            ->label('Description')
            ->required()
            ->placeholder(fn ($get) => match($get('type_operation')) {
                'paiement_salaire' => 'Description du paiement',
                'virement' => 'Description du virement',
                'depense_diverse' => 'Description de la dépense',
                default => 'Description'
            }),

        Hidden::make('compte_id'),
        Hidden::make('devise'),
        Hidden::make('petite_caisse_id'),
    ])
    
   ->action(function (array $data) {
    try {
        Log::info('Données reçues:', $data);
        Log::info('Type opération:', ['type' => $data['type_operation']]);
        Log::info('Petite caisse ID:', ['id' => $data['petite_caisse_id'] ?? 'non défini']);
        
        DB::transaction(function () use ($data) {
            $comptabilityService = app(ComptabilityService::class);
            
            // CORRECTION : Vérifier d'abord le type d'opération
            if ($data['type_operation'] === 'depense_diverse') {
                // Logique pour Dépenses Diverses
                $petiteCaisse = Caisse::find($data['petite_caisse_id']);
                
                if (!$petiteCaisse) {
                    throw new \Exception('Petite caisse non trouvée');
                }
                
                if ($data['montant'] > $petiteCaisse->solde) {
                    throw new \Exception('Solde insuffisant dans la petite caisse');
                }
                
                // Débiter la petite caisse (physiquement)
                $ancienSolde = $petiteCaisse->solde;
                $petiteCaisse->solde -= $data['montant'];
                $petiteCaisse->save();
                
                // Enregistrer le mouvement physique
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
                
                // Créer l'écriture comptable correspondante
                $journal = JournalComptable::where('type_journal', 'achats')->first();
                $compteCharge = self::getCompteChargeDepense($data['type_depense']);
                
                // Écriture comptable
                EcritureComptable::create([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => 'DEP-' . now()->format('YmdHis'),
                    'type_operation' => 'depense_diverse',
                    'compte_number' => $compteCharge,
                    'libelle' => "Dépense diverse: " . $data['description'] . " - " . $data['beneficiaire'],
                    'montant_debit' => $data['montant'],
                    'montant_credit' => 0,
                    'date_ecriture' => now(),
                    'date_valeur' => now(),
                    'devise' => $data['devise_depense'],
                    'statut' => 'comptabilise',
                    'created_by' => Auth::id(),
                ]);
                
                EcritureComptable::create([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => 'DEP-' . now()->format('YmdHis'),
                    'type_operation' => 'depense_diverse',
                    'compte_number' => '571300', // Compte petite caisse
                    'libelle' => "Dépense diverse: " . $data['description'] . " - " . $data['beneficiaire'],
                    'montant_debit' => 0,
                    'montant_credit' => $data['montant'],
                    'date_ecriture' => now(),
                    'date_valeur' => now(),
                    'devise' => $data['devise_depense'],
                    'statut' => 'comptabilise',
                    'created_by' => Auth::id(),
                ]);
                
                Notification::make()
                    ->title('Dépense enregistrée')
                    ->body("Dépense de {$data['montant']} {$data['devise_depense']} effectuée depuis la petite caisse")
                    ->success()
                    ->send();
                    
            } else {
                // Logique pour paiement salaire et virement (reste inchangé)
                // ICI SEULEMENT on cherche un compte
                $compte = \App\Models\Compte::find($data['compte_id']);
                
                if (!$compte) {
                    throw new \Exception('Compte non trouvé');
                }
                
                // CRÉDITER le compte (DÉPÔT) - Logique commune pour paiement salaire et virement
                $ancienSolde = $compte->solde;
                $compte->solde += $data['montant'];
                $compte->save();
                
                if ($data['type_operation'] === 'paiement_salaire') {
                    // Enregistrer le mouvement pour paiement salaire
                    $mouvement = Mouvement::create([
                        'compte_id' => $compte->id,
                        'type' => 'depot',
                        'type_mouvement' => 'paiement_salaire_charge',
                        'montant' => $data['montant'],
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $compte->solde,
                        'description' => $data['description'],
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
                    
                } elseif ($data['type_operation'] === 'virement') {
                    // Enregistrer le mouvement pour virement
                    $mouvement = Mouvement::create([
                        'compte_id' => $compte->id,
                        'type' => 'depot',
                        'type_mouvement' => 'virement_comptabilite',
                        'montant' => $data['montant'],
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $compte->solde,
                        'description' => $data['description'],
                        'nom_deposant' => $data['beneficiaire'],
                        'devise' => $data['devise'],
                        'operateur_id' => Auth::id(),
                        'numero_compte' => $compte->numero_compte,
                        'client_nom' => $data['beneficiaire'],
                        'date_mouvement' => now()
                    ]);
                    
                    // Enregistrer l'écriture comptable
                    $comptabilityService->enregistrerVirement(
                        $mouvement,
                        $compte,
                        $data['motif_virement'] ?? 'transfert',
                        $data['description'],
                        $data['beneficiaire']
                    );
                    
                    Notification::make()
                        ->title('Virement effectué')
                        ->body("Virement de {$data['montant']} {$data['devise']} crédité sur le compte {$compte->numero_compte}. Nouveau solde: {$compte->solde} {$compte->devise}")
                        ->success()
                        ->send();
                }
            }
        });
        
    } catch (\Exception $e) {
        Notification::make()
            ->title('Erreur')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
})
    ->modalWidth('3xl'),

                    Action::make('gestion_depenses')
                        ->label('Délaistage Petite Caisse')
                        ->icon('heroicon-o-arrow-left-circle')
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
            
            // MODIFICATION ICI : Vérifier si c'est une dépense diverse avant de chercher un compte
            if ($data['type_operation'] === 'depense_diverse') {
                // Logique pour Dépenses Diverses
                $petiteCaisse = Caisse::find($data['petite_caisse_id']);
                
                if (!$petiteCaisse) {
                    throw new \Exception('Petite caisse non trouvée');
                }
                
                if ($data['montant'] > $petiteCaisse->solde) {
                    throw new \Exception('Solde insuffisant dans la petite caisse');
                }
                
                // Débiter la petite caisse (physiquement)
                $ancienSolde = $petiteCaisse->solde;
                $petiteCaisse->solde -= $data['montant'];
                $petiteCaisse->save();
                
                // Enregistrer le mouvement physique
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
                
                // Créer l'écriture comptable correspondante
                $journal = JournalComptable::where('type_journal', 'achats')->first();
                $compteCharge = self::getCompteChargeDepense($data['type_depense']);
                
                // Écriture comptable
                EcritureComptable::create([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => 'DEP-' . now()->format('YmdHis'),
                    'type_operation' => 'depense_diverse_comptabilite',
                    'compte_number' => $compteCharge,
                    'libelle' => "Dépense diverse: " . $data['description'] . " - " . $data['beneficiaire'],
                    'montant_debit' => $data['montant'],
                    'montant_credit' => 0,
                    'date_ecriture' => now(),
                    'date_valeur' => now(),
                    'devise' => $data['devise_depense'],
                    'statut' => 'comptabilise',
                    'created_by' => Auth::id(),
                ]);
                
                EcritureComptable::create([
                    'journal_comptable_id' => $journal->id,
                    'reference_operation' => 'DEP-' . now()->format('YmdHis'),
                    'type_operation' => 'depense_diverse_comptabilite',
                    'compte_number' => '571300', // Compte petite caisse
                    'libelle' => "Dépense diverse: " . $data['description'] . " - " . $data['beneficiaire'],
                    'montant_debit' => 0,
                    'montant_credit' => $data['montant'],
                    'date_ecriture' => now(),
                    'date_valeur' => now(),
                    'devise' => $data['devise_depense'],
                    'statut' => 'comptabilise',
                    'created_by' => Auth::id(),
                ]);
                
                Notification::make()
                    ->title('Dépense enregistrée')
                    ->body("Dépense de {$data['montant']} {$data['devise_depense']} effectuée depuis la petite caisse")
                    ->success()
                    ->send();
                    
            } else {
                // Logique pour paiement salaire et virement (reste inchangé)
                $compte = \App\Models\Compte::find($data['compte_id']);
                
                if (!$compte) {
                    throw new \Exception('Compte non trouvé');
                }
                
                // CRÉDITER le compte (DÉPÔT) - Logique commune pour paiement salaire et virement
                $ancienSolde = $compte->solde;
                $compte->solde += $data['montant'];
                $compte->save();
                
                if ($data['type_operation'] === 'paiement_salaire') {
                    // Enregistrer le mouvement pour paiement salaire
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
                    
                } elseif ($data['type_operation'] === 'virement') {

                     $compte = \App\Models\Compte::find($data['compte_id']);
    
                            if (!$compte) {
                                throw new \Exception('Compte non trouvé');
                            }
                            
                            // CORRECTION : Récupérer le solde AVANT l'opération
                            $ancienSolde = $compte->solde;
                            
                            // CRÉDITER le compte
                            $compte->solde += $data['montant'];
                            $compte->save();


                    // Enregistrer le mouvement pour virement
                    $mouvement = Mouvement::create([
                        'compte_id' => $compte->id,
                        'type' => 'depot',
                        'type_mouvement' => 'virement_comptabilite',
                        'montant' => $data['montant'],
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $compte->solde,
                        'description' => $data['description'] . " - Motif: " . ($data['motif_virement'] ?? 'virement'),
                        'nom_deposant' => $data['beneficiaire'],
                        'devise' => $data['devise'],
                        'operateur_id' => Auth::id(),
                        'numero_compte' => $compte->numero_compte,
                        'client_nom' => $data['beneficiaire'],
                        'date_mouvement' => now()
                    ]);
                    
                    // Enregistrer l'écriture comptable
                    $comptabilityService->enregistrerVirement(
                        $mouvement,
                        $compte,
                        $data['motif_virement'] ?? 'transfert',
                        $data['description'],
                        $data['beneficiaire']
                    );
                    
                    Notification::make()
                        ->title('Virement effectué')
                        ->body("Virement de {$data['montant']} {$data['devise']} crédité sur le compte {$compte->numero_compte}. Nouveau solde: {$compte->solde} {$compte->devise}")
                        ->success()
                        ->send();
                }
            }
        });
        
    } catch (\Exception $e) {
        Notification::make()
            ->title('Erreur')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
})
                        ->requiresConfirmation()
                        ->modalHeading('Délaistage Petite Caisse')
                        ->modalDescription('Êtes-vous sûr de vouloir transférer le solde de la petite caisse vers la comptabilité ?'),

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
                ])
                ->label('Opérations de Caisse')
                ->icon('heroicon-o-banknotes')
                ->color('gray'),

                // Action État des Comptes (séparée car importante)
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

            ])
            ->label('Actions Comptables')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('primary')
            ->button(),

        ];
    }

    // Méthodes helper pour la mise à jour en temps réel (inchangées)
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