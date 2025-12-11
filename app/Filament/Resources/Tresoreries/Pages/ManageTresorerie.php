<?php
// app/Filament/Resources/TresorerieResource/Pages/ManageTresorerie.php

namespace App\Filament\Resources\TresorerieResource\Pages;

use App\Filament\Resources\RapportTresoreries\RapportTresorerieResource;
use App\Filament\Resources\Tresoreries\TresorerieResource;
use App\Models\Caisse;
use App\Models\Compte;
use App\Models\CompteEpargne;
use App\Models\Mouvement;
use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\CompteTransitoire;
use App\Models\Depense;
use App\Models\User;
use App\Models\TauxChange;
use App\Models\AchatFourniture;
use App\Models\PaiementSalaire;
use App\Models\JournalComptable;
use App\Models\EcritureComptable;
use App\Services\TresorerieService;
use Filament\Actions;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageTresorerie extends ManageRecords
{
    protected static string $resource = TresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [

            ActionGroup::make([

                
Action::make('rapport_instantanee')
    ->label('Rapport Instantané')
    ->icon('heroicon-o-clock')
    ->color('info')
    ->schema([
        DatePicker::make('date_rapport')
            ->label('Date du rapport')
            ->default(now())
            ->required(),
        Toggle::make('inclure_mouvements')
            ->label('Inclure le détail des mouvements')
            ->default(true),
    ])
  ->action(function (array $data) {
    try {
        $tresorerieService = app(TresorerieService::class);
        $rapport = $tresorerieService->rapportInstantanee($data['date_rapport']);
        
        // Export HTML temporaire
        $html = view('pdf.rapport-instantanee', [
            'rapport' => $rapport,
            'inclure_mouvements' => $data['inclure_mouvements']
        ])->render();

        $filename = 'rapport-tresorerie-instantane-' . now()->format('Y-m-d-H-i') . '.html';
        
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

   Action::make('paiement_groupes')
                    ->label('Paiement Crédits Groupe')
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->action(function () {
                        // Éventuellement des logs ou préparation de données
                        Log::info('Redirection vers la page de paiement groupe');
                        
                        // Redirection vers la page dédiée
                        return redirect()->route('paiement.credits.groupe');
                    }),

// Action::make('rapport_periode')
//     ->label('Rapport Période')
//     ->icon('heroicon-o-calendar')
//     ->color('warning')
//     ->schema([
//         DatePicker::make('date_debut')
//             ->label('Date de début')
//             ->default(now()->subDays(7))
//             ->required(),
//         DatePicker::make('date_fin')
//             ->label('Date de fin')
//             ->default(now())
//             ->required(),
//     ])
//     ->action(function (array $data) {
//         try {
//             $tresorerieService = app(TresorerieService::class);
//             $rapport = $tresorerieService->rapportPeriode($data['date_debut'], $data['date_fin']);
            
//             $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapport-periode', compact('rapport'))
//                 ->setPaper('A4', 'landscape');

//             return response()->streamDownload(function () use ($pdf) {
//                 echo $pdf->output();
//             }, 'rapport-tresorerie-periode-' . $data['date_debut'] . '-a-' . $data['date_fin'] . '.pdf');
            
//         } catch (\Exception $e) {
//             Notification::make()
//                 ->title('Erreur')
//                 ->body('Impossible de générer le rapport: ' . $e->getMessage())
//                 ->danger()
//                 ->send();
//         }
//     }),

// Modifiez l'action existante pour permettre de forcer la génération
// Action::make('rapport_journalier')
//     ->label('Rapport Journalier (Sauvegardé)')
//     ->icon('heroicon-o-document-chart-bar')
//     ->color('success')
//     ->schema([
//         DatePicker::make('date_rapport')
//             ->label('Date du rapport')
//             ->default(now())
//             ->required(),
//         Toggle::make('forcer')
//             ->label('Forcer la génération (écraser si existe)')
//             ->default(false)
//             ->helperText('Si un rapport existe déjà pour cette date, il sera remplacé.'),
//     ])
//     ->action(function (array $data) {
//         try {
//             $tresorerieService = app(TresorerieService::class);
//             $rapport = $tresorerieService->genererRapportTresorerie($data['date_rapport'], $data['forcer']);
            
//             Notification::make()
//                 ->title('Rapport généré')
//                 ->body('Rapport journalier créé avec succès')
//                 ->success()
//                 ->send();

//             // Rediriger vers le rapport créé
//             return redirect(RapportTresorerieResource::getUrl('view', ['record' => $rapport->id]));
            
//         } catch (\Exception $e) {
//             Notification::make()
//                 ->title('Erreur')
//                 ->body('Erreur: ' . $e->getMessage())
//                 ->danger()
//                 ->send();
//         }
//     })
//     ->requiresConfirmation()
//     ->modalHeading('Générer Rapport Journalier')
//     ->modalDescription('Êtes-vous sûr de vouloir générer le rapport pour cette date ?'),

             // NOUVELLE ACTION POUR LA CONVERSION DE DEVISES
            Action::make('conversion_devises')
                ->label('Conversion Devises')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->schema([
                    Section::make('Informations Conversion')
                        ->schema([
                            Select::make('devise_source')
                                ->label('Devise Source')
                                ->options([
                                    'USD' => 'USD',
                                    'CDF' => 'CDF',
                                ])
                                ->required()
                                ->default('USD')
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        // Récupérer le taux de change actuel
                                        $taux = TauxChange::getTauxActuel($state, $state === 'USD' ? 'CDF' : 'USD');
                                        if ($taux) {
                                            $set('taux_change', $taux->taux);
                                            $set('taux_change_id', $taux->id);
                                        }
                                        
                                        // Afficher le solde de la grande caisse source
                                        $grandeCaisseSource = Caisse::where('type_caisse', 'like', '%grande%')
                                                                    ->where('devise', $state)
                                                                    ->first();
                                        if ($grandeCaisseSource) {
                                            $set('solde_source_display', number_format($grandeCaisseSource->solde, 2) . ' ' . $state);
                                            $set('caisse_source_id', $grandeCaisseSource->id);
                                        } else {
                                            $set('solde_source_display', '0.00 ' . $state);
                                            $set('caisse_source_id', null);
                                        }
                                    }
                                }),

                            Select::make('devise_destination')
                                ->label('Devise Destination')
                                ->options([
                                    'USD' => 'USD',
                                    'CDF' => 'CDF',
                                ])
                                ->required()
                                ->default('CDF')
                                ->live()
                                ->afterStateUpdated(function ($set, $state, $get) {
                                    $deviseSource = $get('devise_source');
                                    if ($deviseSource && $state && $deviseSource !== $state) {
                                        // Récupérer le taux de change
                                        $taux = TauxChange::getTauxActuel($deviseSource, $state);
                                        if ($taux) {
                                            $set('taux_change', $taux->taux);
                                            $set('taux_change_id', $taux->id);
                                        }
                                        
                                        // Afficher le solde de la grande caisse destination
                                        $grandeCaisseDest = Caisse::where('type_caisse', 'like', '%grande%')
                                                                  ->where('devise', $state)
                                                                  ->first();
                                        if ($grandeCaisseDest) {
                                            $set('solde_dest_display', number_format($grandeCaisseDest->solde, 2) . ' ' . $state);
                                            $set('caisse_destination_id', $grandeCaisseDest->id);
                                        } else {
                                            $set('solde_dest_display', '0.00 ' . $state);
                                            $set('caisse_destination_id', null);
                                        }
                                    }
                                }),

   TextInput::make('taux_change')
    ->label('Taux de Change')
    ->numeric()
    ->required()
    ->minValue(0.0001)
    ->step(0.0001)
    ->live()
    ->afterStateUpdated(function ($set, $state, $get) {
        $montantSource = $get('montant_source') ?? 0;
        $deviseSource = $get('devise_source');
        $deviseDestination = $get('devise_destination');
        
        if ($state && $montantSource > 0) {
            // CORRECTION DE LA LOGIQUE DE CONVERSION
            if ($deviseSource === 'USD' && $deviseDestination === 'CDF') {
                // USD vers CDF : MULTIPLIER par le taux
                $montantConverti = $montantSource * $state;
            } else if ($deviseSource === 'CDF' && $deviseDestination === 'USD') {
                // CDF vers USD : DIVISER par le taux
                $montantConverti = $montantSource / $state;
            } else {
                $montantConverti = $montantSource;
            }
            
            $set('montant_destination', number_format($montantConverti, 2));
            $set('montant_destination_value', $montantConverti);
        }
    }),
                            Hidden::make('taux_change_id'),

                            Grid::make(2)
                                ->schema([
                                    TextInput::make('solde_source_display')
                                        ->label('Solde Grande Caisse Source')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->default('0.00 USD'),

                                    TextInput::make('solde_dest_display')
                                        ->label('Solde Grande Caisse Destination')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->default('0.00 CDF'),
                                ]),
                        ]),

Section::make('Montants à Convertir')
    ->schema([
        TextInput::make('montant_source')
            ->label('Montant à Convertir')
            ->numeric()
            ->required()
            ->minValue(0.01)
            ->step(0.01)
            ->live()
            ->afterStateUpdated(function ($set, $state, $get) {
                $taux = $get('taux_change') ?? 1;
                $deviseSource = $get('devise_source');
                $deviseDestination = $get('devise_destination');
                
                Log::info('Changement montant source:', [
                    'montant_source' => $state,
                    'taux' => $taux,
                    'devise_source' => $deviseSource,
                    'devise_destination' => $deviseDestination
                ]);
                
                if ($state && $taux) {
                    // CORRECTION DE LA LOGIQUE
                    if ($deviseSource === 'USD' && $deviseDestination === 'CDF') {
                        // USD vers CDF : MULTIPLIER par le taux
                        $montantConverti = $state * $taux;
                        Log::info('Calcul USD->CDF:', [
                            'operation' => 'USD->CDF',
                            'formule' => "{$state} * {$taux}",
                            'resultat' => $montantConverti
                        ]);
                    } else if ($deviseSource === 'CDF' && $deviseDestination === 'USD') {
                        // CDF vers USD : DIVISER par le taux  
                        $montantConverti = $state / $taux;
                        Log::info('Calcul CDF->USD:', [
                            'operation' => 'CDF->USD',
                            'formule' => "{$state} / {$taux}",
                            'resultat' => $montantConverti
                        ]);
                    } else {
                        $montantConverti = $state;
                    }
                    
                    $set('montant_destination', number_format($montantConverti, 2));
                    $set('montant_destination_value', $montantConverti);
                    
                    Log::info('Valeurs définies:', [
                        'montant_destination_affichage' => number_format($montantConverti, 2),
                        'montant_destination_value' => $montantConverti
                    ]);
                    
                    // Validation du solde source
                    $caisseSourceId = $get('caisse_source_id');
                    if ($caisseSourceId) {
                        $caisseSource = Caisse::find($caisseSourceId);
                        if ($caisseSource && $state > $caisseSource->solde) {
                            $set('validation_message', 'Solde insuffisant dans la caisse source');
                        } else {
                            $set('validation_message', '');
                        }
                    }
                }
            }),

                Section::make('Détails de la Conversion')
                ->schema([
                    Textarea::make('motif_conversion')
                        ->label('Motif de la Conversion')
                        ->required()
                        ->placeholder('Ex: Conversion quotidienne - Ajustement des liquidités')
                        ->default('Conversion de devises entre grandes caisses'),
                ]),
            
        // AJOUTER CES CHAMPS CACHÉS
        Hidden::make('caisse_source_id'),
        Hidden::make('caisse_destination_id'),
        Hidden::make('montant_destination_value'),
        
        // Champ pour afficher le montant converti
        TextInput::make('montant_destination')
            ->label('Montant Converti')
            ->disabled()
            ->dehydrated(false)
            ->default('0.00'),
            
        // Champ pour afficher les messages de validation
        TextInput::make('validation_message')
            ->label('Validation')
            ->disabled()
            ->dehydrated(false)
            ->extraAttributes(['class' => 'text-danger-600 font-medium'])
            ->visible(fn ($get) => !empty($get('validation_message'))),
    ])


                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            self::effectuerConversionDevises($data);
                        });

                        Notification::make()
                            ->title('Conversion réussie')
                            ->body("Conversion de {$data['montant_source']} {$data['devise_source']} vers " . 
                                   number_format($data['montant_destination_value'], 2) . " {$data['devise_destination']} effectuée avec succès")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur de conversion')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Conversion de Devises')
                ->modalDescription('Êtes-vous sûr de vouloir effectuer cette conversion ?'),
                // ->visible(fn () => Auth::user()->can('view_compte')),

        Action::make('delaisage_tresorerie')
    ->label('Délaistage Trésorerie')
    ->icon('heroicon-o-arrow-right-circle')
    ->color('warning')
    ->schema([
        Select::make('devise_delaisage')
            ->label('Devise du Délaistage')
            ->options([
                'USD' => 'USD',
                'CDF' => 'CDF',
            ])
            ->required()
            ->default('USD')
            ->live()
            ->afterStateUpdated(function ($set, $state) {
                if ($state) {
                    // MODIFICATION : Filtrer uniquement les grandes caisses
                    $caisses = Caisse::where('devise', $state)
                                    ->where('type_caisse', 'like', '%grande%') // ← AJOUT IMPORTANT
                                    ->get();
                    
                    $totalSoldes = $caisses->sum('solde');
                    $set('total_soldes_display', number_format($totalSoldes, 2) . ' ' . $state);
                    
                    $infoCaisses = "**Grandes Caisses en {$state}:**\n";
                    foreach ($caisses as $caisse) {
                        $infoCaisses .= "- {$caisse->nom}: " . number_format($caisse->solde, 2) . " {$caisse->devise}\n";
                    }
                    $set('info_caisses_delaisage', $infoCaisses);
                }
            }),
        
        TextInput::make('total_soldes_display')
            ->label('Total des Soldes à Transférer')
            ->disabled()
            ->dehydrated(false)
            ->default('0.00 USD'),
            
        TextInput::make('info_caisses_delaisage')
            ->label('Détail des Grandes Caisses') // ← MODIFICATION DU LABEL
            ->disabled()
            ->dehydrated(false)
            ->columnSpanFull()
            ->extraAttributes(['class' => 'bg-gray-50 border-gray-200']),
            
        Textarea::make('motif_delaisage')
            ->label('Motif du Délaistage')
            ->required()
            ->placeholder('Ex: Délaistage quotidien - Fin de journée')
            ->default('Délaistage automatique des grandes caisses vers comptabilité'), // ← MODIFICATION
    ])
    ->action(function (array $data) {
        try {
            DB::transaction(function () use ($data) {
                $devise = $data['devise_delaisage'];
                
                // MODIFICATION : Filtrer uniquement les GRANDES caisses
                $caisses = Caisse::where('devise', $devise)
                                ->where('type_caisse', 'like', '%grande%') // ← AJOUT IMPORTANT
                                ->get();
                
                if ($caisses->isEmpty()) {
                    throw new \Exception("Aucune grande caisse trouvée pour la devise {$devise}");
                }
                
                $totalTransfert = 0;
                $reference = 'DELAISAGE-' . now()->format('Ymd-His');
                
                foreach ($caisses as $caisse) {
                    if ($caisse->solde > 0) {
                        // Enregistrer le mouvement de sortie
                        Mouvement::create([
                            'caisse_id' => $caisse->id,
                            'type' => 'retrait',
                            'type_mouvement' => 'delaisage_comptabilite',
                            'montant' => $caisse->solde,
                            'solde_avant' => $caisse->solde,
                            'solde_apres' => 0,
                            'description' => $data['motif_delaisage'] . " - Transfert vers comptabilité",
                            'nom_deposant' => 'Système Délaistage',
                            'devise' => $devise,
                            'operateur_id' => Auth::id(),
                            'numero_compte' => $caisse->type_caisse,
                            'client_nom' => 'Transfert comptabilité',
                            'date_mouvement' => now()
                        ]);
                        
                        $totalTransfert += $caisse->solde;
                        
                        // Réinitialiser le solde de la caisse
                        $caisse->solde = 0;
                        $caisse->save();
                    }
                }
                
                if ($totalTransfert > 0) {
                    // Générer l'écriture comptable
                    self::genererEcritureComptableDelaisage($totalTransfert, $devise, $reference, $data['motif_delaisage']);
                    
                    Notification::make()
                        ->title('Délaistage réussi')
                        ->body("{$totalTransfert} {$devise} transférés vers la comptabilité depuis les grandes caisses")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Aucun transfert')
                        ->body("Aucun solde à transférer pour les grandes caisses en {$devise}")
                        ->info()
                        ->send();
                }
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
    ->modalHeading('Délaistage Trésorerie (Grandes Caisses)') // ← MODIFICATION
    ->modalDescription('Êtes-vous sûr de vouloir transférer les soldes des GRANDES caisses vers la comptabilité ?'), // ← MODIFICATION

            Action::make('operation_tresorerie')
                ->label('Nouvelle Opération')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->schema(self::getOperationFormSchema())
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            switch ($data['type_operation']) {
                                case 'depot_compte':
                                    self::depotVersCompte($data);
                                    break;
                                case 'retrait_compte':
                                    self::retraitDepuisCompte($data);
                                    break;
                                case 'paiement_credit':
                                    self::paiementCredit($data);
                                    break;
                                case 'versement_agent':
                                    self::versementAgentCollecteur($data);
                                    break;
                                case 'transfert_caisse':
                                    self::transfertEntreCaisses($data);
                                    break;
                             
                                case 'achat_carnet_livre': // S'ASSURER QUE CE CASE EXISTE
                                self::achatCarnetLivre($data);
                                break;

                                case 'retrait_epargne': // NOUVEAU
                                self::retraitDepuisCompteEpargne($data);
                                break;

                                  case 'frais_adhesion': // NOUVEAU
                                self::prelevementFraisAdhesion($data);
                                break;
                            }

                            // Notifier la comptabilité
                            self::notifierComptabilite($data);
                        });

                        Notification::make()
                            ->title('Opération réussie')
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
            
            // Actions\CreateAction::make()
            //     ->label('Nouvelle Caisse')
            //     ->icon('heroicon-o-plus'),
            
            // Action::make('rapport_journalier')
            //     ->label('Rapport Journalier')
            //     ->icon('heroicon-o-document-chart-bar')
            //     ->color('info')
            //     ->action(function () {
            //         try {
            //             $rapport = app(TresorerieService::class)->genererRapportFinJournee();
                        
            //             Notification::make()
            //                 ->title('Rapport généré')
            //                 ->body('Rapport journalier créé avec succès')
            //                 ->success()
            //                 ->send();
                            
            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Erreur')
            //                 ->body('Erreur: ' . $e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     })
            //     ->requiresConfirmation()
            //     ->modalHeading('Générer Rapport Journalier')
            //     ->modalDescription('Êtes-vous sûr de vouloir générer le rapport de fin de journée ?')
            //     ->visible(fn () => Auth::user()->can('view_compte')),


            ])
              ->label('Actions caisse')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('primary')
            ->button(),

            // Dans getHeaderActions() - ajoutez ces nouvelles actions

        ];
    }

    private static function getOperationFormSchema(): array
    {
        return [
            Section::make('Type d\'Opération')
                ->schema([
                    Select::make('type_operation')
                        ->label('Type d\'opération')
                        ->options([
                            'depot_compte' => 'Dépôt vers Compte Membre',
                            'retrait_compte' => 'Retrait depuis Compte Courant',
                            'retrait_epargne' => 'Retrait depuis Compte Épargne', 
                            'paiement_credit' => 'Paiement de Crédit',
                            'versement_agent' => 'Versement Agent Collecteur',
                            'transfert_caisse' => 'Transfert entre Caisses',
                            'achat_carnet_livre' => 'Achat Carnet et Livres',
                            'frais_adhesion' => 'Frais d\'Adhésion',
                          
                          
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($set) {
                            $set('compte_numero', null);
                            $set('compte_id', null);
                            $set('credit_numero', null);
                            $set('credit_id', null);
                            $set('agent_identifiant', null);
                            $set('agent_id', null);
                            $set('caisse_source_identifiant', null);
                            $set('caisse_source_id', null);
                            $set('caisse_destination_identifiant', null);
                            $set('caisse_destination_id', null);
                            $set('categorie_depense', null);
                            $set('devise', 'USD');
                        }),
                ])
                ->columns(1),

            // Section pour les opérations liées aux comptes membres - MODIFIÉE
            Section::make('Informations Compte Membre')
                ->schema([
                    TextInput::make('compte_numero')
                        ->label('Numéro de Compte Membre')
                        ->required(function ($get) {
                            return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit', 'paiement_salaire']);
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $compte = Compte::where('numero_compte', $state)->first();
                                if ($compte) {
                                    // Afficher les informations du client
                                    $nomComplet = $compte->nom;
                                    if ($compte->type_compte === 'groupe_solidaire') {
                                        $nomComplet .= ' (Groupe)';
                                    } else {
                                        if ($compte->postnom) $nomComplet .= ' ' . $compte->postnom;
                                        if ($compte->prenom) $nomComplet .= ' ' . $compte->prenom;
                                    }
                                    $set('client_nom_complet', $nomComplet);

                                    $soldeTotal = (float) $compte->solde;
                                    $soldeDisponible = (float) Mouvement::getSoldeDisponible($compte->id);
                                    $cautionBloquee = (float) Mouvement::getCautionBloquee($compte->id);
                                    
                                    $set('solde_total_display', number_format($soldeTotal, 2) . ' ' . $compte->devise);
                                    $set('solde_disponible_display', number_format($soldeDisponible, 2) . ' ' . $compte->devise);
                                    $set('caution_bloquee_display', number_format($cautionBloquee, 2) . ' ' . $compte->devise);
                                    $set('devise', $compte->devise);
                                    $set('compte_id', $compte->id);
                                } else {
                                    $set('client_nom_complet', 'Compte non trouvé');
                                    $set('solde_total_display', '0.00 USD');
                                    $set('solde_disponible_display', '0.00 USD');
                                    $set('caution_bloquee_display', '0.00 USD');
                                    $set('compte_id', null);
                                }
                            }
                        })
                        ->placeholder('Saisir le numéro de compte (Ex: C0001 ou GS00001)')
                        ->hint('Tapez le numéro de compte et attendez la validation'),

                    // Affichage du nom complet du client
                    TextInput::make('client_nom_complet')
                        ->label('Nom du Client')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('')
                        ->visible(function ($get) {
                            return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit', 'paiement_salaire']) && $get('compte_numero');
                        }),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('solde_total_display')
                                ->label('Solde Total')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD'),
                            
                            TextInput::make('solde_disponible_display')
                                ->label('Solde Disponible')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD'),
                            
                            TextInput::make('caution_bloquee_display')
                                ->label('Caution Bloquée')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD'),
                        ])
                        ->visible(function ($get) {
                            return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit', 'paiement_salaire']) && $get('compte_numero');
                        }),

                    Hidden::make('compte_id'),
                ])
                ->visible(function ($get) {
                    return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit', 'paiement_salaire']);
                }),

            // Section pour les comptes épargne - NOUVELLE SECTION
            Section::make('Informations Compte Épargne')
                ->schema([
                    TextInput::make('compte_epargne_numero')
                        ->label('Numéro de Compte Épargne')
                        ->required(function ($get) {
                            return $get('type_operation') === 'retrait_epargne';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $compteEpargne = CompteEpargne::where('numero_compte', $state)->first();
                                if ($compteEpargne) {
                                    $set('client_epargne_nom_complet', self::getNomCompletCompteEpargne($compteEpargne));
                                    $set('solde_epargne_display', number_format($compteEpargne->solde, 2) . ' ' . $compteEpargne->devise);
                                    $set('devise', $compteEpargne->devise);
                                    $set('compte_epargne_id', $compteEpargne->id);
                                    
                                    // Vérifier si le retrait est possible
                                    $soldeMinimum = $compteEpargne->solde_minimum ?? 0;
                                    $retraitMaximal = $compteEpargne->solde - $soldeMinimum;
                                    $set('retrait_maximal_display', number_format($retraitMaximal, 2) . ' ' . $compteEpargne->devise);
                                } else {
                                    $set('client_epargne_nom_complet', 'Compte épargne non trouvé');
                                    $set('solde_epargne_display', '0.00 USD');
                                    $set('retrait_maximal_display', '0.00 USD');
                                    $set('compte_epargne_id', null);
                                }
                            }
                        })
                        ->placeholder('Saisir le numéro de compte épargne (Ex: CEM000001 ou CEG000001)'),

                    TextInput::make('client_epargne_nom_complet')
                        ->label('Titulaire du Compte')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('')
                        ->visible(function ($get) {
                            return $get('type_operation') === 'retrait_epargne' && $get('compte_epargne_numero');
                        }),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('solde_epargne_display')
                                ->label('Solde Épargne')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD'),
                            
                            TextInput::make('retrait_maximal_display')
                                ->label('Retrait Maximal Possible')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD'),
                        ])
                        ->visible(function ($get) {
                            return $get('type_operation') === 'retrait_epargne' && $get('compte_epargne_numero');
                        }),

                    Hidden::make('compte_epargne_id'),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'retrait_epargne';
                }),

            // Section pour les paiements de crédit - MODIFIÉE
            Section::make('Informations Crédit')
                ->schema([
                    TextInput::make('credit_numero')
                        ->label('Numéro de Crédit')
                        ->required(function ($get) {
                            return $get('type_operation') === 'paiement_credit';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state, $get) {
                            if ($state && $get('type_operation') === 'paiement_credit') {
                                $compteId = $get('compte_id');
                                if (!$compteId) {
                                    $set('montant_du_display', '0.00 USD');
                                    $set('credit_id', null);
                                    return;
                                }
                                
                                $compte = Compte::find($compteId);
                                if (!$compte) return;

                                if (str_starts_with($compte->numero_compte, 'GS')) {
                                    $credit = CreditGroupe::where('numero_credit', $state)
                                        ->where('compte_id', $compteId)
                                        ->where('statut_demande', 'approuve')
                                        ->where('montant_total', '>', 0)
                                        ->first();
                                } else {
                                    $credit = Credit::where('numero_credit', $state)
                                        ->where('compte_id', $compteId)
                                        ->where('statut_demande', 'approuve')
                                        ->where('montant_total', '>', 0)
                                        ->first();
                                }

                                if ($credit) {
                                    $set('montant_du_display', number_format($credit->montant_total, 2) . ' ' . $compte->devise);
                                    $set('credit_id', $credit->id);
                                } else {
                                    $set('montant_du_display', 'Crédit non trouvé');
                                    $set('credit_id', null);
                                }
                            }
                        })
                        ->placeholder('Saisir le numéro de crédit'),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('montant_du_display')
                                ->label('Montant Dû Total')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD'),
                            
                            TextInput::make('prochain_echeance_display')
                                ->label('Prochaine Échéance')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('N/A'),
                        ])
                        ->visible(function ($get) {
                            return $get('type_operation') === 'paiement_credit' && $get('credit_numero');
                        }),

                    Hidden::make('credit_id'),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'paiement_credit';
                }),

            // Section pour les agents collecteurs - MODIFIÉE
            Section::make('Agent Collecteur')
                ->schema([
                    TextInput::make('agent_identifiant')
                        ->label('Identifiant de l\'Agent (ID, Nom ou Email)')
                        ->required(function ($get) {
                            return $get('type_operation') === 'versement_agent';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                // Chercher l'agent par ID, nom ou email
                                $agent = User::whereHas('roles', function ($query) {
                                        $query->where('name', 'AgentCollecteur');
                                    })
                                    ->where(function ($query) use ($state) {
                                        $query->where('id', $state)
                                              ->orWhere('name', 'LIKE', "%{$state}%")
                                              ->orWhere('email', 'LIKE', "%{$state}%");
                                    })
                                    ->first();

                                if ($agent) {
                                    $compteAgent = CompteTransitoire::where('user_id', $agent->id)->first();
                                    if ($compteAgent) {
                                        $set('solde_agent_display', number_format($compteAgent->solde, 2) . ' ' . $compteAgent->devise);
                                        $set('devise', $compteAgent->devise);
                                    } else {
                                        $set('solde_agent_display', '0.00 USD');
                                        $set('devise', 'USD');
                                    }
                                    $set('agent_id', $agent->id);
                                    $set('agent_nom_complet', $agent->name);
                                } else {
                                    $set('solde_agent_display', '0.00 USD');
                                    $set('devise', 'USD');
                                    $set('agent_id', null);
                                    $set('agent_nom_complet', 'Agent non trouvé');
                                }
                            }
                        })
                        ->placeholder('Saisir ID, nom ou email de l\'agent'),

                    TextInput::make('agent_nom_complet')
                        ->label('Nom de l\'Agent')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('')
                     ->visible(function ($get) {
                            return $get('type_operation') === 'versement_agent' && $get('agent_identifiant');
                        }),

                    TextInput::make('solde_agent_display')
                        ->label('Solde Actuel de l\'Agent')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00 USD')
                   ->visible(function ($get) {
                            return $get('type_operation') === 'versement_agent' && $get('agent_identifiant');
                        }),

                    // Sélection de devise pour le versement agent
                    Select::make('devise')
                        ->label('Devise du Versement')
                        ->options([
                            'USD' => 'USD',
                            'CDF' => 'CDF',
                        ])
                        ->default('USD')
                        ->required(function ($get) {
                            return $get('type_operation') === 'versement_agent';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                      ->where('devise', $state)
                                                      ->first();
                                if ($grandeCaisse) {
                                    $set('solde_grande_caisse_display', number_format($grandeCaisse->solde, 2) . ' ' . $state);
                                } else {
                                    $set('solde_grande_caisse_display', '0.00 ' . $state);
                                }
                            }
                        }),

                    TextInput::make('solde_grande_caisse_display')
                        ->label(function ($get) {
                            $devise = $get('devise') ?? 'USD';
                            return "Solde Grande Caisse {$devise}";
                        })
                        ->disabled()
                        ->dehydrated(false)
                        ->default(function ($get) {
                            $devise = $get('devise') ?? 'USD';
                            $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                  ->where('devise', $devise)
                                                  ->first();
                            return $grandeCaisse ? number_format($grandeCaisse->solde, 2) . ' ' . $devise : '0.00 ' . $devise;
                        })
                        ->visible(function ($get) {
                            return $get('type_operation') === 'versement_agent' && $get('devise');
                        }),

                  Hidden::make('agent_id'),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'versement_agent';
                }),

            // Section pour les transferts entre caisses - MODIFIÉE
            Section::make('Transfert entre Caisses')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('caisse_source_identifiant')
                                ->label('Caisse Source (Nom ou Type)')
                                ->required(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse';
                                })
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        $caisse = Caisse::where('nom', 'LIKE', "%{$state}%")
                                                        ->orWhere('type_caisse', 'LIKE', "%{$state}%")
                                                        ->first();
                                        if ($caisse) {
                                            $set('solde_caisse_source_display', number_format($caisse->solde, 2) . ' ' . $caisse->devise);
                                            $set('caisse_source_id', $caisse->id);
                                            $set('caisse_source_nom', $caisse->nom);
                                            $set('devise', $caisse->devise);
                                        } else {
                                            $set('solde_caisse_source_display', 'Caisse non trouvée');
                                            $set('caisse_source_id', null);
                                            $set('caisse_source_nom', '');
                                        }
                                    }
                                })
                                ->placeholder('Saisir le nom ou type de caisse'),

                            TextInput::make('caisse_destination_identifiant')
                                ->label('Caisse Destination (Nom ou Type)')
                                ->required(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse';
                                })
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        $caisse = Caisse::where('nom', 'LIKE', "%{$state}%")
                                                        ->orWhere('type_caisse', 'LIKE', "%{$state}%")
                                                        ->first();
                                        if ($caisse) {
                                            $set('solde_caisse_dest_display', number_format($caisse->solde, 2) . ' ' . $caisse->devise);
                                            $set('caisse_destination_id', $caisse->id);
                                            $set('caisse_destination_nom', $caisse->nom);
                                        } else {
                                            $set('solde_caisse_dest_display', 'Caisse non trouvée');
                                            $set('caisse_destination_id', null);
                                            $set('caisse_destination_nom', '');
                                        }
                                    }
                                })
                                ->placeholder('Saisir le nom ou type de caisse'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('solde_caisse_source_display')
                                ->label('Solde Caisse Source')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                            ->visible(function ($get) {
                                return $get('type_operation') === 'transfert_caisse' && $get('caisse_source_identifiant');
                            }),

                            TextInput::make('solde_caisse_dest_display')
                                ->label('Solde Caisse Destination')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                                ->visible(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse' && $get('caisse_destination_identifiant');
                                }),
                        ]),

                    Hidden::make('caisse_source_id'),
                    Hidden::make('caisse_destination_id'),
                    Hidden::make('caisse_source_nom'),
                    Hidden::make('caisse_destination_nom'),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'transfert_caisse';
                }),

           

            // Section pour l'achat de carnets et livres - MODIFIÉE
            Section::make('Achat de Carnets et Livres')
                ->schema([
                    Select::make('type_achat')
                        ->label('Type d\'Achat')
                        ->options([
                            'carnet' => 'Carnets',
                            'livre' => 'Livres',
                            'autre' => 'Autres Articles',
                        ])
                        ->required(function ($get) {
                            return $get('type_operation') === 'achat_carnet_livre';
                        })
                        ->default('carnet'),

                    TextInput::make('quantite_achat')
                        ->label('Quantité')
                        ->numeric()
                        ->required(function ($get) {
                            return $get('type_operation') === 'achat_carnet_livre';
                        })
                        ->default(1)
                        ->minValue(1),

                    TextInput::make('prix_unitaire')
                        ->label('Prix Unitaire')
                        ->numeric()
                        ->required(function ($get) {
                            return $get('type_operation') === 'achat_carnet_livre';
                        })
                        ->minValue(0.01)
                        ->step(0.01)
                        ->live()
                        ->afterStateUpdated(function ($set, $state, $get) {
                            $quantite = $get('quantite_achat') ?? 1;
                            $montantTotal = $state * $quantite;
                            $set('montant_total_achat_display', number_format($montantTotal, 2) . ' USD');
                            $set('montant', $montantTotal);
                        }),

                    TextInput::make('montant_total_achat_display')
                        ->label('Montant Total')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00 USD'),

                    Select::make('devise_achat')
                        ->label('Devise de l\'Achat')
                        ->options([
                            'USD' => 'USD',
                            'CDF' => 'CDF',
                        ])
                        ->default('USD')
                        ->required(function ($get) {
                            return $get('type_operation') === 'achat_carnet_livre';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                ->where('devise', $state)
                                                ->first();
                                if ($caisse) {
                                    $set('solde_caisse_achat_display', number_format($caisse->solde, 2) . ' ' . $state);
                                    $set('nom_caisse_achat', $caisse->nom);
                                } else {
                                    $set('solde_caisse_achat_display', '0.00 ' . $state);
                                    $set('nom_caisse_achat', 'Non disponible');
                                }
                            }
                        }),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('nom_caisse_achat')
                                ->label('Caisse Utilisée')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $devise = $get('devise_achat') ?? 'USD';
                                    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                    ->where('devise', $devise)
                                                    ->first();
                                    return $caisse ? $caisse->nom : 'Grande Caisse ' . $devise;
                                }),

                            TextInput::make('solde_caisse_achat_display')
                                ->label('Solde Disponible')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $devise = $get('devise_achat') ?? 'USD';
                                    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                    ->where('devise', $devise)
                                                    ->first();
                                    return $caisse ? number_format($caisse->solde, 2) . ' ' . $devise : '0.00 ' . $devise;
                                }),
                        ])
                        ->visible(function ($get) {
                            return $get('type_operation') === 'achat_carnet_livre' && $get('devise_achat');
                        }),

                    TextInput::make('fournisseur_achat')
                        ->label('Nom Agent')
                        ->required(function ($get) {
                            return $get('type_operation') === 'achat_carnet_livre';
                        })
                        ->placeholder('Nom du fournisseur'),

                    Hidden::make('montant')
                        ->default(0),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'achat_carnet_livre';
                }),


                 Section::make('Frais d\'Adhésion')
                    ->schema([
                        TextInput::make('compte_numero_adhesion')
                            ->label('Numéro de Compte Membre')
                            ->required(function ($get) {
                                return $get('type_operation') === 'frais_adhesion';
                            })
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $compte = Compte::where('numero_compte', $state)->first();
                                    if ($compte) {
                                        $nomComplet = self::getNomCompletClient($compte);
                                        $set('client_nom_complet_adhesion', $nomComplet);
                                        $set('solde_total_display_adhesion', number_format($compte->solde, 2) . ' ' . $compte->devise);
                                        $set('solde_disponible_display_adhesion', number_format(Mouvement::getSoldeDisponible($compte->id), 2) . ' ' . $compte->devise);
                                        $set('devise', $compte->devise);
                                        $set('compte_id_adhesion', $compte->id);
                                    } else {
                                        $set('client_nom_complet_adhesion', 'Compte non trouvé');
                                        $set('solde_total_display_adhesion', '0.00 USD');
                                        $set('solde_disponible_display_adhesion', '0.00 USD');
                                        $set('compte_id_adhesion', null);
                                    }
                                }
                            })
                            ->placeholder('Saisir le numéro de compte')
                            ->visible(function ($get) {
                                return $get('type_operation') === 'frais_adhesion';
                            }),

                        TextInput::make('client_nom_complet_adhesion')
                            ->label('Nom du Client')
                            ->disabled()
                            ->dehydrated(false)
                            ->default('')
                            ->visible(function ($get) {
                                return $get('type_operation') === 'frais_adhesion' && $get('compte_numero_adhesion');
                            }),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('solde_total_display_adhesion')
                                    ->label('Solde Total')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default('0.00 USD'),
                                
                                TextInput::make('solde_disponible_display_adhesion')
                                    ->label('Solde Disponible')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default('0.00 USD'),
                            ])
                            ->visible(function ($get) {
                                return $get('type_operation') === 'frais_adhesion' && $get('compte_numero_adhesion');
                            }),

                        TextInput::make('montant_frais_adhesion')
                            ->label('Montant des Frais d\'Adhésion')
                            ->numeric()
                            ->required(function ($get) {
                                return $get('type_operation') === 'frais_adhesion';
                            })
                            ->minValue(0.01)
                            ->step(0.01)
                            ->default(5.00) // Montant par défaut
                            ->live()
                            ->afterStateUpdated(function ($set, $state, $get) {
                                if ($state) {
                                    $compteId = $get('compte_id_adhesion');
                                    if ($compteId) {
                                        $soldeDisponible = Mouvement::getSoldeDisponible($compteId);
                                        if ($state > $soldeDisponible) {
                                            $set('validation_frais_adhesion', 'Solde disponible insuffisant');
                                        } else {
                                            $set('validation_frais_adhesion', '');
                                        }
                                    }
                                }
                            })
                            ->visible(function ($get) {
                                return $get('type_operation') === 'frais_adhesion';
                            }),

                        TextInput::make('validation_frais_adhesion')
                            ->label('Validation')
                            ->disabled()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'text-danger-600 font-medium'])
                            ->visible(function ($get) {
                                return $get('type_operation') === 'frais_adhesion' && !empty($get('validation_frais_adhesion'));
                            }),

                        Hidden::make('compte_id_adhesion'),
                    ])
                    ->visible(function ($get) {
                        return $get('type_operation') === 'frais_adhesion';
                    }),

           // Section pour les détails de l'opération 
