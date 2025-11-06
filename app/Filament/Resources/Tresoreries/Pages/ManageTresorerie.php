<?php
// app/Filament/Resources/TresorerieResource/Pages/ManageTresorerie.php

namespace App\Filament\Resources\TresorerieResource\Pages;

use App\Filament\Resources\Tresoreries\TresorerieResource;
use App\Models\Caisse;
use App\Models\Compte;
use App\Models\Mouvement;
use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\CompteTransitoire;
use App\Models\Depense;
use App\Models\User;
use App\Models\AchatFourniture;
use App\Models\PaiementSalaire;
use App\Models\JournalComptable;
use App\Models\EcritureComptable;
use Filament\Actions;
// use Filament\Actions\Action;
// use Filament\Forms\Components\Hidden;
// use Filament\Forms\Components\Select;
// use Filament\Forms\Components\Textarea;
// use Filament\Forms\Components\TextInput;
// use Filament\Notifications\Notification;
// use Filament\Forms\Components\TextInput;
// use Filament\Resources\Pages\ManageRecords;
// use Filament\Forms\Components\Grid;
// use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageTresorerie extends ManageRecords
{
    protected static string $resource = TresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [

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
                                    $caisses = Caisse::where('devise', $state)->get();
                                    $totalSoldes = $caisses->sum('solde');
                                    $set('total_soldes_display', number_format($totalSoldes, 2) . ' ' . $state);
                                    
                                    $infoCaisses = "**Caisses en {$state}:**\n";
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
                            ->label('Détail des Caisses')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'bg-gray-50 border-gray-200']),
                            
                        Textarea::make('motif_delaisage')
                            ->label('Motif du Délaistage')
                            ->required()
                            ->placeholder('Ex: Délaistage quotidien - Fin de journée')
                            ->default('Délaistage automatique des caisses vers comptabilité'),
                    ])
                    ->action(function (array $data) {
                        try {
                            DB::transaction(function () use ($data) {
                                $devise = $data['devise_delaisage'];
                                $caisses = Caisse::where('devise', $devise)->get();
                                
                                if ($caisses->isEmpty()) {
                                    throw new \Exception("Aucune caisse trouvée pour la devise {$devise}");
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
                                        ->body("{$totalTransfert} {$devise} transférés vers la comptabilité")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Aucun transfert')
                                        ->body("Aucun solde à transférer pour la devise {$devise}")
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
                    ->modalHeading('Délaistage Trésorerie')
                    ->modalDescription('Êtes-vous sûr de vouloir transférer tous les soldes des caisses vers la comptabilité ?')
                    ->visible(fn () => Auth::user()->can('view_compte')),

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
                                case 'depense_diverse':
                                    self::depenseDiverse($data);
                                    break;
                                case 'paiement_salaire':
                                    self::paiementSalaire($data);
                                    break;
                                case 'achat_carnet_livre':
                                    self::achatCarnetLivre($data);
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
            
            Actions\CreateAction::make()
                ->label('Nouvelle Caisse')
                ->icon('heroicon-o-plus'),
            
            Action::make('rapport_journalier')
                ->label('Rapport Journalier')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->action(function () {
                    try {
                        $rapport = app(\App\Services\TresorerieService::class)->genererRapportFinJournee();
                        
                        Notification::make()
                            ->title('Rapport généré')
                            ->body('Rapport journalier créé avec succès')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body('Erreur: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Générer Rapport Journalier')
                ->modalDescription('Êtes-vous sûr de vouloir générer le rapport de fin de journée ?')
                ->visible(fn () => Auth::user()->can('view_compte')),
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
                            'retrait_compte' => 'Retrait depuis Compte Membre',
                            'paiement_credit' => 'Paiement de Crédit',
                            'versement_agent' => 'Versement Agent Collecteur',
                            'transfert_caisse' => 'Transfert entre Caisses',
                            'depense_diverse' => 'Dépense Diverse',
                            'paiement_salaire' => 'Paiement Salaire/Charges',
                            'achat_carnet_livre' => 'Achat Carnet et Livres',
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

            // Section pour les dépenses diverses - MODIFIÉE
            Section::make('Détails de la Dépense')
                ->schema([
                    Select::make('categorie_depense')
                        ->label('Catégorie de Dépense')
                        ->options([
                            'frais_bureau' => 'Frais de Bureau',
                            'transport' => 'Transport',
                            'communication' => 'Communication',
                            'entretien' => 'Entretien',
                            'fournitures' => 'Fournitures',
                            'autres' => 'Autres Dépenses',
                        ])
                        ->required(function ($get) {
                            return $get('type_operation') === 'depense_diverse';
                        }),

                    Select::make('devise_depense')
                        ->label('Devise de la Dépense')
                        ->options([
                            'USD' => 'USD',
                            'CDF' => 'CDF',
                        ])
                        ->default('USD')
                        ->required(function ($get) {
                            return $get('type_operation') === 'depense_diverse';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                ->where('devise', $state)
                                                ->first();
                                if ($caisse) {
                                    $set('solde_caisse_depense_display', number_format($caisse->solde, 2) . ' ' . $state);
                                    $set('nom_caisse_depense', $caisse->nom);
                                } else {
                                    $set('solde_caisse_depense_display', '0.00 ' . $state);
                                    $set('nom_caisse_depense', 'Non disponible');
                                }
                            }
                        }),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('nom_caisse_depense')
                                ->label('Caisse Utilisée')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $devise = $get('devise_depense') ?? 'USD';
                                    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                    ->where('devise', $devise)
                                                    ->first();
                                    return $caisse ? $caisse->nom : 'Grande Caisse ' . $devise;
                                }),

                            TextInput::make('solde_caisse_depense_display')
                                ->label('Solde Disponible')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $devise = $get('devise_depense') ?? 'USD';
                                    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                    ->where('devise', $devise)
                                                    ->first();
                                    return $caisse ? number_format($caisse->solde, 2) . ' ' . $devise : '0.00 ' . $devise;
                                }),
                        ])
                        ->visible(function ($get) {
                            return $get('type_operation') === 'depense_diverse' && $get('devise_depense');
                        }),

                    TextInput::make('beneficiaire_depense')
                        ->label('Bénéficiaire')
                        ->required(function ($get) {
                            return $get('type_operation') === 'depense_diverse';
                        })
                        ->placeholder('Nom du bénéficiaire de la dépense'),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'depense_diverse';
                }),

            // Section pour le paiement des salaires et charges - MODIFIÉE
            Section::make('Paiement des Salaires et Charges')
                ->schema([
                    Select::make('type_charge')
                        ->label('Type de Charge')
                        ->options([
                            'salaire' => 'Salaire',
                            'transport' => 'Frais de Transport',
                            'communication' => 'Frais de Communication',
                            'prime' => 'Prime',
                            'avance' => 'Avance sur Salaire',
                            'autres' => 'Autres Charges',
                        ])
                        ->required(function ($get) {
                            return $get('type_operation') === 'paiement_salaire';
                        })
                        ->default('salaire'),

                    Select::make('devise_salaire')
                        ->label('Devise du Paiement')
                        ->options([
                            'USD' => 'USD',
                            'CDF' => 'CDF',
                        ])
                        ->default('USD')
                        ->required(function ($get) {
                            return $get('type_operation') === 'paiement_salaire';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                ->where('devise', $state)
                                                ->first();
                                if ($caisse) {
                                    $set('solde_caisse_salaire_display', number_format($caisse->solde, 2) . ' ' . $state);
                                    $set('nom_caisse_salaire', $caisse->nom);
                                } else {
                                    $set('solde_caisse_salaire_display', '0.00 ' . $state);
                                    $set('nom_caisse_salaire', 'Non disponible');
                                }
                            }
                        }),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('nom_caisse_salaire')
                                ->label('Caisse Utilisée')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $devise = $get('devise_salaire') ?? 'USD';
                                    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                    ->where('devise', $devise)
                                                    ->first();
                                    return $caisse ? $caisse->nom : 'Grande Caisse ' . $devise;
                                }),

                            TextInput::make('solde_caisse_salaire_display')
                                ->label('Solde Disponible')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $devise = $get('devise_salaire') ?? 'USD';
                                    $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                    ->where('devise', $devise)
                                                    ->first();
                                    return $caisse ? number_format($caisse->solde, 2) . ' ' . $devise : '0.00 ' . $devise;
                                }),
                        ])
                        ->visible(function ($get) {
                            return $get('type_operation') === 'paiement_salaire' && $get('devise_salaire');
                        }),

                    TextInput::make('periode_paiement')
                        ->label('Période de Paiement')
                        ->placeholder('Ex: Novembre 2024')
                        ->required(function ($get) {
                            return $get('type_operation') === 'paiement_salaire';
                        }),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'paiement_salaire';
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

            // Section pour les détails de l'opération - MODIFIÉE
           // Section pour les détails de l'opération - MODIFIÉE
