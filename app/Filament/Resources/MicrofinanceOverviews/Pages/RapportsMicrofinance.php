<?php

namespace App\Filament\Resources\MicrofinanceOverviewResource\Pages;


use App\Exports\RapportsMicrofinanceExport;
use App\Filament\Resources\MicrofinanceOverviews\MicrofinanceOverviewResource;
use App\Filament\Widgets\RapportStatsWidget;
use App\Helpers\CurrencyHelper;
use App\Models\Compte;
use App\Models\EcritureComptable;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use App\Models\Credit;
use App\Models\PaiementCredit;
use App\Models\CreditGroupe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vtiful\Kernel\Excel;

class RapportsMicrofinance extends ListRecords
{
    protected static string $resource = MicrofinanceOverviewResource::class;

    public $rapportPerformanceData = [];

    public function getTitle(): string
    {
        return 'Rapports de l\'Agence';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Credit::query()
                    ->where('statut_demande', 'approuve')
                    ->whereRaw('1 = 0') // Ne retourne aucune donnée réelle
            )
            ->columns([
                TextColumn::make('numero_compte')
                    ->label('Compte')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if ($record->type_credit === 'groupe') {
                            return $record->compte->numero_compte ?? 'N/A';
                        }
                        return $record->compte->numero_compte ?? '';
                    }),

                TextColumn::make('nom_complet')
                    ->label('Client/Groupe')
                    ->getStateUsing(function ($record) {
                        if ($record->type_credit === 'groupe') {
                            return $record->compte->nom ?? 'Groupe ' . ($record->compte->numero_compte ?? 'N/A');
                        }
                        return ($record->compte->nom ?? '') . ' ' . ($record->compte->prenom ?? '');
                    })
                    ->searchable(),