// Section pour les détails de l'opération - CORRIGÉE
Section::make('Détails de l\'Opération')
    ->schema([

        // CHAMP POUR LE NOM DU CLIENT (visible pour frais d'adhésion)
        TextInput::make('nom_client_adhesion')
            ->label('Nom du Client')
            ->required(function ($get) {
                return $get('type_operation') === 'frais_adhesion';
            })
            ->placeholder('Saisir le nom du client')
            ->visible(function ($get) {
                return $get('type_operation') === 'frais_adhesion';
            }),

        // CHAMP MONTANT UNIQUE - SUPPRIMER LE DUPLICATA
        TextInput::make('montant')
            ->label(function ($get) {
                $devise = $get('devise') ?? 'USD';
                $typeOperation = $get('type_operation');
                
                if ($typeOperation === 'frais_adhesion') {
                    return "Montant des Frais ($devise)";
                }
                return "Montant ($devise)";
            })
            ->numeric()
            ->required()
            ->minValue(0.01)
            ->step(0.01)
            ->rules([
                function ($get) {
                    return function ($attribute, $value, $fail) use ($get) {
                        if (!$value || $value <= 0) return;

                        $typeOperation = $get('type_operation');

                        // Validation pour frais d'adhésion
                        if ($typeOperation === 'frais_adhesion') {
                            $compteId = $get('compte_id_adhesion');
                            if ($compteId) {
                                $soldeDisponible = Mouvement::getSoldeDisponible($compteId);
                                if ($value > $soldeDisponible) {
                                    $fail("Solde disponible insuffisant. Maximum: " . number_format($soldeDisponible, 2) . " USD");
                                }
                            }
                        }

                        // Validations existantes pour autres opérations
                        if ($typeOperation === 'retrait_compte') {
                            $compteId = $get('compte_id');
                            if ($compteId) {
                                $soldeDisponible = Mouvement::getSoldeDisponible($compteId);
                                if ($value > $soldeDisponible) {
                                    $fail("Solde disponible insuffisant. Maximum: " . number_format($soldeDisponible, 2) . " USD");
                                }
                            }
                        }

                        if ($typeOperation === 'retrait_epargne') {
                            $compteEpargneId = $get('compte_epargne_id');
                            if ($compteEpargneId) {
                                $compteEpargne = CompteEpargne::find($compteEpargneId);
                                if ($compteEpargne) {
                                    $soldeMinimum = $compteEpargne->solde_minimum ?? 0;
                                    $retraitMaximal = $compteEpargne->solde - $soldeMinimum;
                                    
                                    if ($value > $retraitMaximal) {
                                        $fail("Retrait impossible - Le solde minimum de " . number_format($soldeMinimum, 2) . " {$compteEpargne->devise} doit être maintenu. Maximum: " . number_format($retraitMaximal, 2) . " {$compteEpargne->devise}");
                                    }
                                    
                                    if ($value > $compteEpargne->solde) {
                                        $fail("Solde insuffisant. Maximum: " . number_format($compteEpargne->solde, 2) . " {$compteEpargne->devise}");
                                    }
                                }
                            }
                        }
                        
                        if ($typeOperation === 'paiement_credit') {
                            $compteId = $get('compte_id');
                            if ($compteId) {
                                $soldeDisponible = Mouvement::getSoldeDisponible($compteId);
                                if ($value > $soldeDisponible) {
                                    $fail("Solde disponible insuffisant pour le paiement. Maximum: " . number_format($soldeDisponible, 2) . " USD");
                                }
                            }
                        }

                        if ($typeOperation === 'transfert_caisse') {
                            $caisseSourceId = $get('caisse_source_id');
                            if ($caisseSourceId) {
                                $caisseSource = Caisse::find($caisseSourceId);
                                if ($caisseSource && $value > $caisseSource->solde) {
                                    $fail("Solde insuffisant dans la caisse source. Maximum: " . number_format($caisseSource->solde, 2) . " {$caisseSource->devise}");
                                }
                            }
                        }
                    };
                }
            ]),

        // CHAMP POUR LE NOM DU DÉPOSANT (visible pour dépôt et versement agent)
        TextInput::make('nom_deposant')
            ->label('Nom du Déposant')
            ->required(function ($get) {
                return in_array($get('type_operation'), ['depot_compte', 'versement_agent']);
            })
            ->placeholder('Saisir le nom de la personne qui dépose')
            ->visible(function ($get) {
                return in_array($get('type_operation'), ['depot_compte', 'versement_agent']);
            }),

        // CHAMP POUR LE NOM DU RETIRANT (caché pour frais d'adhésion)
        TextInput::make('nom_retirant')
            ->label('Nom du Retirant')
            ->required(function ($get) {
                return in_array($get('type_operation'), ['retrait_compte', 'paiement_credit', 'retrait_epargne', 'frais_adhesion']);
            })
            ->placeholder('Saisir le nom de la personne qui retire')
            ->default('TUMAINI LETU FINANCE') // Valeur par défaut
            ->visible(function ($get) {
                $typeOperation = $get('type_operation');
                // Cacher pour frais d'adhésion, montrer pour les autres
                return in_array($typeOperation, ['retrait_compte', 'paiement_credit', 'retrait_epargne']) && $typeOperation !== 'frais_adhesion';
            })
            ->hidden(function ($get) {
                return $get('type_operation') === 'frais_adhesion';
            }),

        // Champ caché pour frais d'adhésion avec valeur fixe
        Hidden::make('nom_retirant_adhesion')
            ->default('TUMAINI LETU FINANCE')
            ->visible(function ($get) {
                return $get('type_operation') === 'frais_adhesion';
            }),

        Textarea::make('description')
            ->label('Description')
            ->nullable()
            ->maxLength(255)
            ->placeholder(function ($get) {
                $type = $get('type_operation');
                return match ($type) {
                    'depot_compte' => 'Dépôt sur compte membre',
                    'retrait_compte' => 'Retrait depuis compte membre',
                    'paiement_credit' => 'Paiement de crédit',
                    'versement_agent' => 'Versement agent collecteur',
                    'transfert_caisse' => 'Transfert entre caisses',
                    'frais_adhesion' => 'Frais d\'adhésion - TUMAINI LETU FINANCE',
                    default => 'Description de l\'opération'
                };
            }),

        Hidden::make('devise')
            ->default('USD'),

        // CHAMP CACHÉ pour l'opérateur (utilisateur connecté)
        Hidden::make('operateur_id')
            ->default(fn () => Auth::id()),

        // Affichage informatif de l'opérateur
        TextInput::make('operateur_info')
            ->label('Opérateur (Caissier)')
            ->disabled()
            ->dehydrated(false)
            ->default(fn () => Auth::user()->name . ' (' . Auth::user()->email . ')')
            ->columnSpanFull(),
    ]),
        ];
    }

    // MÉTHODES D'OPÉRATIONS AVEC ÉCRITURES COMPTABLES

    private static function depotVersCompte(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        
        if (!$compte) {
            throw new \Exception('Compte non trouvé');
        }

        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                              ->where('devise', $data['devise'])
                              ->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        DB::transaction(function () use ($data, $compte, $grandeCaisse) {
            // Créditer la grande caisse
            $ancienSoldeCaisse = $grandeCaisse->solde;
            $grandeCaisse->solde += $data['montant'];
            $grandeCaisse->save();

            // Créditer le compte
            $ancienSolde = $compte->solde;
            $compte->solde += $data['montant'];
            $compte->save();

            // Enregistrer le mouvement
            $mouvement = Mouvement::create([
                'compte_id' => $compte->id,
                'caisse_id' => $grandeCaisse->id,
                'type' => 'depot',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSolde,
                'solde_apres' => $compte->solde,
                'description' => $data['description'] ?? "Dépôt depuis grande caisse",
                'nom_deposant' => $data['nom_deposant'],
                'devise' => $data['devise'],
                'operateur_id' => Auth::id(),
                'numero_compte' => $compte->numero_compte,
                'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte),
                'date_mouvement' => now()
            ]);

            // Générer l'écriture comptable
            self::genererEcritureComptableDepot($mouvement, $compte, $grandeCaisse, $data);
        });
    }

    private static function retraitDepuisCompte(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        
        if (!$compte) {
            throw new \Exception('Compte non trouvé');
        }

        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                              ->where('devise', $data['devise'])
                              ->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        // Validation stricte du retrait
        $soldeDisponible = Mouvement::getSoldeDisponible($compte->id);
        if ($data['montant'] > $soldeDisponible) {
            throw new \Exception('Retrait impossible - Montant supérieur au solde disponible');
        }

        DB::transaction(function () use ($data, $compte, $grandeCaisse) {
            // Débiter la grande caisse
            $ancienSoldeCaisse = $grandeCaisse->solde;
            $grandeCaisse->solde -= $data['montant'];
            $grandeCaisse->save();

            // Débiter le compte
            $ancienSolde = $compte->solde;
            $compte->solde -= $data['montant'];
            $compte->save();

            // Enregistrer le mouvement
            $mouvement = Mouvement::create([
                'compte_id' => $compte->id,
                'caisse_id' => $grandeCaisse->id,
                'type' => 'retrait',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSolde,
                'solde_apres' => $compte->solde,
                'description' => $data['description'] ?? "Retrait vers grande caisse",
                'nom_deposant' => $data['nom_retirant'],
                'devise' => $data['devise'],
                'operateur_id' => Auth::id(),
                'numero_compte' => $compte->numero_compte,
                'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte),
                'date_mouvement' => now()
            ]);

            // Générer l'écriture comptable
            self::genererEcritureComptableRetrait($mouvement, $compte, $grandeCaisse, $data);
        });
    }

    private static function paiementCredit(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        
        if (!$compte) {
            throw new \Exception('Compte non trouvé');
        }

        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                              ->where('devise', $data['devise'])
                              ->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        // Vérifier le solde disponible
        $soldeDisponible = Mouvement::getSoldeDisponible($compte->id);
        if ($data['montant'] > $soldeDisponible) {
            throw new \Exception('Solde disponible insuffisant pour le paiement');
        }

        DB::transaction(function () use ($data, $compte, $grandeCaisse) {
            // Débiter le compte
            $ancienSolde = $compte->solde;
            $compte->solde -= $data['montant'];
            $compte->save();

            // Créditer la grande caisse
            $ancienSoldeCaisse = $grandeCaisse->solde;
            $grandeCaisse->solde += $data['montant'];
            $grandeCaisse->save();

            // Enregistrer le mouvement
            $mouvement = Mouvement::create([
                'compte_id' => $compte->id,
                'caisse_id' => $grandeCaisse->id,
                'type' => 'retrait',
                'type_mouvement' => 'paiement_credit',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSolde,
                'solde_apres' => $compte->solde,
                'description' => $data['description'] ?? "Paiement crédit",
                'nom_deposant' => $data['nom_retirant'],
                'devise' => $data['devise'],
                'operateur_id' => Auth::id(),
                'numero_compte' => $compte->numero_compte,
                'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte),
                'date_mouvement' => now()
            ]);

            // Générer l'écriture comptable
            self::genererEcritureComptablePaiementCredit($mouvement, $compte, $grandeCaisse, $data);
        });
    }

    private static function versementAgentCollecteur(array $data)
    {
        $agent = User::find($data['agent_id']);
        
        if (!$agent) {
            throw new \Exception('Agent non trouvé');
        }

        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                              ->where('devise', $data['devise'])
                              ->first();

        if (!$grandeCaisse) {
            throw new \Exception("Aucune grande caisse en {$data['devise']} trouvée");
        }

        DB::transaction(function () use ($data, $agent, $grandeCaisse) {
            // Vérifier ou créer le compte transitoire de l'agent
            $compteAgent = CompteTransitoire::where('user_id', $data['agent_id'])
                                            ->where('devise', $data['devise'])
                                            ->first();
            
            if (!$compteAgent) {
                $compteAgent = CompteTransitoire::create([
                    'user_id' => $data['agent_id'],
                    'agent_nom' => $agent->name,
                    'devise' => $data['devise'],
                    'solde' => 0,
                    'statut' => 'actif'
                ]);
            }

            // Créditer la grande caisse
            $ancienSoldeCaisse = $grandeCaisse->solde;
            $grandeCaisse->solde += $data['montant'];
            $grandeCaisse->save();

            // Créditer le compte agent
            $ancienSoldeAgent = $compteAgent->solde;
            $compteAgent->solde += $data['montant'];
            $compteAgent->save();

            // Enregistrer le mouvement
            $mouvement = Mouvement::create([
                'compte_transitoire_id' => $compteAgent->id,
                'caisse_id' => $grandeCaisse->id,
                'type' => 'depot',
                'type_mouvement' => 'versement_agent',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSoldeAgent,
                'solde_apres' => $compteAgent->solde,
                'description' => $data['description'] ?? "Dépôt agent collecteur {$agent->name}",
                'nom_deposant' => $data['nom_deposant'],
                'operateur_id' => Auth::id(),
                'devise' => $data['devise'],
                'numero_compte' => 'AGENT-' . $agent->id,
                'client_nom' => $agent->name,
                'date_mouvement' => now()
            ]);

            // Générer l'écriture comptable
            self::genererEcritureComptableVersementAgent($mouvement, $agent, $grandeCaisse, $data);

            Notification::make()
                ->title('Dépôt agent réussi')
                ->body("Dépôt de {$data['montant']} {$data['devise']} effectué par l'agent {$agent->name}")
                ->success()
                ->send();
        });
    }

    private static function transfertEntreCaisses(array $data)
    {
        $caisseSource = Caisse::find($data['caisse_source_id']);
        $caisseDestination = Caisse::find($data['caisse_destination_id']);

        if (!$caisseSource || !$caisseDestination) {
            throw new \Exception('Caisse source ou destination non trouvée');
        }

        if ($caisseSource->solde < $data['montant']) {
            throw new \Exception('Solde insuffisant dans la caisse source');
        }

        DB::transaction(function () use ($data, $caisseSource, $caisseDestination) {
            // Débiter la caisse source
            $ancienSoldeSource = $caisseSource->solde;
            $caisseSource->solde -= $data['montant'];
            $caisseSource->save();

            // Créditer la caisse destination
            $ancienSoldeDest = $caisseDestination->solde;
            $caisseDestination->solde += $data['montant'];
            $caisseDestination->save();

            // Enregistrer les mouvements
            $mouvementSource = Mouvement::create([
                'caisse_id' => $caisseSource->id,
                'type' => 'retrait',
                'type_mouvement' => 'transfert_sortant',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSoldeSource,
                'solde_apres' => $caisseSource->solde,
                'description' => "Transfert vers {$caisseDestination->nom}",
                'nom_deposant' => $data['nom_operant'],
                'devise' => $data['devise'],
                'operateur_id' => Auth::id(),
                'numero_compte' => $caisseSource->type_caisse,
                'client_nom' => 'Transfert entre caisses',
                'date_mouvement' => now()
            ]);

            $mouvementDest = Mouvement::create([
                'caisse_id' => $caisseDestination->id,
                'type' => 'depot',
                'type_mouvement' => 'transfert_entrant',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSoldeDest,
                'solde_apres' => $caisseDestination->solde,
                'description' => "Transfert depuis {$caisseSource->nom}",
                'nom_deposant' => $data['nom_operant'],
                'devise' => $data['devise'],
                'operateur_id' => Auth::id(),
                'numero_compte' => $caisseDestination->type_caisse,
                'client_nom' => 'Transfert entre caisses',
                'date_mouvement' => now()
            ]);

            // Générer l'écriture comptable
            self::genererEcritureComptableTransfert($mouvementSource, $mouvementDest, $caisseSource, $caisseDestination, $data);
        });
    }



