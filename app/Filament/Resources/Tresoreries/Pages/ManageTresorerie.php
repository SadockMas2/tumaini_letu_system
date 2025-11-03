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
use Filament\Actions;
// use Filament\Actions\Action;
// use Filament\Forms\Components\Hidden;
// use Filament\Forms\Components\Select;
// use Filament\Forms\Components\Textarea;
// use Filament\Forms\Components\TextInput;
// use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
// use Filament\Forms\Components\Grid;
// use Filament\Forms\Components\Section;
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
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($set) {
                            $set('compte_id', null);
                            $set('credit_id', null);
                            $set('agent_id', null);
                            $set('categorie_depense', null);
                            $set('devise', 'USD');
                        }),
                ])
                ->columns(1),

            // Section pour les opérations liées aux comptes membres
            Section::make('Informations Compte Membre')
                ->schema([
                    Select::make('compte_id')
                        ->label('Compte Membre')
                        ->options(Compte::all()->pluck('numero_compte', 'id'))
                        ->required(function ($get) {
                            return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit']);
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $compte = Compte::find($state);
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
                                }
                            }
                        }),

                    // Affichage du nom complet du client
                    TextInput::make('client_nom_complet')
                        ->label('Nom du Client')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('')
                        ->visible(function ($get) {
                            return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit']) && $get('compte_id');
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
                            return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit']) && $get('compte_id');
                        }),
                ])
                ->visible(function ($get) {
                    return in_array($get('type_operation'), ['depot_compte', 'retrait_compte', 'paiement_credit']);
                }),

            // Section pour les paiements de crédit
            Section::make('Informations Crédit')
                ->schema([
                    Select::make('credit_id')
                        ->label('Crédit à Payer')
                        ->options(function ($get) {
                            $compteId = $get('compte_id');
                            if (!$compteId) return [];
                            
                            $compte = Compte::find($compteId);
                            if (str_starts_with($compte->numero_compte, 'GS')) {
                                return CreditGroupe::where('compte_id', $compteId)
                                    ->where('statut_demande', 'approuve')
                                    ->where('montant_total', '>', 0)
                                    ->get()
                                    ->pluck('numero_credit', 'id');
                            } else {
                                return Credit::where('compte_id', $compteId)
                                    ->where('statut_demande', 'approuve')
                                    ->where('montant_total', '>', 0)
                                    ->get()
                                    ->pluck('numero_credit', 'id');
                            }
                        })
                        ->required(function ($get) {
                            return $get('type_operation') === 'paiement_credit';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state, $get) {
                            if ($state && $get('type_operation') === 'paiement_credit') {
                                $compteId = $get('compte_id');
                                $compte = Compte::find($compteId);
                                if (!$compte) return;

                                if (str_starts_with($compte->numero_compte, 'GS')) {
                                    $credit = CreditGroupe::find($state);
                                } else {
                                    $credit = Credit::find($state);
                                }

                                if ($credit) {
                                    $set('montant_du_display', number_format($credit->montant_total, 2) . ' ' . $compte->devise);
                                }
                            }
                        }),

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
                            return $get('type_operation') === 'paiement_credit' && $get('credit_id');
                        }),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'paiement_credit';
                }),

            // Section pour les agents collecteurs - CORRIGÉE
            Section::make('Agent Collecteur')
                ->schema([
                    Select::make('agent_id')
                        ->label('Agent Collecteur')
                        ->options(User::whereHas('roles', function ($query) {
                            $query->where('name', 'AgentCollecteur');
                        })->get()->pluck('name', 'id'))
                        ->required(function ($get) {
                            return $get('type_operation') === 'versement_agent';
                        })
                        ->live()
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $compteAgent = CompteTransitoire::where('user_id', $state)->first();
                                if ($compteAgent) {
                                    $set('solde_agent_display', number_format($compteAgent->solde, 2) . ' ' . $compteAgent->devise);
                                    $set('devise', $compteAgent->devise);
                                } else {
                                    $set('solde_agent_display', '0.00 USD');
                                    $set('devise', 'USD');
                                }
                            }
                        }),

                    TextInput::make('solde_agent_display')
                        ->label('Solde Actuel de l\'Agent')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00 USD')
                        ->visible(function ($get) {
                            return $get('type_operation') === 'versement_agent' && $get('agent_id');
                        }),

                    // Sélection de devise pour le versement agent - AMÉLIORÉE
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
                                // Mettre à jour l'affichage du solde de la grande caisse selon la devise
                                $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                                                      ->where('devise', $state)
                                                      ->first();
                                if ($grandeCaisse) {
                                    $set('solde_grande_caisse_display', number_format($grandeCaisse->solde, 2) . ' ' . $state);
                                } else {
                                    $set('solde_grande_caisse_display', '0.00 ' . $state);
                                }
                            }
                        })
                        ->visible(function ($get) {
                            return $get('type_operation') === 'versement_agent';
                        }),

                    // AJOUT : Affichage du solde de la grande caisse selon la devise
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
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'versement_agent';
                }),

            // Section pour les transferts entre caisses
            Section::make('Transfert entre Caisses')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('caisse_source_id')
                                ->label('Caisse Source')
                                ->options(Caisse::all()->pluck('type_caisse', 'id'))
                                ->required(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse';
                                })
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        $caisse = Caisse::find($state);
                                        if ($caisse) {
                                            $set('solde_caisse_source_display', number_format($caisse->solde, 2) . ' ' . $caisse->devise);
                                        }
                                    }
                                }),

                            Select::make('caisse_destination_id')
                                ->label('Caisse Destination')
                                ->options(Caisse::all()->pluck('type_caisse', 'id'))
                                ->required(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse';
                                })
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        $caisse = Caisse::find($state);
                                        if ($caisse) {
                                            $set('solde_caisse_dest_display', number_format($caisse->solde, 2) . ' ' . $caisse->devise);
                                        }
                                    }
                                }),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('solde_caisse_source_display')
                                ->label('Solde Caisse Source')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                                ->visible(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse' && $get('caisse_source_id');
                                }),
                            
                            TextInput::make('solde_caisse_dest_display')
                                ->label('Solde Caisse Destination')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('0.00 USD')
                                ->visible(function ($get) {
                                    return $get('type_operation') === 'transfert_caisse' && $get('caisse_destination_id');
                                }),
                        ]),
                ])
                ->visible(function ($get) {
                    return $get('type_operation') === 'transfert_caisse';
                }),

            // Section pour les dépenses diverses
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

            // Section pour les détails de l'opération - AMÉLIORÉE
            Section::make('Détails de l\'Opération')
                ->schema([
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

                                    // // AJOUT : Validation pour versement agent
                                    // if ($typeOperation === 'versement_agent') {
                                    //     $devise = $get('devise') ?? 'USD';
                                    //     $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                                    //                           ->where('devise', $devise)
                                    //                           ->first();
                                    //     if ($grandeCaisse && $value > $grandeCaisse->solde) {
                                    //         $fail("Solde insuffisant dans la grande caisse {$devise}. Maximum: " . number_format($grandeCaisse->solde, 2) . " {$devise}");
                                    //     }
                                    // }
                                };
                            }
                        ]),

                    TextInput::make('nom_operant')
                        ->label(function ($get) {
                            $type = $get('type_operation');
                            return match ($type) {
                                'depot_compte' => 'Nom du Déposant',
                                'retrait_compte' => 'Nom du Retirant',
                                'depense_diverse' => 'Nom du Bénéficiaire',
                                'paiement_credit' => 'Nom du Payeur',
                                'versement_agent' => 'Nom de l\'Agent',
                                default => 'Nom de l\'Opérateur'
                            };
                        })
                        ->required()
                        ->placeholder(function ($get) {
                            $type = $get('type_operation');
                            return match ($type) {
                                'depot_compte' => 'Nom de la personne qui dépose',
                                'retrait_compte' => 'Nom de la personne qui retire',
                                'depense_diverse' => 'Nom du bénéficiaire',
                                'versement_agent' => 'Nom de l\'agent collecteur',
                                default => 'Nom de l\'opérateur'
                            };
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
                                'depense_diverse' => 'Dépense diverse',
                                default => 'Description de l\'opération'
                            };
                        }),

                    Hidden::make('devise')
                        ->default('USD'),

                    Hidden::make('operateur_id')
                        ->default(fn () => Auth::id()),
                ]),
        ];
    }

    // MÉTHODES D'OPÉRATIONS CORRIGÉES

    private static function depotVersCompte(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        // Vérifier le solde de la grande caisse
        if ($grandeCaisse->solde < $data['montant']) {
            throw new \Exception('Solde insuffisant dans la grande caisse');
        }

        // CORRECTION : Créditer la grande caisse (elle AUGMENTE)
        $grandeCaisse->solde += $data['montant'];
        $grandeCaisse->save();

        // Créditer le compte
        $ancienSolde = $compte->solde;
        $compte->solde += $data['montant'];
        $compte->save();

        // Enregistrer le mouvement avec toutes les informations
        Mouvement::create([
            'compte_id' => $compte->id,
            'caisse_id' => $grandeCaisse->id,
            'type' => 'depot',
            'montant' => $data['montant'],
            'solde_avant' => $ancienSolde,
            'solde_apres' => $compte->solde,
            'description' => $data['description'] ?? "Dépôt depuis grande caisse",
            'nom_deposant' => $data['nom_operant'],
             'devise' => $data['devise'],
            'operateur_id' => auth::id(),
            'numero_compte' => $compte->numero_compte,
            'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte)
        ]);
    }

    private static function retraitDepuisCompte(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        // Validation stricte du retrait
        $soldeDisponible = Mouvement::getSoldeDisponible($compte->id);
        if ($data['montant'] > $soldeDisponible) {
            throw new \Exception('Retrait impossible - Montant supérieur au solde disponible');
        }

        // CORRECTION : Débiter la grande caisse (elle DIMINUE)
        $grandeCaisse->solde -= $data['montant'];
        $grandeCaisse->save();

        // Débiter le compte
        $ancienSolde = $compte->solde;
        $compte->solde -= $data['montant'];
        $compte->save();

        // Enregistrer le mouvement avec toutes les informations
        Mouvement::create([
            'compte_id' => $compte->id,
            'caisse_id' => $grandeCaisse->id,
            'type' => 'retrait',
            'montant' => $data['montant'],
            'solde_avant' => $ancienSolde,
            'solde_apres' => $compte->solde,
            'description' => $data['description'] ?? "Retrait vers grande caisse",
            'nom_deposant' => $data['nom_operant'],
             'devise' => $data['devise'],
            'operateur_id' => Auth::id(),
            'numero_compte' => $compte->numero_compte,
            'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte)
        ]);
    }

    private static function paiementCredit(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        // Vérifier le solde disponible
        $soldeDisponible = Mouvement::getSoldeDisponible($compte->id);
        if ($data['montant'] > $soldeDisponible) {
            throw new \Exception('Solde disponible insuffisant pour le paiement');
        }

        // Débiter le compte
        $ancienSolde = $compte->solde;
        $compte->solde -= $data['montant'];
        $compte->save();

        // Créditer la grande caisse (elle AUGMENTE)
        $grandeCaisse->solde += $data['montant'];
        $grandeCaisse->save();

        // CORRECTION : Utiliser 'retrait' comme type et 'paiement_credit' comme type_mouvement
        Mouvement::create([
            'compte_id' => $compte->id,
            'caisse_id' => $grandeCaisse->id,
            'type' => 'retrait',
            'type_mouvement' => 'paiement_credit',
            'montant' => $data['montant'],
            'solde_avant' => $ancienSolde,
            'solde_apres' => $compte->solde,
            'description' => $data['description'] ?? "Paiement crédit",
            'nom_deposant' => $data['nom_operant'],
             'devise' => $data['devise'],
            'operateur_id' => Auth::id(),
            'numero_compte' => $compte->numero_compte,
            'client_nom' => $data['client_nom_complet'] ?? self::getNomCompletClient($compte),
            'date_mouvement' => now()
        ]);
    }

    // CORRECTION COMPLÈTE : versementAgentCollecteur
  // CORRECTION COMPLÈTE : versementAgentCollecteur
