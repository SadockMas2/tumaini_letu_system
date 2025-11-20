<?php

namespace App\Filament\Resources\MicrofinanceOverviewResource\Pages;

use App\Filament\Resources\MicrofinanceOverviews\MicrofinanceOverviewResource;
use App\Filament\Widgets\RapportStatsWidget;
use App\Helpers\CurrencyHelper;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
                        \Filament\Forms\Components\DatePicker::make('date_debut'),
                        \Filament\Forms\Components\DatePicker::make('date_fin'),
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
                    $compte = new \App\Models\Compte();
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

    private function exporterVersExcel()
    {
        session()->flash('success', 'Fonctionnalité d\'export Excel à implémenter!');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RapportStatsWidget::class,
        ];
    }
}