Section::make('Détails de l\'Opération')
    ->schema([
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

        // CHAMP POUR LE NOM DU RETIRANT (visible pour retrait et paiement crédit)
        TextInput::make('nom_retirant')
            ->label('Nom du Retirant')
            ->required(function ($get) {
                return in_array($get('type_operation'), ['retrait_compte', 'paiement_credit']);
            })
            ->placeholder('Saisir le nom de la personne qui retire')
            ->visible(function ($get) {
                return in_array($get('type_operation'), ['retrait_compte', 'paiement_credit']);
            }),

        TextInput::make('montant')
            ->label(function ($get) {
                $devise = $get('devise') ?? 'USD';
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

                        // if ($typeOperation === 'paiement_salaire') {
                        //     $devise = $get('devise_salaire') ?? 'USD';
                        //     $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                        //                     ->where('devise', $devise)
                        //                     ->first();
                        //     if ($caisse && $value > $caisse->solde) {
                        //         $fail("Solde insuffisant dans la caisse {$caisse->nom}. Maximum: " . number_format($caisse->solde, 2) . " {$devise}");
                        //     }
                        // }
                        
                        if ($typeOperation === 'retrait_compte') {
                            $compteId = $get('compte_id');
                            if ($compteId) {
                                $soldeDisponible = Mouvement::getSoldeDisponible($compteId);
                                if ($value > $soldeDisponible) {
                                    $fail("Solde disponible insuffisant. Maximum: " . number_format($soldeDisponible, 2) . " USD");
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

                        if ($typeOperation === 'depense_diverse') {
                            $devise = $get('devise_depense') ?? 'USD';
                            $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                                            ->where('devise', $devise)
                                            ->first();
                            if ($caisse && $value > $caisse->solde) {
                                $fail("Solde insuffisant dans la caisse {$caisse->nom}. Maximum: " . number_format($caisse->solde, 2) . " {$devise}");
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
                    'depense_diverse' => 'Dépense diverse',
                    'paiement_salaire' => 'Paiement salaire/charges',
                    'achat_carnet_livre' => 'Achat de carnets/livres',
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

    private static function depenseDiverse(array $data)
    {
        $devise = $data['devise_depense'] ?? 'USD';
        
        $caisse = Caisse::where('type_caisse', 'like', '%grande%')
                        ->where('devise', $devise)
                        ->first();

        if (!$caisse) {
            $caisse = Caisse::where('type_caisse', 'like', '%petite%')
                            ->where('devise', $devise)
                            ->first();
        }

        if (!$caisse) {
            throw new \Exception("Aucune caisse trouvée pour la devise {$devise}");
        }

        if ($caisse->solde < $data['montant']) {
            throw new \Exception("Solde insuffisant dans la caisse {$caisse->nom}. Solde disponible: " . number_format($caisse->solde, 2) . " {$devise}");
        }

        if ($caisse->type_caisse === 'petite_caisse' && $data['montant'] > 100) {
            throw new \Exception("La petite caisse ne peut pas gérer des dépenses supérieures à 100 USD");
        }

        DB::transaction(function () use ($data, $caisse, $devise) {
            // Débiter la caisse
            $ancienSoldeCaisse = $caisse->solde;
            $caisse->solde -= $data['montant'];
            $caisse->save();

            // Enregistrer la dépense
            $depense = Depense::create([
                'caisse_id' => $caisse->id,
                'categorie' => $data['categorie_depense'],
                'montant' => $data['montant'],
                'devise' => $devise,
                'beneficiaire' => $data['beneficiaire_depense'] ?? 'Bénéficiare non spécifié',
                'description' => $data['description'] ?? "Dépense diverse - {$data['categorie_depense']}",
                'operateur_id' => Auth::id(),
                'date_depense' => now(),
                'reference' => 'DEP-' . now()->format('YmdHis')
            ]);

            // Enregistrer le mouvement
            $mouvement = Mouvement::create([
                'caisse_id' => $caisse->id,
                'type' => 'retrait',
                'type_mouvement' => 'depense_diverse',
                'montant' => $data['montant'],
                'solde_avant' => $ancienSoldeCaisse,
                'solde_apres' => $caisse->solde,
                'description' => $data['description'] ?? "Dépense diverse - {$data['categorie_depense']} - {$caisse->nom}",
                'nom_deposant' => $data['beneficiaire_depense'] ?? 'Bénéficiare non spécifié',
                'devise' => $devise,
                'operateur_id' => Auth::id(),
                'numero_compte' => 'DEPENSE',
                'client_nom' => $data['beneficiaire_depense'] ?? $data['nom_operant'],
                'date_mouvement' => now()
            ]);

            // Générer l'écriture comptable
            self::genererEcritureComptableDepense($mouvement, $depense, $caisse, $data);

            Notification::make()
                ->title('Dépense enregistrée')
                ->body("Dépense de {$data['montant']} {$devise} effectuée depuis {$caisse->nom}")
                ->success()
                ->send();
        });
    }

private static function paiementSalaire(array $data)
{
    $compte = Compte::find($data['compte_id']);
    
    if (!$compte) {
        throw new \Exception('Compte non trouvé');
    }

    $devise = $data['devise_salaire'] ?? 'USD';

    DB::transaction(function () use ($data, $compte, $devise) {
        // CRÉDITER le compte du membre pour qu'il puisse retirer l'argent
        $ancienSoldeCompte = $compte->solde;
        $nouveauSolde = $ancienSoldeCompte + $data['montant'];
        
        $compte->solde = $nouveauSolde; // CRÉDITER le compte
        $compte->save();

        // CORRECTION : S'assurer que les valeurs sont correctes pour le mouvement
        $mouvement = Mouvement::create([
            'compte_id' => $compte->id,
            'type' => 'depot', // Type dépôt pour créditer le compte
            'type_mouvement' => 'paiement_salaire',
            'montant' => $data['montant'],
            'solde_avant' => (float) $ancienSoldeCompte, // Convertir en float pour être sûr
            'solde_apres' => (float) $nouveauSolde, // Utiliser la variable calculée
            'description' => $data['description'] ?? "Paiement {$data['type_charge']} - {$data['periode_paiement']}",
            'nom_deposant' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte),
            'devise' => $devise,
            'operateur_id' => Auth::id(),
            'numero_compte' => $compte->numero_compte,
            'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte),
            'date_mouvement' => now()
        ]);

        // Enregistrer la transaction de paiement de salaire (sans caisse_id)
        $paiementSalaire = PaiementSalaire::create([
            'compte_id' => $compte->id,
            'type_charge' => $data['type_charge'],
            'montant' => $data['montant'],
            'devise' => $devise,
            'periode' => $data['periode_paiement'],
            'beneficiaire' => $data['client_nom_complet'] ?? $compte->nom,
            'description' => $data['description'] ?? "Paiement {$data['type_charge']} - {$data['periode_paiement']}",
            'operateur_id' => Auth::id(),
            'date_paiement' => now(),
            'reference' => 'SAL-' . now()->format('YmdHis')
        ]);

        // Générer l'écriture comptable avec les comptes 66 et 422
        self::genererEcritureComptableSalaire($mouvement, $compte, $data);

        // LOG pour vérification
        logger("=== VÉRIFICATION PAIEMENT SALAIRE ===");
        logger("Compte: {$compte->numero_compte}");
        logger("Ancien solde: {$ancienSoldeCompte}");
        logger("Montant crédité: {$data['montant']}");
        logger("Nouveau solde calculé: {$nouveauSolde}");
        logger("Solde compte après save: {$compte->solde}");
        logger("Mouvement - solde_avant: {$mouvement->solde_avant}");
        logger("Mouvement - solde_apres: {$mouvement->solde_apres}");

        Notification::make()
            ->title('Paiement de salaire effectué')
            ->body("Paiement de {$data['montant']} {$devise} crédité sur le compte {$compte->numero_compte} ({$compte->nom}) - Nouveau solde: {$nouveauSolde} {$devise}")
            ->success()
            ->send();
    });
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

    // MÉTHODES POUR GÉNÉRER LES ÉCRITURES COMPTABLES

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

    private static function genererEcritureComptableDepense($mouvement, $depense, $caisse, $data)
    {
        $journal = JournalComptable::where('type_journal', 'caisse')->first();
        
        if (!$journal) {
            throw new \Exception('Journal de caisse non trouvé');
        }

        $reference = 'DEP-' . now()->format('Ymd-His');
        $categorieCompte = match($data['categorie_depense']) {
            'frais_bureau' => '613100',
            'transport' => '613200',
            'communication' => '613300',
            'entretien' => '613400',
            'fournitures' => '613500',
            default => '613600' // autres
        };

        // Débit: Compte de dépense
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depense_diverse',
            'compte_number' => $categorieCompte,
            'libelle' => "Dépense {$data['categorie_depense']} - {$data['description']}",
            'montant_debit' => $data['montant'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise_depense'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte de la caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depense_diverse',
            'compte_number' => '571100', // Compte caisse
            'libelle' => "Dépense {$data['categorie_depense']} - {$data['description']}",
            'montant_debit' => 0,
            'montant_credit' => $data['montant'],
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $data['devise_depense'],
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

private static function genererEcritureComptableSalaire($mouvement, $compte, $data)
{
    $journal = JournalComptable::where('type_journal', 'caisse')->first();
    
    if (!$journal) {
        throw new \Exception('Journal de caisse non trouvé');
    }

    $reference = 'SAL-' . now()->format('Ymd-His');
    
    // Déterminer le compte de charges selon le type de charge
    $compteCharge = self::getCompteChargeSalaire($data['type_charge']);
    
    // ÉCRITURE 1: Débit des charges de personnel (compte 66)
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'paiement_salaire',
        'compte_number' => $compteCharge, // Compte 66xxx
        'libelle' => "Paiement {$data['type_charge']} - {$data['periode_paiement']} - {$compte->nom}",
        'montant_debit' => $data['montant'],
        'montant_credit' => 0,
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise_salaire'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);

    // ÉCRITURE 2: Crédit du compte personnel (compte 422)
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'paiement_salaire',
        'compte_number' => '422000', // Compte 422 - Personnel, rémunérations dues
        'libelle' => "Paiement {$data['type_charge']} - {$data['periode_paiement']} - {$compte->nom}",
        'montant_debit' => 0,
        'montant_credit' => $data['montant'],
        'date_ecriture' => now(),
        'date_valeur' => now(),
        'devise' => $data['devise_salaire'],
        'statut' => 'comptabilise',
        'created_by' => Auth::id(),
    ]);
}

private static function getCompteChargeSalaire(string $typeCharge): string
{
    return match($typeCharge) {
        'salaire' => '661100', // Appointements, salaires et commissions
        'transport' => '661800', // Autres rémunérations directes
        'communication' => '661800', // Autres rémunérations directes  
        'prime' => '661200', // Primes et gratifications
        'avance' => '661800', // Autres rémunérations directes
        'autres' => '661800', // Autres rémunérations directes
        default => '661100' // Par défaut salaires
    };
}

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

    private static function notifierComptabilite(array $data)
    {
        // Notification à la comptabilité
        // Vous pouvez implémenter cette fonction selon vos besoins
    }
}