private static function versementAgentCollecteur(array $data)
{
    $agent = User::find($data['agent_id']);
    
    if (!$agent) {
        throw new \Exception('Agent non trouvé');
    }

    // Récupérer la grande caisse selon la devise choisie
    $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')
                          ->where('devise', $data['devise'])
                          ->first();

    if (!$grandeCaisse) {
        throw new \Exception("Aucune grande caisse en {$data['devise']} trouvée");
    }

    // Vérifier ou créer le compte transitoire de l'agent
    $compteAgent = CompteTransitoire::where('user_id', $data['agent_id'])
                                    ->where('devise', $data['devise'])
                                    ->first();
    
    if (!$compteAgent) {
        // Créer le compte transitoire dans la devise choisie
        $compteAgent = CompteTransitoire::create([
            'user_id' => $data['agent_id'],
            'agent_nom' => $agent->name,
            'devise' => $data['devise'],
            'solde' => 0,
            'statut' => 'actif'
        ]);
    }

    // CORRECTION : Les deux augmentent - PAS DE VÉRIFICATION DE SOLDE CAR ON AJOUTE DE L'ARGENT
    // Grande caisse AUGMENTE (l'agent dépose de l'argent)
    $ancienSoldeCaisse = $grandeCaisse->solde;
    $grandeCaisse->solde += $data['montant'];
    $grandeCaisse->save();

    // Compte agent AUGMENTE aussi (il a plus d'argent disponible)
    $ancienSoldeAgent = $compteAgent->solde;
    $compteAgent->solde += $data['montant'];
    $compteAgent->save();

    // Enregistrer le mouvement
    Mouvement::create([
        'compte_transitoire_id' => $compteAgent->id,
        'caisse_id' => $grandeCaisse->id,
        'type' => 'depot',
        'type_mouvement' => 'versement_agent',
        'montant' => $data['montant'],
        'solde_avant' => $ancienSoldeAgent,
        'solde_apres' => $compteAgent->solde,
        'description' => $data['description'] ?? "Dépôt agent collecteur {$agent->name}",
        'nom_deposant' => $data['nom_operant'],
        'operateur_id' => auth::id(),
        'devise' => $data['devise'],
        'numero_compte' => 'AGENT-' . $agent->id,
        'client_nom' => $agent->name,
        'date_mouvement' => now()
    ]);

    // Notification de succès
    Notification::make()
        ->title('Dépôt agent réussi')
        ->body("Dépôt de {$data['montant']} {$data['devise']} effectué par l'agent {$agent->name}")
        ->success()
        ->send();
}

    private static function depenseDiverse(array $data)
    {
        $grandeCaisse = Caisse::where('type_caisse', 'like', '%grande%')->first();

        if (!$grandeCaisse) {
            throw new \Exception('Aucune grande caisse trouvée');
        }

        if ($grandeCaisse->solde < $data['montant']) {
            throw new \Exception('Solde insuffisant dans la grande caisse');
        }

        // Débiter la grande caisse
        $ancienSoldeCaisse = $grandeCaisse->solde;
        $grandeCaisse->solde -= $data['montant'];
        $grandeCaisse->save();

        // Enregistrer la dépense
        Depense::create([
            'caisse_id' => $grandeCaisse->id,
            'categorie' => $data['categorie_depense'],
            'montant' => $data['montant'],
            'beneficiaire' => $data['beneficiaire_depense'] ?? $data['nom_operant'],
            'description' => $data['description'] ?? "Dépense diverse - {$data['categorie_depense']}",
            'operateur_id' => auth::id(),
        ]);

        // CORRECTION : Utiliser 'retrait' comme type et 'depense_diverse' comme type_mouvement
        Mouvement::create([
            'caisse_id' => $grandeCaisse->id,
            'type' => 'retrait',
            'type_mouvement' => 'depense_diverse',
            'montant' => $data['montant'],
            'solde_avant' => $ancienSoldeCaisse,
            'solde_apres' => $grandeCaisse->solde,
            'description' => $data['description'] ?? "Dépense diverse - {$data['categorie_depense']}",
            'nom_deposant' => $data['beneficiaire_depense'] ?? $data['nom_operant'],
             'devise' => $data['devise'],
            'operateur_id' => auth::id(),
            'numero_compte' => 'DEPENSE',
            'client_nom' => $data['beneficiaire_depense'] ?? $data['nom_operant'],
            'date_mouvement' => now()
        ]);
    }

    private static function transfertEntreCaisses(array $data)
    {
        $caisseSource = Caisse::find($data['caisse_source_id']);
        $caisseDestination = Caisse::find($data['caisse_destination_id']);

        if ($caisseSource->solde < $data['montant']) {
            throw new \Exception('Solde insuffisant dans la caisse source');
        }

        // Débiter la caisse source
        $caisseSource->solde -= $data['montant'];
        $caisseSource->save();

        // Créditer la caisse destination
        $caisseDestination->solde += $data['montant'];
        $caisseDestination->save();

        // CORRECTION : Utiliser 'retrait' pour le transfert sortant
        Mouvement::create([
            'caisse_id' => $caisseSource->id,
            'type' => 'retrait',
            'type_mouvement' => 'transfert_sortant',
            'montant' => $data['montant'],
            'solde_avant' => $caisseSource->solde + $data['montant'],
            'solde_apres' => $caisseSource->solde,
            'description' => "Transfert vers {$caisseDestination->type_caisse}",
            'nom_deposant' => $data['nom_operant'],
             'devise' => $data['devise'],
            'operateur_id' => Auth::id(),
            'numero_compte' => $caisseSource->type_caisse,
            'client_nom' => 'Transfert entre caisses',
            'date_mouvement' => now()
        ]);

        // CORRECTION : Utiliser 'depot' pour le transfert entrant
        Mouvement::create([
            'caisse_id' => $caisseDestination->id,
            'type' => 'depot',
            'type_mouvement' => 'transfert_entrant',
            'montant' => $data['montant'],
            'solde_avant' => $caisseDestination->solde - $data['montant'],
            'solde_apres' => $caisseDestination->solde,
            'description' => "Transfert depuis {$caisseSource->type_caisse}",
            'nom_deposant' => $data['nom_operant'],
             'devise' => $data['devise'],
            'operateur_id' => Auth::id(),
            'numero_compte' => $caisseDestination->type_caisse,
            'client_nom' => 'Transfert entre caisses',
            'date_mouvement' => now()
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