private static function retraitDepuisCompteEpargne(array $data)
{
    $compteEpargne = CompteEpargne::find($data['compte_epargne_id']);
    
    if (!$compteEpargne) {
        throw new \Exception('Compte épargne non trouvé');
    }

    $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                          ->where('devise', $data['devise'])
                          ->first();

    if (!$grandeCaisse) {
        throw new \Exception('Aucune grande caisse trouvée');
    }

    // Validation du retrait
    $soldeMinimum = $compteEpargne->solde_minimum ?? 0;
    $retraitMaximal = $compteEpargne->solde - $soldeMinimum;
    
    if ($data['montant'] > $retraitMaximal) {
        throw new \Exception('Retrait impossible - Le solde minimum doit être maintenu');
    }

    if ($data['montant'] > $compteEpargne->solde) {
        throw new \Exception('Solde insuffisant');
    }

    DB::transaction(function () use ($data, $compteEpargne, $grandeCaisse) {
        // Débiter la grande caisse
        $ancienSoldeCaisse = $grandeCaisse->solde;
        $grandeCaisse->solde -= $data['montant'];
        $grandeCaisse->save();

        // Débiter le compte épargne
        $ancienSoldeEpargne = $compteEpargne->solde;
        $compteEpargne->solde -= $data['montant'];
        $compteEpargne->save();

        // Enregistrer le mouvement
        $mouvement = Mouvement::create([
            'compte_epargne_id' => $compteEpargne->id,
            'caisse_id' => $grandeCaisse->id,
            'type' => 'retrait',
            'type_mouvement' => 'retrait_epargne',
            'montant' => $data['montant'],
            'solde_avant' => $ancienSoldeEpargne,
            'solde_apres' => $compteEpargne->solde,
            'description' => $data['description'] ?? "Retrait depuis compte épargne",
            'nom_deposant' => $data['nom_retirant'],
            'devise' => $data['devise'],
            'operateur_id' => Auth::id(),
            'numero_compte' => $compteEpargne->numero_compte,
            'client_nom' => $data['client_epargne_nom_complet'] ?? self::getNomCompletCompteEpargne($compteEpargne),
            'date_mouvement' => now()
        ]);

        // Générer l'écriture comptable
        self::genererEcritureComptableRetraitEpargne($mouvement, $compteEpargne, $grandeCaisse, $data);

        Notification::make()
            ->title('Retrait épargne réussi')
            ->body("Retrait de {$data['montant']} {$data['devise']} effectué depuis le compte épargne {$compteEpargne->numero_compte}")
            ->success()
            ->send();
    });
}