                TextColumn::make('type_credit')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => $state === 'groupe' ? 'primary' : 'success'),

                TextColumn::make('agent_name')
                    ->label('Agent')
                    ->getStateUsing(function ($record) {
                        if ($record->type_credit === 'groupe') {
                            return $record->agent->name ?? 'N/A';
                        }
                        return $record->agent->name ?? '';
                    })
                    ->searchable(),

                TextColumn::make('superviseur_name')
                    ->label('Superviseur')
                    ->getStateUsing(function ($record) {
                        if ($record->type_credit === 'groupe') {
                            return $record->superviseur->name ?? 'N/A';
                        }
                        return $record->superviseur->name ?? '';
                    })
                    ->searchable(),

                TextColumn::make('montant_accorde')
                    ->label('Montant Accordé')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('info')
                    ->sortable(),

                TextColumn::make('montant_total')
                    ->label('Montant Total')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('interets_attendus')
                    ->label('Intérêts Attendus')
                    ->getStateUsing(fn ($record) => $record->montant_total - $record->montant_accorde)
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('danger'),

                TextColumn::make('date_octroi')
                    ->label('Date Octroi')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_echeance')
                    ->label('Date Échéance')
                    ->date()
                    ->color(fn ($record) => $record->date_echeance < now() ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('total_paiements')
                    ->label('Montant Payé')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('success'),
            ])
            ->filters([
                Filter::make('type_credit')
                    ->schema([
                        Select::make('type_credit')
                            ->label('Type de Crédit')
                            ->options([
                                'individuel' => 'Individuel',
                                'groupe' => 'Groupe',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['type_credit'],
                                fn (Builder $query, $type): Builder => $query->where('type_credit', $type),
                            );
                    }),

                Filter::make('periode')
                    ->schema([
                        DatePicker::make('date_debut'),
                        DatePicker::make('date_fin'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_debut'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_octroi', '>=', $date),
                            )
                            ->when(
                                $data['date_fin'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_octroi', '<=', $date),
                            );
                    }),

                Filter::make('agent')
                    ->schema([
                        Select::make('agent_id')
                            ->label('Agent')
                            ->options(User::whereHas('roles', function ($query) {
                                $query->where('name', 'ConseillerMembres');
                            })->pluck('name', 'id')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['agent_id'],
                                fn (Builder $query, $agentId): Builder => $query->where('agent_id', $agentId),
                            );
                    }),

                Filter::make('superviseur')
                    ->schema([
                        Select::make('superviseur_id')
                            ->label('Superviseur')
                            ->options(User::whereHas('roles', function ($query) {
                                $query->where('name', 'ChefBureau');
                            })->pluck('name', 'id')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['superviseur_id'],
                                fn (Builder $query, $superviseurId): Builder => $query->where('superviseur_id', $superviseurId),
                            );
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    // Créer une méthode pour récupérer les données combinées sous forme de modèles Eloquent
    private function getCombinedCredits(): Collection
    {
        $creditsIndividuels = Credit::where('statut_demande', 'approuve')
            ->with(['compte', 'agent', 'superviseur', 'paiements'])
            ->get();

        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
            ->with(['compte', 'agent', 'superviseur'])
            ->get()
            ->map(function ($creditGroupe) {
                // Créer un modèle Credit factice pour les crédits groupe
                $credit = new Credit();
                $credit->id = $creditGroupe->id + 100000; // Offset pour éviter les conflits d'ID
                $credit->compte_id = $creditGroupe->compte_id;
                $credit->agent_id = $creditGroupe->agent_id;
                $credit->superviseur_id = $creditGroupe->superviseur_id;
                $credit->type_credit = 'groupe';
                $credit->montant_demande = $creditGroupe->montant_demande;
                $credit->montant_accorde = $creditGroupe->montant_accorde;
                $credit->montant_total = $creditGroupe->montant_total;
                $credit->date_octroi = $creditGroupe->date_octroi;
                $credit->date_echeance = $creditGroupe->date_echeance;
                $credit->created_at = $creditGroupe->created_at;
                $credit->updated_at = $creditGroupe->updated_at;
                
                // Ajouter les relations avec vérification
                if ($creditGroupe->relationLoaded('compte') && $creditGroupe->compte) {
                    $credit->setRelation('compte', $creditGroupe->compte);
                } else {
                    // Créer un compte factice si la relation n'est pas chargée
                    $compte = new Compte();
                    $compte->numero_compte = 'GS' . str_pad($creditGroupe->id, 5, '0', STR_PAD_LEFT);
                    $compte->nom = 'Groupe ' . $creditGroupe->id;
                    $credit->setRelation('compte', $compte);
                }
                
                if ($creditGroupe->relationLoaded('agent') && $creditGroupe->agent) {
                    $credit->setRelation('agent', $creditGroupe->agent);
                // } else {
                //     // Créer un agent factice si la relation n'est pas chargée
                //     $agent = new User();
                //     $agent->name = 'Agent ' . $creditGroupe->agent_id;
                //     $credit->setRelation('agent', $agent);
                }
                
                if ($creditGroupe->relationLoaded('superviseur') && $creditGroupe->superviseur) {
                    $credit->setRelation('superviseur', $creditGroupe->superviseur);
                // } else {
                //     // Créer un superviseur factice si la relation n'est pas chargée
                //     $superviseur = new User();
                //     $superviseur->name = 'Superviseur ' . $creditGroupe->superviseur_id;
                //     $credit->setRelation('superviseur', $superviseur);
                }
                
                $credit->setRelation('paiements', collect()); // Collection vide pour les paiements
                
                return $credit;
            });

        return $creditsIndividuels->merge($creditsGroupe)->sortByDesc('id');
    }

    // Surcharger la méthode pour utiliser nos données personnalisées
    public function getTableRecords(): Collection
    {
        return $this->getCombinedCredits();
    }

    // Surcharger la pagination si nécessaire
    protected function paginateTableQuery(Builder $query): Paginator
    {
        $combinedCredits = $this->getCombinedCredits();
        
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $this->getTableRecordsPerPage();
        
        $results = $combinedCredits->slice(($page - 1) * $perPage, $perPage)->values();
        
        return new LengthAwarePaginator(
            $results,
            $combinedCredits->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ]
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rapport_performance')
                ->label('Rapport Performance')
                ->color('success')
                ->icon('heroicon-m-chart-bar')
                ->modalHeading('Rapport de Performance')
                ->modalContent(view('filament.pages.rapport-performance'))
                ->modalFooterActions([
                    Action::make('fermer')
                        ->label('Fermer')
                        ->color('gray')
                        ->action(fn () => $this->closeModal()),
                ])
                ->action(fn () => $this->preparerRapportPerformance()),

                 Action::make('paiement_remboursements')
                ->label('Paiement Remboursements')
                ->color('warning')
                ->icon('heroicon-m-currency-dollar')
                ->schema([
                    Section::make('Paramètres de Paiement')
                        ->schema([
                            DatePicker::make('date_paiement')
                                ->label('Date de Paiement')
                                ->default(now())
                                ->required(),
                            Toggle::make('forcer_paiement')
                                ->label('Forcer le paiement même si solde insuffisant')
                                ->helperText('Permet de traiter les paiements partiels')
                                ->default(true),
                            Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Notes optionnelles sur ce lot de paiements...'),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->processerPaiementsRemboursements($data);
                })
                ->modalHeading('Paiement des Remboursements Hebdomadaires')
                ->modalDescription('Exécuter le paiement automatique des remboursements pour tous les crédits actifs')
                ->modalSubmitActionLabel('Exécuter les Paiements')
                ->modalCancelActionLabel('Annuler'),

          Action::make('exporter_excel')
                ->label('Exporter Excel')
                ->color('primary')
                ->icon('heroicon-m-document-arrow-down')
                ->action(fn () => $this->exporterVersExcel()),
                
            Action::make('retour')
                ->label('Retour Vue Microfinance')
                ->url(static::$resource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-m-arrow-left'),
        ];
    }

 // Dans processerPaiementsRemboursements
private function processerPaiementsRemboursements(array $data = null)
{
    DB::transaction(function () use ($data) {
        $datePaiement = now();
        $results = [];
        
        // Traiter les crédits individuels
        $creditsIndividuels = Credit::where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->with(['compte', 'paiements'])
            ->get();
            
        foreach ($creditsIndividuels as $credit) {
            $result = $this->traiterPaiementCreditIndividuel($credit, $datePaiement);
            if ($result) $results[] = $result;
        }
        
        // COMMENTEZ TEMPORAIREMENT LA PARTIE GROUPES
        /*
        // Traiter les crédits groupe
        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->with(['compte']) // Enlevez 'creditsIndividuels.compte'
            ->get();
            
        foreach ($creditsGroupe as $creditGroupe) {
            $result = $this->traiterPaiementCreditGroupe($creditGroupe, $datePaiement);
            if ($result) $results[] = $result;
        }
        */
        
        return $results;
    });
}

// Méthode pour crédits individuels
private function traiterPaiementCreditIndividuel($credit, $datePaiement)
{
    $compte = $credit->compte;
    
    // Calculer le solde disponible (hors caution)
    $soldeDisponible = $this->calculerSoldeDisponible($compte->id);
    $montantDu = $this->calculerMontantDuCetteSemaine($credit);
    
    if ($soldeDisponible <= 0) {
        return [
            'type' => 'individuel',
            'compte' => $compte->numero_compte,
            'statut' => 'echec',
            'raison' => 'Solde disponible insuffisant',
            'solde_disponible' => $soldeDisponible,
            'montant_du' => $montantDu
        ];
    }

    
    
    // Montant à prélever (le minimum entre solde disponible et montant dû)
    $montantAPrelever = min($soldeDisponible, $montantDu);
    
    // Répartir entre capital et intérêts
    $repartition = $this->repartirCapitalInterets($credit, $montantAPrelever);
    
    // Effectuer le prélèvement
    $this->effectuerPrelevement($compte, $credit, $montantAPrelever, $repartition, $datePaiement);
    
    // Vérifier si en retard
    $enRetard = $montantAPrelever < $montantDu;
    
    return [
        'type' => 'individuel',
        'compte' => $compte->numero_compte,
        'statut' => $enRetard ? 'partiel' : 'succes',
        'montant_preleve' => $montantAPrelever,
        'montant_du' => $montantDu,
        'capital' => $repartition['capital'],
        'interets' => $repartition['interets'],
        'en_retard' => $enRetard
    ];
}
private function effectuerPrelevement($compte, $credit, $montant, $repartition, $datePaiement)
{
    // Débiter le compte
    $ancienSolde = $compte->solde;
    $compte->solde -= $montant;
    $compte->save();

    // Créer le paiement avec un type plus court
 $paiement = PaiementCredit::create([
    'credit_id' => $credit->id,
    'compte_id' => $compte->id,
    'montant_paye' => $montant,
    'date_paiement' => $datePaiement,
    'type_paiement' => \App\Enums\TypePaiement::AUTOMATIQUE->value,
    'reference' => 'PAY-AUTO-' . $credit->id . '-' . now()->format('YmdHis'),
    'statut' => 'complet',
    'capital_rembourse' => $repartition['capital'],
    'interets_payes' => $repartition['interets']
]);

    // Créer le mouvement
    Mouvement::create([
        'compte_id' => $compte->id,
        'type_mouvement' => 'paiement_credit_automatique',
        'montant' => -$montant,
        'solde_avant' => $ancienSolde,
        'solde_apres' => $compte->solde,
        'description' => "Paiement automatique crédit - Capital: " . number_format($repartition['capital'], 2) . " USD, Intérêts: " . number_format($repartition['interets'], 2) . " USD",
        'reference' => $paiement->reference,
        'date_mouvement' => $datePaiement,
        'nom_deposant' => 'Système Automatique'
    ]);

    // Générer l'écriture comptable
    $this->genererEcritureComptablePaiement($compte, $credit, $montant, $repartition, $paiement->reference);
}

    private function effectuerPaiementGroupe($creditGroupe, $montantTotal, $datePaiement)
    {
        $compteGroupe = $creditGroupe->compte;
        $ancienSolde = $compteGroupe->solde;
        $compteGroupe->solde += $montantTotal;
        $compteGroupe->save();

        // Mouvement pour le groupe
        Mouvement::create([
            'compte_id' => $compteGroupe->id,
            'type_mouvement' => 'recouvrement_credit_groupe',
            'montant' => $montantTotal,
            'solde_avant' => $ancienSolde,
            'solde_apres' => $compteGroupe->solde,
            'description' => "Recouvrement crédit groupe - Total membres: " . number_format($montantTotal, 2) . " USD",
            'reference' => 'RECOUV-GRP-' . $creditGroupe->id . '-' . now()->format('YmdHis'),
            'date_mouvement' => $datePaiement,
            'nom_deposant' => 'Système Automatique'
        ]);
    }

    private function afficherResultatsPaiements($results)
    {
        $message = "Paiements exécutés avec succès!\n\n";
        $message .= "📊 **Résumé:**\n";
        $message .= "• Crédits traités: {$results['credits_traites']}\n";
        $message .= "• Total prélevé: " . number_format($results['total_preleve'], 2) . " USD\n";
        $message .= "• Crédits en retard: {$results['credits_en_retard']}\n\n";
        
        $message .= "✅ **Crédits Individuels:** " . count($results['individuels']) . "\n";
        $message .= "👥 **Crédits Groupe:** " . count($results['groupes']) . "\n";
        
        Notification::make()
            ->title('Paiements des Remboursements Terminés')
            ->body($message)
            ->success()
            ->send();
    }

// Méthode de répartition capital/intérêts
 private function repartirCapitalInterets($credit, $montantPaiement)
    {
        $capitalHebdomadaire = $credit->montant_accorde / 16;
        $montantHebdomadaireTotal = $credit->remboursement_hebdo;
        $interetHebdomadaire = $montantHebdomadaireTotal - $capitalHebdomadaire;
        
        // Si paiement complet
        if ($montantPaiement >= $montantHebdomadaireTotal) {
            return [
                'capital' => $capitalHebdomadaire,
                'interets' => $interetHebdomadaire
            ];
        }
        
        // Si paiement partiel : priorité aux intérêts
        $interetsAPayer = min($montantPaiement, $interetHebdomadaire);
        $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
        
        return [
            'capital' => $capitalAPayer,
            'interets' => $interetsAPayer
        ];
    }

// Calcul du montant dû cette semaine
 private function calculerMontantDuCetteSemaine($credit)
    {
        $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        $semaineActuelle = min($semainesEcoulees + 1, 16);
        
        // Pour la dernière semaine, montant restant
        if ($semaineActuelle == 16) {
            $totalDejaPaye = $credit->paiements->sum('montant_paye');
            return max(0, $credit->montant_total - $totalDejaPaye);
        }
        
        return $credit->remboursement_hebdo;
    }


// Calcul du solde disponible (hors caution)
   private function calculerSoldeDisponible($compteId)
    {
        $compte = Compte::find($compteId);
        $caution = DB::table('cautions')
            ->where('compte_id', $compteId)
            ->where('statut', 'bloquee')
            ->sum('montant');
        
        return max(0, $compte->solde - $caution);
    }

private function traiterPaiementCreditGroupe($creditGroupe, $datePaiement)
{
    $totalPreleve = 0;
    $detailsMembres = [];
    
    // Récupérer les membres du groupe depuis la répartition
    $repartition = $creditGroupe->repartition_membres ?? [];
    
    foreach ($repartition as $membreId => $detailsMembre) {
        $resultMembre = $this->traiterPaiementMembreGroupe($membreId, $detailsMembre, $creditGroupe, $datePaiement);
        $detailsMembres[] = $resultMembre;
        $totalPreleve += $resultMembre['montant_preleve'] ?? 0;
    }
    
    // Transférer le total vers le compte groupe
    if ($totalPreleve > 0) {
        $this->effectuerPaiementGroupe($creditGroupe, $totalPreleve, $datePaiement);
    }
    
    return [
        'type' => 'groupe',
        'compte_groupe' => $creditGroupe->compte->numero_compte,
        'total_preleve' => $totalPreleve,
        'membres' => $detailsMembres
    ];
}

private function traiterPaiementMembreGroupe($membreId, $detailsMembre, $creditGroupe, $datePaiement)
{
    // Trouver le compte du membre
    $compteMembre = Compte::where('client_id', $membreId)->first();
    
    if (!$compteMembre) {
        return [
            'compte' => 'Membre ' . $membreId,
            'montant_preleve' => 0,
            'montant_du' => $detailsMembre['remboursement_hebdo'] ?? 0,
            'statut' => 'echec',
            'raison' => 'Compte membre non trouvé'
        ];
    }
    
    // Calculer le solde disponible
    $soldeDisponible = $this->calculerSoldeDisponible($compteMembre->id);
    $montantDuMembre = $detailsMembre['remboursement_hebdo'] ?? 0;
    
    $montantAPrelever = min($soldeDisponible, $montantDuMembre);
    
    if ($montantAPrelever > 0) {
        // Créer une structure de crédit factice pour la répartition
        $creditFactice = (object)[
            'montant_accorde' => $detailsMembre['montant_accorde'] ?? 0,
            'remboursement_hebdo' => $montantDuMembre
        ];
        
        $repartition = $this->repartirCapitalInterets($creditFactice, $montantAPrelever);
        
        // Effectuer le prélèvement du membre
        $this->effectuerPrelevementMembreGroupe($compteMembre, $creditGroupe, $montantAPrelever, $repartition, $datePaiement, $membreId);
        
        return [
            'compte' => $compteMembre->numero_compte,
            'montant_preleve' => $montantAPrelever,
            'montant_du' => $montantDuMembre,
            'statut' => $montantAPrelever < $montantDuMembre ? 'partiel' : 'succes'
        ];
    } else {
        return [
            'compte' => $compteMembre->numero_compte,
            'montant_preleve' => 0,
            'montant_du' => $montantDuMembre,
            'statut' => 'echec',
            'raison' => 'Solde insuffisant'
        ];
    }
}



    private function genererEcritureComptablePaiement($compte, $credit, $montant, $repartition, $reference)
    {
        $journal = JournalComptable::where('type_journal', 'banque')->first();
        
        if (!$journal) {
            Log::warning('Journal banque non trouvé pour écriture comptable');
            return;
        }

        // Débit: Compte membre (capital)
        if ($repartition['capital'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'paiement_credit_capital',
                'compte_number' => '411000',
                'libelle' => "Remboursement capital crédit - Client: {$compte->nom}",
                'montant_debit' => $repartition['capital'],
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }

        // Débit: Compte membre (intérêts)
        if ($repartition['interets'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'paiement_credit_interets',
                'compte_number' => '411000',
                'libelle' => "Paiement intérêts crédit - Client: {$compte->nom}",
                'montant_debit' => $repartition['interets'],
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }

        // Crédit: Compte recouvrement (capital)
        if ($repartition['capital'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'recouvrement_capital',
                'compte_number' => '751100',
                'libelle' => "Recouvrement capital crédit - Client: {$compte->nom}",
                'montant_debit' => 0,
                'montant_credit' => $repartition['capital'],
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }

        // Crédit: Compte produits financiers (intérêts)
        if ($repartition['interets'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'revenus_interets',
                'compte_number' => '758100',
                'libelle' => "Revenus intérêts crédit - Client: {$compte->nom}",
                'montant_debit' => 0,
                'montant_credit' => $repartition['interets'],
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }
    }

    private function marquerCommeEnRetard($credit)
    {
        if ($credit instanceof CreditGroupe) {
            DB::table('credit_groupes')
                ->where('id', $credit->id)
                ->update([
                    'en_retard' => true,
                    'date_dernier_retard' => now(),
                    'updated_at' => now()
                ]);
        } else {
            DB::table('credits')
                ->where('id', $credit->id)
                ->update([
                    'en_retard' => true,
                    'date_dernier_retard' => now(),
                    'updated_at' => now()
                ]);
        }
    }

private function effectuerPrelevementMembreGroupe($compteMembre, $creditGroupe, $montant, $repartition, $datePaiement, $membreId)
{
    // Débiter le compte du membre
    $ancienSolde = $compteMembre->solde;
    $compteMembre->solde -= $montant;
    $compteMembre->save();

    // Créer le mouvement pour le membre
    Mouvement::create([
        'compte_id' => $compteMembre->id,
        'type_mouvement' => 'paiement_credit_groupe_membre',
        'montant' => -$montant,
        'solde_avant' => $ancienSolde,
        'solde_apres' => $compteMembre->solde,
        'description' => "Paiement crédit groupe - Capital: " . number_format($repartition['capital'], 2) . " USD, Intérêts: " . number_format($repartition['interets'], 2) . " USD - Groupe: " . $creditGroupe->compte->numero_compte,
        'reference' => 'PAY-GRP-MEMBRE-' . $creditGroupe->id . '-' . $membreId . '-' . now()->format('YmdHis'),
        'date_mouvement' => $datePaiement,
        'nom_deposant' => 'Système Automatique'
    ]);
    
    Log::info("Prélèvement membre groupe effectué", [
        'groupe_id' => $creditGroupe->id,
        'membre_id' => $membreId,
        'compte_membre' => $compteMembre->numero_compte,
        'montant' => $montant,
        'capital' => $repartition['capital'],
        'interets' => $repartition['interets']
    ]);
}
    private function preparerRapportPerformance()
    {
        $creditsIndividuels = Credit::where('statut_demande', 'approuve')->get();
        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')->get();
        
        $totalCredits = $creditsIndividuels->count() + $creditsGroupe->count();
        $totalMontantAccorde = $creditsIndividuels->sum('montant_accorde') + $creditsGroupe->sum('montant_accorde');
        $totalMontantTotal = $creditsIndividuels->sum('montant_total') + $creditsGroupe->sum('montant_total');
        $totalInteretsAttendus = $totalMontantTotal - $totalMontantAccorde;
        $totalPaiements = PaiementCredit::sum('montant_paye');

        $this->rapportPerformanceData = [
            'totalCredits' => $totalCredits,
            'totalMontantAccorde' => $totalMontantAccorde,
            'totalMontantTotal' => $totalMontantTotal,
            'totalInteretsAttendus' => $totalInteretsAttendus,
            'totalPaiements' => $totalPaiements,
            'creditsIndividuels' => $creditsIndividuels->count(),
            'creditsGroupe' => $creditsGroupe->count(),
            'montantAccordeIndividuel' => $creditsIndividuels->sum('montant_accorde'),
            'montantAccordeGroupe' => $creditsGroupe->sum('montant_accorde'),
            'montantTotalIndividuel' => $creditsIndividuels->sum('montant_total'),
            'montantTotalGroupe' => $creditsGroupe->sum('montant_total'),
        ];

        $this->rapportPerformanceData['tauxRemboursement'] = $totalMontantTotal > 0 
            ? round(($totalPaiements / $totalMontantTotal) * 100, 2)
            : 0;
    }
   private function exporterVersExcel(): StreamedResponse
    {
        $fileName = 'rapports_microfinance_' . now()->format('d_m_Y_H_i') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($handle, [
                'Numéro Compte',
                'Client/Groupe',
                'Type Crédit',
                'Agent',
                'Superviseur',
                'Montant Accordé',
                'Montant Total',
                'Intérêts Attendus',
                'Montant Payé',
                'Date Octroi',
                'Date Échéance',
                'Statut'
            ], ';');

            // Données
            $combinedCredits = $this->getCombinedCredits();
            
            foreach ($combinedCredits as $credit) {
                $totalPaiements = $credit->paiements->sum('montant_paye');
                $interetsAttendus = $credit->montant_total - $credit->montant_accorde;
                $statut = $credit->date_echeance < now() ? 'En retard' : 'En cours';
                
                $numeroCompte = $credit->type_credit === 'groupe' 
                    ? ($credit->compte->numero_compte ?? 'GS' . str_pad($credit->id, 5, '0', STR_PAD_LEFT))
                    : ($credit->compte->numero_compte ?? 'N/A');
                
                $nomComplet = $credit->type_credit === 'groupe'
                    ? ($credit->compte->nom ?? 'Groupe ' . ($credit->compte->numero_compte ?? 'N/A'))
                    : ($credit->compte->nom ?? '') . ' ' . ($credit->compte->prenom ?? '');
                
                fputcsv($handle, [
                    $numeroCompte,
                    $nomComplet,
                    $credit->type_credit === 'groupe' ? 'Groupe' : 'Individuel',
                    $credit->agent->name ?? 'N/A',
                    $credit->superviseur->name ?? 'N/A',
                    CurrencyHelper::format($credit->montant_accorde, false),
                    CurrencyHelper::format($credit->montant_total, false),
                    CurrencyHelper::format($interetsAttendus, false),
                    CurrencyHelper::format($totalPaiements, false),
                    $credit->date_octroi?->format('d/m/Y') ?? 'N/A',
                    $credit->date_echeance?->format('d/m/Y') ?? 'N/A',
                    $statut
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RapportStatsWidget::class,
        ];
    }
}