private static function genererEcritureComptableRetraitEpargne($mouvement, $compteEpargne, $caisse, $data)
{
    $journal = JournalComptable::where('type_journal', 'caisse')->first();
    
    if (!$journal) {
        throw new \Exception('Journal de caisse non trouvé');
    }

    $reference = 'RET-EP-' . now()->format('Ymd-His');

    // Débit: Compte épargne (compte 109)
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'retrait_epargne',
        'compte_number' => '412000', // Compte épargne
        'libelle' => "Retrait compte épargne {$compteEpargne->numero_compte} - {$data['description']}",
        'montant_debit' => $data['montant'],
        'montant_credit' => 0,
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);

    // Crédit: Compte de la caisse
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'retrait_epargne',
        'compte_number' => '571100', // Compte caisse
        'libelle' => "Retrait compte épargne {$compteEpargne->numero_compte} - {$data['description']}",
        'montant_debit' => 0,
        'montant_credit' => $data['montant'],
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);
}

private static function getNomCompletCompteEpargne(CompteEpargne $compteEpargne): string
{
    if ($compteEpargne->type_compte === 'groupe_solidaire') {
        return $compteEpargne->groupeSolidaire->nom_groupe ?? $compteEpargne->numero_compte . ' (Groupe)';
    } else {
        return $compteEpargne->client->nom_complet ?? $compteEpargne->numero_compte . ' (Individuel)';
    }
}


   private static function achatCarnetLivre(array $data)
{
    $devise = $data['devise_achat'] ?? 'USD';
    
    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                    ->where('devise', $devise)
                    ->first();

    if (!$caisse) {
        throw new \Exception("Aucune grande caisse trouvée pour la devise {$devise}");
    }

    // Calculer le montant total si nécessaire
    $montant = $data['montant'];
    if (isset($data['prix_unitaire']) && isset($data['quantite_achat'])) {
        $montant = $data['prix_unitaire'] * $data['quantite_achat'];
    }

    DB::transaction(function () use ($data, $caisse, $devise, $montant) {
        // Créditer la grande caisse
        $ancienSoldeCaisse = $caisse->solde;
        $caisse->solde += $montant;
        $caisse->save();

        // Enregistrer l'achat
        $achat = AchatFourniture::create([
            'type_achat' => $data['type_achat'],
            'quantite' => $data['quantite_achat'] ?? 1,
            'prix_unitaire' => $data['prix_unitaire'] ?? $montant,
            'montant_total' => $montant,
            'devise' => $devise,
            'fournisseur' => $data['fournisseur_achat'],
            'description' => $data['description'] ?? "Achat {$data['type_achat']} - {$data['fournisseur_achat']}",
            'operateur_id' => Auth::id(),
            'date_achat' => now(),
            'reference' => 'ACH-' . now()->format('YmdHis')
        ]);

        // Enregistrer le mouvement
        $mouvement = Mouvement::create([
            'caisse_id' => $caisse->id,
            'type' => 'depot',
            'type_mouvement' => 'achat_carnet_livre',
            'montant' => $montant,
            'solde_avant' => $ancienSoldeCaisse,
            'solde_apres' => $caisse->solde,
            'description' => $data['description'] ?? "Achat {$data['type_achat']} - {$data['fournisseur_achat']} - Quantité: " . ($data['quantite_achat'] ?? 1),
            'nom_deposant' => $data['fournisseur_achat'],
            'devise' => $devise,
            'operateur_id' => Auth::id(),
            'numero_compte' => 'ACHAT',
            'client_nom' => $data['fournisseur_achat'],
            'date_mouvement' => now()
        ]);

        // === NOUVEAU : GESTION DU COMPTE SPÉCIAL ===
        
        // 1. Trouver ou créer le compte spécial pour les achats
        $compteSpecial = \App\Models\CompteSpecial::where('nom', 'like', '%achat%')
            ->where('devise', $devise)
            ->first();

        if (!$compteSpecial) {
            $compteSpecial = \App\Models\CompteSpecial::create([
                'nom' => 'Compte Achats Carnets/Livres',
                'solde' => 0,
                'devise' => $devise
            ]);
        }

        // 2. Mettre à jour le solde du compte spécial
        $ancienSoldeSpecial = $compteSpecial->solde;
        $compteSpecial->solde += $montant;
        $compteSpecial->save();

        // 3. Créer l'historique du compte spécial
        \App\Models\HistoriqueCompteSpecial::create([
            'client_nom' => $data['fournisseur_achat'] ?? 'Fournisseur',
            'montant' => $montant,
            'devise' => $devise,
            'description' => $data['description'] ?? "Achat {$data['type_achat']} - {$data['fournisseur_achat']} - Quantité: " . ($data['quantite_achat'] ?? 1),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // === FIN NOUVEAU CODE ===

        // Générer l'écriture comptable
        self::genererEcritureComptableAchat($mouvement, $achat, $caisse, $data);

        Notification::make()
            ->title('Achat enregistré')
            ->body("Achat de {$data['type_achat']} pour {$montant} {$devise} - La caisse a été créditée et le compte spécial mis à jour")
            ->success()
            ->send();
    });
}

// Vérifier que cette méthode existe dans ManageTresorerie

    // MÉTHODES POUR GÉNÉRER LES ÉCRITURES COMPTABLES


    // Vérifier que cette méthode existe aussi
private static function genererEcritureComptableAchat($mouvement, $achat, $caisse, $data)
{
    $journal = JournalComptable::where('type_journal', 'caisse')->first();
    
    if (!$journal) {
        throw new \Exception('Journal de caisse non trouvé');
    }

    $reference = 'ACH-' . now()->format('Ymd-His');

    // Débit: Compte de la caisse
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'achat_carnet_livre',
        'compte_number' => '571100', // Compte caisse
        'libelle' => "Achat {$data['type_achat']} - {$data['description']}",
        'montant_debit' => $data['montant'],
        'montant_credit' => 0,
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise_achat'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);

    // Crédit: Compte de revenus achats
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'achat_carnet_livre',
        'compte_number' => '701000', // Compte revenus achats
        'libelle' => "Achat {$data['type_achat']} - {$data['description']}",
        'montant_debit' => 0,
        'montant_credit' => $data['montant'],
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise_achat'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);
}
    private static function genererEcritureComptableDepot($mouvement, $compte, $caisse, $data)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }

        $reference = 'DEP-' . now()->format('Ymd-His');

        // Débit: Compte de la caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depot_compte',
            'compte_number' => '571100', // Compte caisse
            'libelle' => "Dépôt compte {$compte->numero_compte} - {$data['description']}",
            'montant_debit' => $data['montant'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte du membre
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depot_compte',
            'compte_number' => '411000', // Compte membres
            'libelle' => "Dépôt compte {$compte->numero_compte} - {$data['description']}",
            'montant_debit' => 0,
            'montant_credit' => $data['montant'],
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    private static function genererEcritureComptableRetrait($mouvement, $compte, $caisse, $data)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }

        $reference = 'RET-' . now()->format('Ymd-His');

        // Débit: Compte du membre
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'retrait_compte',
            'compte_number' => '411000', // Compte membres
            'libelle' => "Retrait compte {$compte->numero_compte} - {$data['description']}",
            'montant_debit' => $data['montant'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte de la caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'retrait_compte',
            'compte_number' => '571100', // Compte caisse
            'libelle' => "Retrait compte {$compte->numero_compte} - {$data['description']}",
            'montant_debit' => 0,
            'montant_credit' => $data['montant'],
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    private static function genererEcritureComptablePaiementCredit($mouvement, $compte, $caisse, $data)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }

        $reference = 'PCR-' . now()->format('Ymd-His');

        // Débit: Compte du membre
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'paiement_credit',
            'compte_number' => '411000', // Compte membres
            'libelle' => "Paiement crédit compte {$compte->numero_compte} - {$data['description']}",
            'montant_debit' => $data['montant'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte de recouvrement crédit
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'paiement_credit',
            'compte_number' => '751000', // Compte recouvrement crédit
            'libelle' => "Paiement crédit compte {$compte->numero_compte} - {$data['description']}",
            'montant_debit' => 0,
            'montant_credit' => $data['montant'],
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    private static function genererEcritureComptableVersementAgent($mouvement, $agent, $caisse, $data)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }

        $reference = 'VAG-' . now()->format('Ymd-His');

        // Débit: Compte de la caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'versement_agent',
            'compte_number' => '571100', // Compte caisse
            'libelle' => "Versement agent {$agent->name} - {$data['description']}",
            'montant_debit' => $data['montant'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte agents collecteurs
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'versement_agent',
            'compte_number' => '455000', // Compte agents
            'libelle' => "Versement agent {$agent->name} - {$data['description']}",
            'montant_debit' => 0,
            'montant_credit' => $data['montant'],
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    private static function genererEcritureComptableTransfert($mouvementSource, $mouvementDest, $caisseSource, $caisseDest, $data)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }

        $reference = 'TRF-' . now()->format('Ymd-His');

        // Débit: Compte caisse destination
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'transfert_caisse',
            'compte_number' => '571200', // Compte caisse destination
            'libelle' => "Transfert depuis {$caisseSource->nom} - {$data['description']}",
            'montant_debit' => $data['montant'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte caisse source
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'transfert_caisse',
            'compte_number' => '571100', // Compte caisse source
            'libelle' => "Transfert vers {$caisseDest->nom} - {$data['description']}",
            'montant_debit' => 0,
            'montant_credit' => $data['montant'],
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }




    // private static function genererEcritureComptableAchat($mouvement, $achat, $caisse, $data)
    // {
    //     $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
    //     if (!$journal) {
    //         throw new \Exception('Journal de caisse non trouvé');
    //     }

    //     $reference = 'ACH-' . now()->format('Ymd-His');

    //     // Débit: Compte de la caisse
    //     EcritureComptable::create([
    //         'journal_comptable_id' => $journal->id,
    //         'reference_operation' => $reference,
    //         'type_operation' => 'achat_carnet_livre',
    //         'compte_number' => '571100', // Compte caisse
    //         'libelle' => "Achat {$data['type_achat']} - {$data['description']}",
    //         'montant_debit' => $data['montant'],
    //         'montant_credit' => 0,
    //         'date_ecriture' => now(),
    //         'date_valeur' => now(),
    //         'devise' => $data['devise_achat'],
    //         'statut' => 'comptabilise',
    //         'created_by' => Auth::id(),
    //     ]);

    //     // Crédit: Compte de revenus achats
    //     EcritureComptable::create([
    //         'journal_comptable_id' => $journal->id,
    //         'reference_operation' => $reference,
    //         'type_operation' => 'achat_carnet_livre',
    //         'compte_number' => '701000', // Compte revenus achats
    //         'libelle' => "Achat {$data['type_achat']} - {$data['description']}",
    //         'montant_debit' => 0,
    //         'montant_credit' => $data['montant'],
    //         'date_ecriture' => now(),
    //         'date_valeur' => now(),
    //         'devise' => $data['devise_achat'],
    //         'statut' => 'comptabilise',
    //         'created_by' => Auth::id(),
    //     ]);
    // }

    private static function genererEcritureComptableDelaisage($montant, $devise, $reference, $motif)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }
        
        // Débit: Compte de transit trésorerie
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'delaisage_tresorerie',
            'compte_number' => '511100', // Compte de transit trésorerie
            'libelle' => "Délaistage trésorerie - {$motif}",
            'montant_debit' => $montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte des caisses (regroupement)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'delaisage_tresorerie',
            'compte_number' => '571100', // Compte principal des caisses
            'libelle' => "Délaistage trésorerie - {$motif}",
            'montant_debit' => 0,
            'montant_credit' => $montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Méthode pour obtenir le nom complet du client
     */
    private static function getNomCompletClient(Compte $compte): string
    {
        $nomComplet = $compte->nom;
        if ($compte->type_compte === 'groupe_solidaire') {
            $nomComplet .= ' (Groupe)';
        } else {
            if ($compte->postnom) $nomComplet .= ' ' . $compte->postnom;
            if ($compte->prenom) $nomComplet .= ' ' . $compte->prenom;
        }
        return $nomComplet;
    }


// MODIFIEZ LA MÉTHODE effectuerConversionDevises AVEC DES LOGS
private static function effectuerConversionDevises(array $data)
{
    Log::info('=== DÉBUT CONVERSION DEVISES ===');
    Log::info('Données reçues:', $data);
    
    $caisseSource = Caisse::find($data['caisse_source_id']);
    $caisseDestination = Caisse::find($data['caisse_destination_id']);

    if (!$caisseSource || !$caisseDestination) {
        throw new \Exception('Caisse source ou destination non trouvée');
    }

    // VALIDATION DU SOLDE SOURCE
    if ($caisseSource->solde < $data['montant_source']) {
        Log::error('Solde insuffisant:', [
            'solde_caisse_source' => $caisseSource->solde,
            'montant_source' => $data['montant_source']
        ]);
        throw new \Exception('Solde insuffisant dans la caisse source');
    }

    if ($caisseSource->devise === $caisseDestination->devise) {
        throw new \Exception('Les devises source et destination doivent être différentes');
    }

    DB::transaction(function () use ($data, $caisseSource, $caisseDestination) {
        $montantSource = $data['montant_source'];
        $montantDestination = $data['montant_destination_value'];
        $reference = 'CONV-' . now()->format('Ymd-His');

        Log::info('Avant transaction:', [
            'solde_source_avant' => $caisseSource->solde,
            'solde_dest_avant' => $caisseDestination->solde,
            'montant_source' => $montantSource,
            'montant_destination' => $montantDestination,
            'operation' => "{$caisseSource->devise} -> {$caisseDestination->devise}"
        ]);

        // DÉBITER LA CAISSE SOURCE
        $ancienSoldeSource = $caisseSource->solde;
        $caisseSource->solde -= $montantSource;
        $caisseSource->save();

        // CRÉDITER LA CAISSE DESTINATION
        $ancienSoldeDest = $caisseDestination->solde;
        $caisseDestination->solde += $montantDestination;
        $caisseDestination->save();

        Log::info('Après transaction:', [
            'solde_source_apres' => $caisseSource->solde,
            'solde_dest_apres' => $caisseDestination->solde
        ]);

        // Enregistrer les mouvements
        $mouvementSource = Mouvement::create([
            'caisse_id' => $caisseSource->id,
            'type' => 'retrait',
            'type_mouvement' => 'conversion_devise_sortant',
            'montant' => $montantSource,
            'solde_avant' => $ancienSoldeSource,
            'solde_apres' => $caisseSource->solde,
            'description' => "Conversion vers {$caisseDestination->devise} - Taux: {$data['taux_change']} - {$data['motif_conversion']}",
            'nom_deposant' => 'Système Conversion',
            'devise' => $caisseSource->devise,
            'operateur_id' => Auth::id(),
            'numero_compte' => $caisseSource->type_caisse,
            'client_nom' => 'Conversion devises',
            'date_mouvement' => now()
        ]);

        $mouvementDest = Mouvement::create([
            'caisse_id' => $caisseDestination->id,
            'type' => 'depot',
            'type_mouvement' => 'conversion_devise_entrant',
            'montant' => $montantDestination,
            'solde_avant' => $ancienSoldeDest,
            'solde_apres' => $caisseDestination->solde,
            'description' => "Conversion depuis {$caisseSource->devise} - Taux: {$data['taux_change']} - {$data['motif_conversion']}",
            'nom_deposant' => 'Système Conversion',
            'devise' => $caisseDestination->devise,
            'operateur_id' => Auth::id(),
            'numero_compte' => $caisseDestination->type_caisse,
            'client_nom' => 'Conversion devises',
            'date_mouvement' => now()
        ]);

        // Générer l'écriture comptable (APPEL CORRIGÉ)
        self::genererEcritureComptableConversion($mouvementSource, $mouvementDest, $caisseSource, $caisseDestination, $data);
        
        Log::info('=== FIN CONVERSION DEVISES - SUCCÈS ===');
    });
}


private static function prelevementFraisAdhesion(array $data)
{
    $compte = Compte::find($data['compte_id_adhesion']);
    
    if (!$compte) {
        throw new \Exception('Compte non trouvé');
    }

    // Validation du solde disponible
    $soldeDisponible = Mouvement::getSoldeDisponible($compte->id);
    if ($data['montant'] > $soldeDisponible) {
        throw new \Exception('Solde disponible insuffisant pour les frais d\'adhésion');
    }

    DB::transaction(function () use ($data, $compte) {
        // Débiter le compte membre
        $ancienSolde = $compte->solde;
        $compte->solde -= $data['montant'];
        $compte->save();

        // Enregistrer le mouvement (sans affecter la caisse)
        $mouvement = Mouvement::create([
            'compte_id' => $compte->id,
            'type' => 'retrait',
            'type_mouvement' => 'frais_adhesion',
            'montant' => $data['montant'],
            'solde_avant' => $ancienSolde,
            'solde_apres' => $compte->solde,
            'description' => $data['description'] ?? "Frais d'adhésion - TUMAINI LETU FINANCE",
            'nom_deposant' => 'TUMAINI LETU FINANCE', // Nom fixe
            'devise' => $data['devise'],
            'operateur_id' => Auth::id(),
            'numero_compte' => $compte->numero_compte,
            'client_nom' => $data['client_nom_complet_adhesion'] ?? self::getNomCompletClient($compte),
            'date_mouvement' => now()
        ]);

        // Créditer le compte spécial
        self::crediterCompteSpecialAdhesion($data['montant'], $data['devise'], $compte, $data);

        // Générer l'écriture comptable
        self::genererEcritureComptableFraisAdhesion($mouvement, $compte, $data);

        Notification::make()
            ->title('Frais d\'adhésion prélevés')
            ->body("Frais d'adhésion de {$data['montant']} {$data['devise']} prélevés du compte {$compte->numero_compte}")
            ->success()
            ->send();
    });
}

private static function genererEcritureComptableFraisAdhesion($mouvement, $compte, $data)
{
    $journal = JournalComptable::where('type_journal', 'caisse')->first();
    
    if (!$journal) {
        throw new \Exception('Journal de caisse non trouvé');
    }

    $reference = 'FRA-ADH-' . now()->format('Ymd-His');

    // Débit: Compte frais d'adhésion (compte de produits)
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'frais_adhesion',
        'compte_number' => '701100', // Compte produits - frais d'adhésion
        'libelle' => "Frais d'adhésion - {$compte->numero_compte} - {$data['description']}",
        'montant_debit' => $data['montant'],
        'montant_credit' => 0,
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);

    // Crédit: Compte du membre
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'frais_adhesion',
        'compte_number' => '411000', // Compte membres
        'libelle' => "Frais d'adhésion - {$compte->numero_compte} - {$data['description']}",
        'montant_debit' => 0,
        'montant_credit' => $data['montant'],
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);
}

    // NOUVELLE MÉTHODE POUR GÉNÉRER L'ÉCRITURE COMPTABLE DE CONVERSION
private static function genererEcritureComptableConversion($mouvementSource, $mouvementDest, $caisseSource, $caisseDestination, $data)
{
    $journal = JournalComptable::where('type_journal', 'caisse')->first();
    
    if (!$journal) {
        throw new \Exception('Journal de caisse non trouvé');
    }

    $reference = 'CONV-' . now()->format('Ymd-His');
    $montantSource = $data['montant_source'];
    $montantDestination = $data['montant_destination_value'];
    $tauxChange = $data['taux_change']; // Récupéré depuis $data

    // Débit: Compte caisse destination (dans sa devise)
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'conversion_devise',
        'compte_number' => '571100', // Compte caisse destination
        'libelle' => "Conversion {$caisseSource->devise}->{$caisseDestination->devise} - Taux: {$tauxChange} - {$data['motif_conversion']}",
        'montant_debit' => $montantDestination,
        'montant_credit' => 0,
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $caisseDestination->devise,
        'taux_change' => $tauxChange,
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);

    // Crédit: Compte caisse source (dans sa devise)
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'conversion_devise',
        'compte_number' => '571100', // Compte caisse source
        'libelle' => "Conversion {$caisseSource->devise}->{$caisseDestination->devise} - Taux: {$tauxChange} - {$data['motif_conversion']}",
        'montant_debit' => 0,
        'montant_credit' => $montantSource,
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $caisseSource->devise,
        'taux_change' => $tauxChange,
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);

    // Écriture pour les différences de change si nécessaire
    $difference = abs(($montantSource * $tauxChange) - $montantDestination);
    if ($difference > 0.01) {
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'difference_change',
            'compte_number' => '668000', // Compte différences de change
            'libelle' => "Différence de change conversion {$caisseSource->devise}->{$caisseDestination->devise}",
            'montant_debit' => $difference,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $caisseDestination->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }
}

private static function crediterCompteSpecialAdhesion($montant, $devise, $compte, $data)
{
    // Trouver ou créer le compte spécial pour les frais d'adhésion
    $compteSpecial = \App\Models\CompteSpecial::where('nom', 'like', '%adhesion%')
        ->where('devise', $devise)
        ->first();

    if (!$compteSpecial) {
        $compteSpecial = \App\Models\CompteSpecial::create([
            'nom' => 'Compte Frais d\'Adhésion',
            'solde' => 0,
            'devise' => $devise
        ]);
    }

    // Créditer le compte spécial
    $ancienSoldeSpecial = $compteSpecial->solde;
    $compteSpecial->solde += $montant;
    $compteSpecial->save();

    // Enregistrer dans l'historique du compte spécial
    \App\Models\HistoriqueCompteSpecial::create([
        'client_nom' => $data['nom_client_adhesion'] ?? $data['client_nom_complet_adhesion'] ?? self::getNomCompletClient($compte),
        'montant' => $montant,
        'devise' => $devise,
        'description' => $data['description'] ?? "Frais d'adhésion - Compte: {$compte->numero_compte} - TUMAINI LETU FINANCE",
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return $compteSpecial;
}

    private static function notifierComptabilite(array $data)
    {
        // Notification à la comptabilité
        // Vous pouvez implémenter cette fonction selon vos besoins
    }
}