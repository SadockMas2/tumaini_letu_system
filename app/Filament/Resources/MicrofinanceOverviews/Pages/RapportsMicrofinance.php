<?php

namespace App\Filament\Resources\MicrofinanceOverviewResource\Pages;

use App\Enums\TypePaiement;
use App\Exports\RapportsMicrofinanceExport;
use App\Filament\Resources\MicrofinanceOverviews\MicrofinanceOverviewResource;
use App\Filament\Widgets\RapportStatsWidget;
use App\Helpers\CurrencyHelper;
use App\Models\Compte;
use App\Models\CompteSpecial;
use App\Models\EcritureComptable;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vtiful\Kernel\Excel;

class RapportsMicrofinance extends ListRecords
{
    protected static string $resource = MicrofinanceOverviewResource::class;

    
    protected $listeners = ['processerPaiementsGroupe'];
    public $rapportPerformanceData = [];
    public $selectedGroupeId = null;
    public $paiementsMembres = [];

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

                

               // Dans App\Filament\Resources\MicrofinanceOverviewResource\Pages\RapportsMicrofinance.php

                TextColumn::make('total_paiements')
                    ->label('Montant Payé')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('success')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        // Si total_paiements est déjà calculé dans le modèle
                        if (isset($record->total_paiements)) {
                            return $record->total_paiements;
                        }
                        
                        // Sinon, calculer
                        if ($record->type_credit === 'individuel') {
                            return PaiementCredit::where('credit_id', $record->id)
                                ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
                                ->sum('montant_paye');
                        } else {
                            $groupeId = $record->id - 100000;
                            return PaiementCredit::where('credit_groupe_id', $groupeId)
                                ->where('type_paiement', TypePaiement::GROUPE->value)
                                ->sum('montant_paye');
                        }
                    }),

    

              

                TextColumn::make('total_paiements')
                    ->label('Montant Payé')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('success')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        // Si total_paiements est déjà calculé dans le modèle
                        if (isset($record->total_paiements)) {
                            return $record->total_paiements;
                        }
                        
                        // Sinon, calculer
                        if ($record->type_credit === 'individuel') {
                            return PaiementCredit::where('credit_id', $record->id)
                                ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
                                ->sum('montant_paye');
                        } else {
                            $groupeId = $record->id - 100000;
                            return PaiementCredit::where('credit_groupe_id', $groupeId)
                                ->where('type_paiement', TypePaiement::GROUPE->value)
                                ->sum('montant_paye');
                        }
                    }),

                // NOUVELLES COLONNES
                TextColumn::make('capital_deja_rembourse')
                    ->label('Capital Remboursé')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('info')
                    ->getStateUsing(function ($record) {
                        return $this->calculerCapitalDejaRembourseTable($record);
                    }),

                TextColumn::make('interets_deja_payes')
                    ->label('Intérêts Payés')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('warning')
                    ->getStateUsing(function ($record) {
                        return $this->calculerInteretsDejaPayesTable($record);
                    }),

                TextColumn::make('reste_a_payer')
                    ->label('Reste à Payer')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format($state))
                    ->color('danger')
                    ->getStateUsing(function ($record) {
                        $montantTotal = $record->montant_total;
                        $totalPaiements = $this->calculerTotalPaiementsPourTable($record);
                        return max(0, $montantTotal - $totalPaiements);
                    }),
                   TextColumn::make('date_octroi')
                    ->label('Date Octroi')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_echeance')
                    ->label('Date Échéance')
                    ->date()
                    ->color(fn ($record) => $record->date_echeance < now() ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('date_octroi')
                    ->label('Date Octroi')
                    ->date()
                    ->sortable(),

    
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
/**
 * Calcule le capital déjà remboursé pour l'affichage dans la table
 */
private function calculerCapitalDejaRembourseTable($credit): float
{
    if ($credit->type_credit === 'individuel') {
        // Récupérer tous les paiements
        $paiements = PaiementCredit::where('credit_id', $credit->id)
            ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
            ->get();
        
        if ($paiements->isEmpty()) {
            return 0;
        }
        
        // OPTION 1 : Si vous stockez capital_rembourse dans la table paiements
        if ($paiements->first()->capital_rembourse !== null) {
            return $paiements->sum('capital_rembourse');
        }
        
        // OPTION 2 : Calculer selon la formule fixe - CORRIGÉ
        $montantPaye = $paiements->sum('montant_paye');
        $remboursementHebdo = $credit->remboursement_hebdo ?? ($credit->montant_total / 16);
        
        if ($remboursementHebdo <= 0) return 0;
        
        // CORRECTION : Utiliser fmod() au lieu de % pour les décimaux
        $nombreEcheancesCompletes = floor($montantPaye / $remboursementHebdo);
        $reste = fmod($montantPaye, $remboursementHebdo);
        
        // Capital par échéance = montant_accorde / 16
        $capitalParEcheance = $credit->montant_accorde / 16;
        
        // Calcul du capital pour les échéances complètes
        $capitalTotal = $nombreEcheancesCompletes * $capitalParEcheance;
        
        // Intérêts par échéance
        $interetParEcheance = $remboursementHebdo - $capitalParEcheance;
        
        // Pour le reste, priorité aux intérêts
        if ($reste > 0) {
            // D'abord payer les intérêts, puis le capital
            $interetsDuReste = min($reste, $interetParEcheance);
            $capitalDuReste = max(0, $reste - $interetsDuReste);
            $capitalTotal += $capitalDuReste;
        }
        
        // Arrondir à 2 décimales
        return round($capitalTotal, 2);
        
    } else {
        // Pour les groupes - CORRIGÉ
        $groupeId = $credit->id - 100000;
        $paiements = PaiementCredit::where('credit_groupe_id', $groupeId)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->get();
        
        if ($paiements->isEmpty()) {
            return 0;
        }
        
        $montantPaye = $paiements->sum('montant_paye');
        $remboursementHebdo = $credit->remboursement_hebdo_total ?? ($credit->montant_total / 16);
        
        if ($remboursementHebdo <= 0) return 0;
        
        $nombreEcheancesCompletes = floor($montantPaye / $remboursementHebdo);
        $reste = fmod($montantPaye, $remboursementHebdo);
        
        $capitalParEcheance = $credit->montant_accorde / 16;
        $interetParEcheance = $remboursementHebdo - $capitalParEcheance;
        
        $capitalTotal = $nombreEcheancesCompletes * $capitalParEcheance;
        
        if ($reste > 0) {
            $interetsDuReste = min($reste, $interetParEcheance);
            $capitalDuReste = max(0, $reste - $interetsDuReste);
            $capitalTotal += $capitalDuReste;
        }
        
        return round($capitalTotal, 2);
    }
}

/**
 * Calcule les intérêts déjà payés pour l'affichage dans la table - CORRIGÉ
 */
private function calculerInteretsDejaPayesTable($credit): float
{
    if ($credit->type_credit === 'individuel') {
        // Récupérer tous les paiements
        $paiements = PaiementCredit::where('credit_id', $credit->id)
            ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
            ->get();
        
        if ($paiements->isEmpty()) {
            return 0;
        }
        
        // OPTION 1 : Si vous stockez interets_payes dans la table paiements
        if ($paiements->first()->interets_payes !== null) {
            return $paiements->sum('interets_payes');
        }
        
        // OPTION 2 : Calculer selon la formule fixe
        $montantPaye = $paiements->sum('montant_paye');
        $remboursementHebdo = $credit->remboursement_hebdo ?? ($credit->montant_total / 16);
        
        if ($remboursementHebdo <= 0) return 0;
        
        $nombreEcheancesCompletes = floor($montantPaye / $remboursementHebdo);
        $reste = fmod($montantPaye, $remboursementHebdo);
        
        $capitalParEcheance = $credit->montant_accorde / 16;
        $interetParEcheance = $remboursementHebdo - $capitalParEcheance;
        
        // Intérêts pour les échéances complètes
        $interetsTotaux = $nombreEcheancesCompletes * $interetParEcheance;
        
        // Pour le reste, priorité aux intérêts
        if ($reste > 0) {
            $interetsDuReste = min($reste, $interetParEcheance);
            $interetsTotaux += $interetsDuReste;
        }
        
        return round($interetsTotaux, 2);
        
    } else {
        // Pour les groupes
        $groupeId = $credit->id - 100000;
        $paiements = PaiementCredit::where('credit_groupe_id', $groupeId)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->get();
        
        if ($paiements->isEmpty()) {
            return 0;
        }
        
        $montantPaye = $paiements->sum('montant_paye');
        $remboursementHebdo = $credit->remboursement_hebdo_total ?? ($credit->montant_total / 16);
        
        if ($remboursementHebdo <= 0) return 0;
        
        $nombreEcheancesCompletes = floor($montantPaye / $remboursementHebdo);
        $reste = fmod($montantPaye, $remboursementHebdo);
        
        $capitalParEcheance = $credit->montant_accorde / 16;
        $interetParEcheance = $remboursementHebdo - $capitalParEcheance;
        
        $interetsTotaux = $nombreEcheancesCompletes * $interetParEcheance;
        
        if ($reste > 0) {
            $interetsDuReste = min($reste, $interetParEcheance);
            $interetsTotaux += $interetsDuReste;
        }
        
        return round($interetsTotaux, 2);
    }
}

/**
 * Vérifie la cohérence des calculs
 */
private function verifierCohérenceCalculs($credit): void
{
    $montantPaye = $this->calculerTotalPaiementsPourTable($credit);
    $capitalCalcule = $this->calculerCapitalDejaRembourseTable($credit);
    $interetsCalcules = $this->calculerInteretsDejaPayesTable($credit);
    
    $somme = $capitalCalcule + $interetsCalcules;
    $difference = abs($somme - $montantPaye);
    
    if ($difference > 0.01) {
        Log::warning('INCOHÉRENCE DANS LES CALCULS', [
            'credit_id' => $credit->id,
            'type' => $credit->type_credit,
            'montant_paye' => $montantPaye,
            'capital_calcule' => $capitalCalcule,
            'interets_calcules' => $interetsCalcules,
            'somme' => $somme,
            'difference' => $difference,
            'montant_accorde' => $credit->montant_accorde,
            'montant_total' => $credit->montant_total,
            'remboursement_hebdo' => $credit->type_credit === 'groupe' 
                ? ($credit->remboursement_hebdo_total ?? $credit->montant_total / 16)
                : $credit->remboursement_hebdo,
        ]);
        
        // Correction automatique : ajuster les intérêts pour équilibrer
        if ($somme > $montantPaye) {
            // Si la somme dépasse, réduire les intérêts
            $interetsCorriges = max(0, $interetsCalcules - $difference);
            Log::info('Correction automatique appliquée', [
                'ancien_interets' => $interetsCalcules,
                'nouveaux_interets' => $interetsCorriges
            ]);
        }
    }
}

/**
 * Calcule le total des paiements pour l'affichage dans la table
 */
private function calculerTotalPaiementsPourTable($credit): float
{
    if ($credit->type_credit === 'individuel') {
        return PaiementCredit::where('credit_id', $credit->id)
            ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
            ->sum('montant_paye');
    } else {
        $groupeId = $credit->id - 100000;
        return PaiementCredit::where('credit_groupe_id', $groupeId)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->sum('montant_paye');
    }
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

           
//              // Dans getHeaderActions() de RapportsMicrofinance.php
// Action::make('forcer_egalite_demande_accorde')
//     ->label('⚖️ Forcer Demande = Accordé')
//     ->color('danger')
//     ->icon('heroicon-m-scale')
//     ->action(fn () => $this->forcerMontantDemandeEgalAccorde())
//     ->modalHeading('Forçage Montant Demande = Montant Accordé')
//     ->modalDescription('Corrige TOUS les crédits pour que montant_demande soit égal à montant_accorde')
//     ->requiresConfirmation(),


            // Action::make('corriger_capital')
            // ->label('🔧 Corriger Capital')
            // ->color('danger')
            // ->icon('heroicon-m-wrench')
            // ->action(fn () => $this->restaurerCapitalOriginal())
            // ->modalHeading('Correction du Capital')
            // ->modalDescription('Restaure les montants accordés à leur valeur originale')
            // ->requiresConfirmation(),


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

             Action::make('exporter_rapport')
            ->label('Exporter Rapport')
            ->color('danger')
            ->icon('heroicon-m-document-text')
            ->action(fn () => $this->genererRapportHTML()),

            // // NOUVEAU : Action principale avec sous-menu
            // Action::make('paiement_remboursements')
            //     ->label('Paiement Remboursements')
            //     ->color('warning')
            //     ->icon('heroicon-m-currency-dollar')
            //     ->modalHeading('Système de Paiement des Remboursements')
            //     ->modalDescription('Choisissez le type de crédit à traiter')
            //     ->modalContent(view('filament.pages.choix-paiement-remboursements', [
            //         'groupesActifs' => $this->getGroupesAvecCreditsActifs()
            //     ]))
            //     ->modalFooterActions([])
            //     ->action(fn () => null), // Pas d'action directe

            // Actions séparées pour chaque type (maintenues pour compatibilité)
            Action::make('paiement_individuels')
                ->label('Paiement Crédits Individuels')
                ->color('primary')
                ->icon('heroicon-m-user')
                ->schema([
                    Section::make('Paramètres de Paiement - Crédits Individuels')
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
                    $this->processerPaiementsIndividuels($data);
                })
                ->modalHeading('Paiement des Remboursements - Crédits Individuels')
                ->modalDescription('Exécuter le paiement automatique des remboursements pour tous les crédits individuels actifs')
                ->modalSubmitActionLabel('Exécuter les Paiements Individuels')
                ->modalCancelActionLabel('Annuler'),

                // Action::make('paiement_groupes')
                //     ->label('Paiement Crédits Groupe')
                //     ->color('info')
                //     ->icon('heroicon-m-users')
                //     ->action(function () {
                //         // Éventuellement des logs ou préparation de données
                //         Log::info('Redirection vers la page de paiement groupe');
                        
                //         // Redirection vers la page dédiée
                //         return redirect()->route('paiement.credits.groupe');
                //     }),

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


private function afficherVerificationMontants()
{
    $resultat = $this->verifierEgaliteMontants();
    
    Notification::make()
        ->title('Vérification Montants')
        ->body($resultat)
        ->info()
        ->send();
}


    
    

    /**
 * Restaure les montants accordés à leur valeur originale
 */
private function restaurerCapitalOriginal()
{
    try {
        DB::beginTransaction();
        
        // Liste des crédits avec leurs montants demandés (qui devraient être égaux aux montants accordés)
        $credits = [
            ['id' => 7, 'montant_demande' => 1500.00],
            ['id' => 8, 'montant_demande' => 1000.00],          
            ['id' => 13, 'montant_demande' => 1000.00],
           
         
        ];
        
        $corrections = [];
        
        foreach ($credits as $data) {
            $credit = Credit::find($data['id']);
            if ($credit) {
                $ancienMontant = $credit->montant_accorde;
                $nouveauMontant = $data['montant_demande'];
                
                if (abs($ancienMontant - $nouveauMontant) > 0.01) {
                    // Restaurer le montant accordé
                    $credit->montant_accorde = $nouveauMontant;
                    
                    // Recalculer le montant total selon vos formules
                    $montantTotal = Credit::calculerMontantTotalIndividuel($nouveauMontant);
                    $credit->montant_total = $montantTotal;
                    
                    // Recalculer le remboursement hebdo
                    $credit->remboursement_hebdo = $montantTotal / 16;
                    
                    $credit->save();
                    
                    $corrections[] = [
                        'id' => $credit->id,
                        'ancien' => $ancienMontant,
                        'nouveau' => $nouveauMontant,
                        'montant_total' => $montantTotal,
                        'remboursement_hebdo' => $montantTotal / 16
                    ];
                }
            }
        }
        
        DB::commit();
        
        // Afficher les résultats
        $message = "✅ **Capital restauré avec succès!**\n\n";
        $message .= "**Corrections appliquées:**\n";
        
        foreach ($corrections as $correction) {
            $message .= "• Crédit #{$correction['id']}: {$correction['ancien']} → {$correction['nouveau']} USD\n";
        }
        
        if (empty($corrections)) {
            $message = "✅ Aucune correction nécessaire. Les montants sont déjà corrects.";
        }
        
        Notification::make()
            ->title('Correction Capital Terminée')
            ->body($message)
            ->success()
            ->send();
            
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur correction capital: ' . $e->getMessage());
        
        Notification::make()
            ->title('Erreur')
            ->body('Erreur lors de la correction: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}




/**
 * Corrige les crédits groupe selon la formule fixe
 */
/**
 * Force montant_demande = montant_accorde pour TOUS les crédits
 */
/**
 * Force montant_demande = montant_accorde et recalcul de montant_total = montant_accorde * 1.225
 */
/**
 * Force montant_accorde = montant_demande pour les GROUPES uniquement
 * et recalcul de montant_total = montant_accorde * 1.225
 */
private function forcerMontantDemandeEgalAccorde()
{
    try {
        DB::beginTransaction();
        
        $correctionsGroupes = [];
        
        // SEULEMENT les crédits groupe
        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
            ->with(['compte'])
            ->get();
        
        foreach ($creditsGroupe as $credit) {
            $ancienDemande = $credit->montant_demande;
            $ancienAccorde = $credit->montant_accorde;
            $ancienTotal = $credit->montant_total;
            
            // Vérifier si besoin de correction (si différent de plus de 0.01)
            $besoinCorrection = abs($ancienDemande - $ancienAccorde) > 0.01;
            
            if ($besoinCorrection) {
                // RÈGLE 1: montant_accorde = montant_demande
                $nouveauAccorde = $credit->montant_demande;
                
                // RÈGLE 2: montant_total = montant_accorde * 1.225
                $nouveauTotal = $nouveauAccorde * 1.225;
                
                // Mettre à jour le crédit
                $credit->montant_accorde = $nouveauAccorde;
                $credit->montant_total = $nouveauTotal;
                
                // Recalculer le remboursement hebdo
                if ($credit->remboursement_hebdo_total) {
                    $credit->remboursement_hebdo_total = $nouveauTotal / 16;
                }
                
                $credit->save();
                
                $correctionsGroupes[] = [
                    'id' => $credit->id,
                    'groupe' => $credit->compte?->nom ?? 'Groupe ' . $credit->id,
                    'ancien_demande' => $ancienDemande,
                    'ancien_accorde' => $ancienAccorde,
                    'ancien_total' => $ancienTotal,
                    'nouveau_accorde' => $nouveauAccorde,
                    'nouveau_total' => $nouveauTotal,
                    'verification' => round($nouveauAccorde * 1.225, 2)
                ];
                
                Log::info('Groupe corrigé', [
                    'groupe_id' => $credit->id,
                    'nom' => $credit->compte?->nom,
                    'demande' => $ancienDemande,
                    'accorde_avant' => $ancienAccorde,
                    'accorde_apres' => $nouveauAccorde,
                    'total_avant' => $ancienTotal,
                    'total_apres' => $nouveauTotal,
                    'formule_verif' => $nouveauAccorde . ' × 1.225 = ' . $nouveauTotal
                ]);
            }
        }
        
        DB::commit();
        
        // Afficher les résultats
        if (empty($correctionsGroupes)) {
            $message = "✅ **Aucune correction nécessaire!**\n\n";
            $message .= "Tous les crédits groupe sont déjà cohérents:\n";
            $message .= "• montant_demande = montant_accorde\n";
            $message .= "• montant_total = montant_accorde × 1.225";
        } else {
            $message = "✅ **Correction des groupes terminée!**\n\n";
            $message .= "**Groupes corrigés:** " . count($correctionsGroupes) . "\n\n";
            
            foreach ($correctionsGroupes as $correction) {
                $message .= "• **Groupe #{$correction['id']}** ({$correction['groupe']}):\n";
                $message .= "  Demande: {$correction['ancien_demande']} USD (inchangé)\n";
                $message .= "  Accordé: {$correction['ancien_accorde']} → {$correction['nouveau_accorde']} USD\n";
                $message .= "  Total: {$correction['ancien_total']} → {$correction['nouveau_total']} USD\n";
                $message .= "  Vérif: {$correction['nouveau_accorde']} × 1.225 = {$correction['verification']} USD\n\n";
            }
            
            // Calcul des totaux
            $totalDemande = array_sum(array_column($correctionsGroupes, 'ancien_demande'));
            $totalAccordeAvant = array_sum(array_column($correctionsGroupes, 'ancien_accorde'));
            $totalAccordeApres = array_sum(array_column($correctionsGroupes, 'nouveau_accorde'));
            $totalAvant = array_sum(array_column($correctionsGroupes, 'ancien_total'));
            $totalApres = array_sum(array_column($correctionsGroupes, 'nouveau_total'));
            
            $message .= "**📊 RÉCAPITULATIF:**\n";
            $message .= "• Total demande: " . CurrencyHelper::format($totalDemande) . "\n";
            $message .= "• Total accordé avant: " . CurrencyHelper::format($totalAccordeAvant) . "\n";
            $message .= "• Total accordé après: " . CurrencyHelper::format($totalAccordeApres) . "\n";
            $message .= "• Différence accordé: " . CurrencyHelper::format($totalAccordeApres - $totalAccordeAvant) . "\n";
            $message .= "• Total à remb. avant: " . CurrencyHelper::format($totalAvant) . "\n";
            $message .= "• Total à remb. après: " . CurrencyHelper::format($totalApres) . "\n";
            $message .= "• Intérêts totaux après: " . CurrencyHelper::format($totalApres - $totalAccordeApres) . "\n";
            $message .= "• Ratio: " . ($totalAccordeApres > 0 ? round(($totalApres / $totalAccordeApres * 100) - 100, 2) : 0) . "%";
        }
        
        Notification::make()
            ->title('Correction Montants Groupe')
            ->body($message)
            ->success()
            ->duration(10000) // 10 secondes pour lire
            ->send();
            
        // Log détaillé
        Log::info('Correction montants groupe terminée', [
            'groupes_corriges' => count($correctionsGroupes),
            'details' => $correctionsGroupes
        ]);
            
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur correction montants groupe: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        Notification::make()
            ->title('Erreur')
            ->body('Erreur lors de la correction: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}

    /**
     * Récupère les groupes avec des crédits actifs
     */
    private function getGroupesAvecCreditsActifs(): Collection
    {
        return CreditGroupe::where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->where('date_echeance', '>=', now())
            ->with(['compte'])
            ->get()
            ->map(function ($creditGroupe) {
                $creditGroupe->montant_restant = $this->calculerMontantRestantGroupe($creditGroupe);
                $creditGroupe->prochain_remboursement = $this->calculerProchainRemboursementGroupe($creditGroupe);
                return $creditGroupe;
            });
    }

    /**
 * Vérification ULTIME du capital
 */
private function verifierCapitalUltime()
{
    $credits = Credit::where('statut_demande', 'approuve')->get();
    
    foreach ($credits as $credit) {
        // Vérifier que montant_accorde = montant_demande
        if (abs($credit->montant_accorde - $credit->montant_demande) > 0.01) {
            Log::warning('INCOHÉRENCE DÉTECTÉE: montant_accorde ≠ montant_demande', [
                'credit_id' => $credit->id,
                'montant_demande' => $credit->montant_demande,
                'montant_accorde' => $credit->montant_accorde,
                'difference' => $credit->montant_demande - $credit->montant_accorde
            ]);
        }
        
        // Vérifier les calculs
        $montantTotalCalcule = Credit::calculerMontantTotalIndividuel($credit->montant_accorde);
        if (abs($montantTotalCalcule - $credit->montant_total) > 0.01) {
            Log::warning('INCOHÉRENCE: montant_total incorrect', [
                'credit_id' => $credit->id,
                'montant_total_stocke' => $credit->montant_total,
                'montant_total_calcule' => $montantTotalCalcule
            ]);
        }
    }
}
   /**
 * Calcule le montant restant à rembourser pour un groupe
 */
            private function calculerMontantRestantGroupe($creditGroupe): float
            {
                // Pour les groupes, on calcule le montant restant basé sur les paiements
                // effectués sur le compte groupe (pas sur les comptes membres)
                $totalPaiements = DB::table('paiement_credits')
                    ->where('compte_id', $creditGroupe->compte_id)
                    ->sum('montant_paye');

                return max(0, $creditGroupe->montant_total - $totalPaiements);
            }
    /**
     * Calcule le prochain remboursement pour un groupe
     */
/**
 * Calcule le prochain remboursement pour un groupe
 */
private function calculerProchainRemboursementGroupe($creditGroupe): float
{
    $semaineActuelle = $this->getSemaineActuelle($creditGroupe);
    
    // Si le crédit est déjà remboursé, retourner 0
    if ($creditGroupe->montant_restant <= 0) {
        return 0;
    }
    
    // Pour la dernière semaine, retourner le montant restant
    if ($semaineActuelle == 16) {
        return min($creditGroupe->remboursement_hebdo_total, $creditGroupe->montant_restant);
    }
    
    return $semaineActuelle <= 16 ? $creditGroupe->remboursement_hebdo_total : 0;
}

    /**
     * Détermine la semaine actuelle du remboursement
     */
    private function getSemaineActuelle($credit): int
    {
        if (!$credit->date_octroi) {
            return 1;
        }

        $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        
        return min($semainesEcoulees + 1, 16);
    }

    /**
     * Traite les paiements pour les crédits individuels
     */
    private function processerPaiementsIndividuels(array $data = null)
    {
        DB::transaction(function () use ($data) {
            $datePaiement = $data['date_paiement'] ?? now();
            $forcerPaiement = $data['forcer_paiement'] ?? true;
            $results = [];
            
            // Traiter les crédits individuels
            $creditsIndividuels = Credit::where('statut_demande', 'approuve')
                ->where('montant_total', '>', 0)
                ->where('type_credit', 'individuel')
                ->with(['compte', 'paiements'])
                ->get();
                
            foreach ($creditsIndividuels as $credit) {
                $result = $this->traiterPaiementCreditIndividuel($credit, $datePaiement, $forcerPaiement);
                if ($result) $results[] = $result;
            }
            
            $this->afficherResultatsPaiementsIndividuels($results);
                $this->verifierDonneesCredit($credit);

        });
    }

    /**
     * Traite le paiement d'un crédit individuel (version avec 3 paramètres)
     */

    
private function traiterPaiementCreditIndividuel($credit, $datePaiement, $forcerPaiement = true)
{

    $this->verifierCalculsCredit($credit);
    //  $this->verifierCapitalNonModifie($credit);

    // VÉRIFIER ET CORRIGER le remboursement hebdo
    $this->verifierEtCorrigerRemboursementHebdo($credit);

    $compte = $credit->compte;
    
    // Calculer le solde disponible (hors caution)
    $soldeDisponible = $this->calculerSoldeDisponible($compte->id);
    $montantDu = $this->calculerMontantDuCetteSemaine($credit);

     // DEBUG
    Log::info('DEBUG - Début traitement paiement individuel', [
        'credit_id' => $credit->id,
        'compte' => $compte->numero_compte,
        'montant_accorde' => $credit->montant_accorde,
        'montant_du' => $montantDu,
        'remboursement_hebdo' => $credit->remboursement_hebdo,
        'solde_disponible' => $soldeDisponible
    ]);
    
    if ($soldeDisponible <= 0 && !$forcerPaiement) {
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
    $montantAPrelever = $forcerPaiement ? min($soldeDisponible, $montantDu) : $montantDu;
    
    if ($montantAPrelever <= 0) {
        return [
            'type' => 'individuel',
            'compte' => $compte->numero_compte,
            'statut' => 'echec',
            'raison' => 'Aucun montant à prélever',
            'solde_disponible' => $soldeDisponible,
            'montant_du' => $montantDu
        ];
    }
    
    // === CORRECTION ICI : Utiliser la nouvelle méthode de répartition ===
    $repartition = $this->repartirCapitalInterets($credit, $montantAPrelever);
    
    // VÉRIFICATION CRITIQUE
    Log::info('DEBUG - Vérification répartition', [
        'montant_preleve' => $montantAPrelever,
        'montant_du' => $montantDu,
        'repartition_capital' => $repartition['capital'],
        'repartition_interets' => $repartition['interets'],
        'somme_repartition' => $repartition['capital'] + $repartition['interets'],
        'difference' => abs(($repartition['capital'] + $repartition['interets']) - $montantAPrelever)
    ]);
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


// private function verifierCapitalNonModifie(Credit $credit)
// {
//     // Récupérer l'historique pour voir s'il y a eu des modifications
//     $modifications = DB::table('audits')
//         ->where('auditable_id', $credit->id)
//         ->where('auditable_type', Credit::class)
//         ->where('event', 'updated')
//         ->whereJsonContains('old_values', ['montant_accorde' => $credit->montant_accorde])
//         ->exists();
    
//     if ($modifications) {
//         Log::error('ALERTE : Le capital accordé a été modifié !', [
//             'credit_id' => $credit->id,
//             'montant_accorde_actuel' => $credit->montant_accorde
//         ]);
        
//         // Vous pouvez choisir de restaurer la valeur originale ici
//         // ou de bloquer le paiement
//     }
// }
   
 /**
 * Traite les paiements pour un groupe spécifique
 */
public function processerPaiementsGroupe()
{
    if (!$this->selectedGroupeId || $this->totalPaiementsSaisis <= 0) {
        Notification::make()
            ->title('Erreur')
            ->body('Veuillez sélectionner un groupe et saisir des paiements.')
            ->danger()
            ->send();
        return;
    }

    DB::transaction(function () {
        $creditGroupe = CreditGroupe::with(['compte'])->findOrFail($this->selectedGroupeId);
        $datePaiement = now();
        $results = [];

        $totalPaiementGroupe = 0;

        foreach ($this->paiementsMembres as $membreId => $montantPaye) {
            $montantPaye = floatval($montantPaye);
            if ($montantPaye > 0) {
                $result = $this->traiterPaiementMembreGroupe($membreId, $montantPaye, $creditGroupe, $datePaiement);
                $results[] = $result;
                $totalPaiementGroupe += $montantPaye;
            }
        }

        // Transférer le total vers le compte groupe
        if ($totalPaiementGroupe > 0) {
            $this->effectuerPaiementGroupe($creditGroupe, $totalPaiementGroupe, $datePaiement);
        }

        $this->afficherResultatsPaiementsGroupe($creditGroupe, $results, $totalPaiementGroupe);
        
        // Réinitialiser les données
        $this->selectedGroupeId = null;
        $this->paiementsMembres = [];
    });
}
   
  /**
 * Traite le paiement d'un membre d'un groupe
 */private function traiterPaiementMembreGroupe($membreId, $montantPaye, $creditGroupe, $datePaiement)
{
    // Trouver le compte du membre
    $compteMembre = Compte::where('client_id', $membreId)->first();
    
    if (!$compteMembre) {
        return [
            'compte' => 'Membre ' . $membreId,
            'montant_preleve' => 0,
            'montant_du' => 0,
            'statut' => 'echec',
            'raison' => 'Compte membre non trouvé'
        ];
    }

    // Récupérer les détails du membre depuis la répartition
    $repartition = $creditGroupe->repartition_membres ?? [];
    $detailsMembre = $repartition[$membreId] ?? [];
    
    $montantDuMembre = $detailsMembre['remboursement_hebdo'] ?? 0;
    
    // Calculer le solde disponible du membre (hors caution)
    $soldeDisponible = $this->calculerSoldeDisponible($compteMembre->id);
    
    $montantAPrelever = min($soldeDisponible, $montantPaye, $montantDuMembre);
    
    if ($montantAPrelever > 0) {
        // Créer une structure de crédit factice pour la répartition
        $creditFactice = (object)[
            'montant_accorde' => $detailsMembre['montant_accorde'] ?? 0,
            'remboursement_hebdo' => $montantDuMembre
        ];
        
        $repartitionCapitalInterets = $this->repartirCapitalInterets($creditFactice, $montantAPrelever);
        
        // Effectuer le prélèvement du membre
        $this->effectuerPrelevementMembreGroupe($compteMembre, $creditGroupe, $montantAPrelever, $repartitionCapitalInterets, $datePaiement, $membreId);
        
        return [
            'compte' => $compteMembre->numero_compte,
            'montant_preleve' => $montantAPrelever,
            'montant_du' => $montantDuMembre,
            'statut' => $montantAPrelever < $montantDuMembre ? 'partiel' : 'succes',
            'raison' => $montantAPrelever < $montantDuMembre ? 'Paiement partiel' : 'Paiement complet'
        ];
    } else {
        return [
            'compte' => $compteMembre->numero_compte,
            'montant_preleve' => 0,
            'montant_du' => $montantDuMembre,
            'statut' => 'echec',
            'raison' => 'Solde insuffisant ou montant invalide'
        ];
    }
}

    /**
     * Affiche les résultats des paiements groupe
     */
 private function afficherResultatsPaiementsGroupe($creditGroupe, $results, $totalPaiementGroupe)
{
    $membresPayes = count(array_filter($results, fn($r) => $r['statut'] === 'succes'));
    $membresPartiels = count(array_filter($results, fn($r) => $r['statut'] === 'partiel'));
    $membresEchecs = count(array_filter($results, fn($r) => $r['statut'] === 'echec'));

    $message = "📊 **Paiement Groupe Terminé!**\n\n";
    $message .= "**Groupe:** {$creditGroupe->compte->nom}\n";
    $message .= "**Total collecté:** " . number_format($totalPaiementGroupe, 2) . " USD\n";
    $message .= "**Nouveau solde groupe:** " . number_format($creditGroupe->compte->solde, 2) . " USD\n\n";
    $message .= "**Détails par membre:**\n";
    
    foreach ($results as $result) {
        $statutIcon = $result['statut'] === 'succes' ? '✅' : ($result['statut'] === 'partiel' ? '⚠️' : '❌');
        $message .= "{$statutIcon} {$result['compte']}: " . number_format($result['montant_preleve'], 2) . " USD / " . number_format($result['montant_du'], 2) . " USD\n";
    }

    $message .= "\n**Résumé:** {$membresPayes} ✅ complets, {$membresPartiels} ⚠️ partiels, {$membresEchecs} ❌ échecs";

    Notification::make()
        ->title('Paiement Groupe Terminé')
        ->body($message)
        ->success()
        ->send();
}
    /**
     * Affiche les résultats des paiements individuels
     */
    private function afficherResultatsPaiementsIndividuels($results)
    {
        $succes = count(array_filter($results, fn($r) => $r['statut'] === 'succes'));
        $partiels = count(array_filter($results, fn($r) => $r['statut'] === 'partiel'));
        $echecs = count(array_filter($results, fn($r) => $r['statut'] === 'echec'));
        $totalPreleve = array_sum(array_column($results, 'montant_preleve'));

        $message = "✅ **Paiements Individuels Terminés!**\n\n";
        $message .= "• Crédits traités: " . count($results) . "\n";
        $message .= "• Total prélevé: " . number_format($totalPreleve, 2) . " USD\n";
        $message .= "• Paiements complets: {$succes}\n";
        $message .= "• Paiements partiels: {$partiels}\n";
        $message .= "• Échecs: {$echecs}";

        Notification::make()
            ->title('Paiements Individuels Terminés')
            ->body($message)
            ->success()
            ->send();
    }

    /**
 * Réinitialise les paiements lorsqu'on change de groupe
 */
public function updatedSelectedGroupeId()
{
    $this->paiementsMembres = [];
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

/**
 * Effectue le prélèvement et gère la réduction du capital et des intérêts
 */
private function effectuerPrelevement($compte, $credit, $montant, $repartition, $datePaiement)
{
    DB::transaction(function () use ($compte, $credit, $montant, $repartition, $datePaiement) {
        // DEBUG: Vérifier les valeurs avant traitement
        Log::info('DEBUG - Avant prélèvement', [
            'credit_id' => $credit->id,
            'montant_total' => $montant,
            'repartition_capital' => $repartition['capital'],
            'repartition_interets' => $repartition['interets'],
            'somme_repartition' => $repartition['capital'] + $repartition['interets'],
            'compte' => $compte->numero_compte,
            'solde_avant' => $compte->solde
        ]);

        // Vérifier que la somme capital + intérêts = montant total
        $sommeRepartition = round($repartition['capital'] + $repartition['interets'], 2);
        $montantArrondi = round($montant, 2);
        
        if (abs($sommeRepartition - $montantArrondi) > 0.01) {
            Log::error('INCOHÉRENCE DE RÉPARTITION', [
                'montant_total' => $montant,
                'capital' => $repartition['capital'],
                'interets' => $repartition['interets'],
                'somme' => $sommeRepartition,
                'difference' => $sommeRepartition - $montantArrondi
            ]);
            
            // Ajuster pour éviter l'erreur
            $repartition['interets'] = $montant - $repartition['capital'];
        }

        // Débiter le compte
        $ancienSolde = $compte->solde;
        $compte->solde -= $montant;
        $compte->save();

        // Créer le paiement
        $paiement = PaiementCredit::create([
            'credit_id' => $credit->id,
            'compte_id' => $compte->id,
            'montant_paye' => $montant,
            'date_paiement' => $datePaiement,
            'type_paiement' => TypePaiement::AUTOMATIQUE->value,
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

        // === IMPORTANT : RÉDUIRE LE CAPITAL ET LES INTÉRÊTS DU CRÉDIT ===
        $this->reduireCapitalEtInteretsCredit($credit, $repartition);

        // Générer l'écriture comptable
        $this->genererEcritureComptablePaiement($compte, $credit, $montant, $repartition, $paiement->reference);
    });
}

/**
 * Réduit SEULEMENT les intérêts attendus, JAMAIS le capital accordé
 */
private function reduireCapitalEtInteretsCredit($credit, $repartition)
{
    // NE RIEN FAIRE pour le capital (il ne doit JAMAIS diminuer)
    // Seulement réduire les intérêts attendus
    
    if ($repartition['interets'] > 0) {
        // Pour un crédit individuel réel
        if ($credit instanceof Credit && $credit->type_credit === 'individuel') {
            $vraiCredit = Credit::find($credit->id);
            if ($vraiCredit) {
                // RÉCUPÉRER LE MONTANT ACCORDÉ ORIGINAL
                $montantAccordeOriginal = $vraiCredit->getOriginal('montant_accorde');
                
                // Calculer les intérêts actuels
                $interetsActuels = $vraiCredit->montant_total - $montantAccordeOriginal;
                
                // Réduire SEULEMENT les intérêts
                $nouveauxInterets = max(0, $interetsActuels - $repartition['interets']);
                
                // Le nouveau montant total = capital original + nouveaux intérêts
                $nouveauMontantTotal = $montantAccordeOriginal + $nouveauxInterets;
                
                // VÉRIFICATION CRITIQUE : Ne jamais descendre en dessous du capital accordé
                if ($nouveauMontantTotal < $montantAccordeOriginal) {
                    Log::error('ERREUR GRAVE : Tentative de réduire en dessous du capital accordé', [
                        'credit_id' => $vraiCredit->id,
                        'montant_accorde_original' => $montantAccordeOriginal,
                        'nouveau_montant_total' => $nouveauMontantTotal
                    ]);
                    $nouveauMontantTotal = $montantAccordeOriginal;
                }
                
                $vraiCredit->montant_total = $nouveauMontantTotal;
                $vraiCredit->save();
                
                Log::info('Intérêts réduits (capital PROTÉGÉ)', [
                    'credit_id' => $vraiCredit->id,
                    'capital_paye' => $repartition['capital'],
                    'interets_payes' => $repartition['interets'],
                    'montant_accorde_original_fixe' => $montantAccordeOriginal, // TOUJOURS LE MÊME
                    'ancien_montant_total' => $vraiCredit->getOriginal('montant_total'),
                    'nouveau_montant_total' => $vraiCredit->montant_total,
                    'interets_restants' => $nouveauxInterets
                ]);
            }
        }
        // Pour un crédit groupe
        elseif (property_exists($credit, 'type_credit') && $credit->type_credit === 'groupe') {
            $groupeId = $credit->id - 100000;
            $creditGroupe = CreditGroupe::find($groupeId);
            
            if ($creditGroupe) {
                $montantAccordeOriginal = $creditGroupe->getOriginal('montant_accorde');
                $nouveauMontantTotal = max(
                    $montantAccordeOriginal, // Minimum = montant accordé original
                    $creditGroupe->montant_total - $repartition['interets']
                );
                
                $creditGroupe->montant_total = $nouveauMontantTotal;
                $creditGroupe->save();
                
                Log::info('Intérêts réduits groupe (capital PROTÉGÉ)', [
                    'groupe_id' => $creditGroupe->id,
                    'capital_paye' => $repartition['capital'],
                    'interets_payes' => $repartition['interets'],
                    'montant_accorde_original_fixe' => $montantAccordeOriginal,
                    'nouveau_montant_total' => $creditGroupe->montant_total
                ]);
            }
        }
    }
}
/**
 * Ajuste le Portefeuille Capital après un paiement
 */
private function ajusterPortefeuilleCapital($credit, $capitalRembourse)
{
    if ($capitalRembourse <= 0) {
        return;
    }

    // Rechercher le compte spécial "Portefeuille Capital"
    $compteSpecial = CompteSpecial::where('nom', 'Portefeuille Capital')->first();
    
    if ($compteSpecial) {
        // Mettre à jour le solde du Portefeuille Capital
        $ancienSolde = $compteSpecial->solde;
        $nouveauSolde = $ancienSolde - $capitalRembourse;
        
        $compteSpecial->solde = $nouveauSolde;
        $compteSpecial->save();

        // Créer un mouvement pour le Portefeuille Capital
        Mouvement::create([
            'compte_id' => $compteSpecial->id,
            'type_mouvement' => 'diminution_capital_rembourse',
            'montant' => -$capitalRembourse,
            'solde_avant' => $ancienSolde,
            'solde_apres' => $nouveauSolde,
            'description' => "Diminution Portefeuille Capital - Capital remboursé: " . number_format($capitalRembourse, 2) . " USD - Crédit ID: " . $credit->id,
            'reference' => 'CAPITAL-REMBOURSE-' . $credit->id . '-' . now()->format('YmdHis'),
            'date_mouvement' => now(),
            'nom_deposant' => 'Système Automatique'
        ]);

        Log::info('Portefeuille Capital ajusté', [
            'credit_id' => $credit->id,
            'capital_rembourse' => $capitalRembourse,
            'ancien_solde' => $ancienSolde,
            'nouveau_solde' => $nouveauSolde
        ]);
    }
}

/**
 * Réduit les intérêts attendus après un paiement
 */
private function reduireInteretsAttendus($credit, $interetsPayes)
{
    if ($interetsPayes <= 0) {
        return;
    }

    // Pour les crédits individuels
    if ($credit instanceof Credit && $credit->type_credit === 'individuel') {
        // Calculer les intérêts attendus actuels
        $interetsAttendusActuels = $credit->montant_total - $credit->montant_accorde;
        
        // Réduire les intérêts attendus en ajustant montant_total
        // Note : On ne touche pas à montant_accorde, seulement à montant_total
        if ($interetsAttendusActuels >= $interetsPayes) {
            $credit->montant_total -= $interetsPayes;
            $credit->save();
            
            Log::info('Intérêts attendus réduits (individuel)', [
                'credit_id' => $credit->id,
                'interets_payes' => $interetsPayes,
                'nouveau_montant_total' => $credit->montant_total
            ]);
        }
    }
    // Pour les crédits groupe
    elseif ($credit instanceof CreditGroupe) {
        // Pour les groupes, réduire montant_total
        $credit->montant_total -= $interetsPayes;
        $credit->save();
        
        Log::info('Intérêts attendus réduits (groupe)', [
            'groupe_id' => $credit->id,
            'interets_payes' => $interetsPayes,
            'nouveau_montant_total' => $credit->montant_total
        ]);
    }
}


        /**
 * Effectue le paiement sur le compte groupe
 */
private function effectuerPaiementGroupe($creditGroupe, $montantTotal, $datePaiement)
{
    $compteGroupe = $creditGroupe->compte;
    $ancienSolde = $compteGroupe->solde;
    
    // CRÉDITER le compte groupe
    $compteGroupe->solde += $montantTotal;
    $compteGroupe->save();

    // Enregistrer le paiement pour le groupe AVEC credit_groupe_id
    PaiementCredit::create([
        'credit_groupe_id' => $creditGroupe->id, // UTILISATION DU NOUVEAU CHAMP
        'compte_id' => $compteGroupe->id,
        'montant_paye' => $montantTotal,
        'date_paiement' => $datePaiement,
        'type_paiement' =>TypePaiement::GROUPE->value,
        'reference' => 'PAY-GROUPE-' . $creditGroupe->id . '-' . now()->format('YmdHis'),
        'statut' => 'complet',
        'capital_rembourse' => $montantTotal,
        'interets_payes' => 0
    ]);

    // Mouvement pour le groupe (entrée d'argent)
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
    
    // Générer l'écriture comptable
    $this->genererEcritureComptablePaiementGroupe($compteGroupe, $creditGroupe, $montantTotal);
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

    private function verifierCalculCapitalInterets($credit, $montantPaye)
{
    $remboursementHebdo = $credit->remboursement_hebdo ?? ($credit->montant_total / 16);
    $capitalHebdo = $credit->montant_accorde / 16;
    $interetHebdo = $remboursementHebdo - $capitalHebdo;
    
    Log::info('VÉRIFICATION CALCUL', [
        'credit_id' => $credit->id,
        'montant_accorde' => $credit->montant_accorde,
        'montant_total' => $credit->montant_total,
        'remboursement_hebdo' => $remboursementHebdo,
        'capital_hebdo' => $capitalHebdo,
        'interet_hebdo' => $interetHebdo,
        'montant_paye_a_repartir' => $montantPaye,
        
        // Calcul du nombre d'échéances complètes
        'nombre_echeances_completes' => floor($montantPaye / $remboursementHebdo),
        'reste' => fmod($montantPaye, $remboursementHebdo),
        
        // Calcul détaillé
        'capital_total' => floor($montantPaye / $remboursementHebdo) * $capitalHebdo,
        'interets_total' => floor($montantPaye / $remboursementHebdo) * $interetHebdo,
    ]);
}


/**
 * Calcule la répartition correcte capital/intérêts
 */
/**
 * Calcule la répartition CORRECTE capital/intérêts selon vos formules
 */
private function repartirCapitalInterets($credit, $montantPaiement)
{
    // Pour les crédits individuels
    if ($credit instanceof Credit && $credit->type_credit === 'individuel') {
        $montantAccorde = $credit->montant_accorde;
        
        // Vérifier et corriger le remboursement hebdo
        $remboursementHebdo = $this->verifierEtCorrigerRemboursementHebdo($credit);
        
        // Capital hebdomadaire FIXE = montantAccorde / 16
        $capitalHebdomadaire = $montantAccorde / 16;
        
        // Intérêts hebdo = remboursementHebdo - capitalHebdo
        $interetHebdomadaire = $remboursementHebdo - $capitalHebdomadaire;
        
        // Calculer les montants déjà payés
        $paiements = PaiementCredit::where('credit_id', $credit->id)
            ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
            ->get();
        
        $montantDejaPaye = $paiements->sum('montant_paye');
        $nombreEcheancesCompletes = floor($montantDejaPaye / $remboursementHebdo);
        $resteDejaPaye = fmod($montantDejaPaye, $remboursementHebdo);
        
        // Si c'est un paiement complet d'une échéance
        if ($montantPaiement >= $remboursementHebdo) {
            return [
                'capital' => $capitalHebdomadaire,
                'interets' => $interetHebdomadaire
            ];
        }
        
        // Pour paiement partiel : priorité aux intérêts de l'échéance courante
        $interetsEcheanceCourante = $interetHebdomadaire;
        
        // Si le reste déjà payé a déjà couvert une partie des intérêts
        if ($resteDejaPaye > 0) {
            $interetsDejaCouverts = min($resteDejaPaye, $interetHebdomadaire);
            $interetsEcheanceCourante = max(0, $interetHebdomadaire - $interetsDejaCouverts);
        }
        
        // D'abord payer les intérêts restants, puis le capital
        $interetsAPayer = min($montantPaiement, $interetsEcheanceCourante);
        $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
        
        return [
            'capital' => $capitalAPayer,
            'interets' => $interetsAPayer
        ];
    }
    
    // Pour les groupes
    if ($credit instanceof CreditGroupe) {
        $montantAccorde = $credit->montant_accorde;
        $remboursementHebdo = $credit->remboursement_hebdo_total ?? ($credit->montant_total / 16);
        
        $capitalHebdomadaire = $montantAccorde / 16;
        $interetHebdomadaire = $remboursementHebdo - $capitalHebdomadaire;
        
        if ($montantPaiement >= $remboursementHebdo) {
            return [
                'capital' => $capitalHebdomadaire,
                'interets' => $interetHebdomadaire
            ];
        }
        
        $interetsAPayer = min($montantPaiement, $interetHebdomadaire);
        $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
        
        return [
            'capital' => $capitalAPayer,
            'interets' => $interetsAPayer
        ];
    }
    
    // Fallback
    return [
        'capital' => $montantPaiement,
        'interets' => 0
    ];
}

private function verifierCapitalFixe(Credit $credit)
{
    // Vérifier dans l'historique des paiements
    $totalCapitalRembourse = PaiementCredit::where('credit_id', $credit->id)
        ->sum('capital_rembourse');
    
    $montantAccorde = $credit->montant_accorde;
    
    if ($totalCapitalRembourse > $montantAccorde) {
        Log::error('ERREUR : Capital remboursé dépasse montant accordé!', [
            'credit_id' => $credit->id,
            'montant_accorde' => $montantAccorde,
            'total_capital_rembourse' => $totalCapitalRembourse,
            'depassement' => $totalCapitalRembourse - $montantAccorde
        ]);
        
        // Corriger en ajustant le dernier paiement
        $this->corrigerDepassementCapital($credit, $totalCapitalRembourse - $montantAccorde);
    }
    
    // Vérifier que montant_total >= montant_accorde
    if ($credit->montant_total < $credit->montant_accorde) {
        Log::warning('Correction montant_total inférieur à montant_accorde', [
            'credit_id' => $credit->id,
            'montant_accorde' => $credit->montant_accorde,
            'montant_total' => $credit->montant_total,
            'difference' => $credit->montant_accorde - $credit->montant_total
        ]);
        
        $credit->montant_total = $credit->montant_accorde;
        $credit->save();
    }
}

private function corrigerDepassementCapital(Credit $credit, $depassement)
{
    // Trouver le dernier paiement
    $dernierPaiement = PaiementCredit::where('credit_id', $credit->id)
        ->orderByDesc('id')
        ->first();
    
    if ($dernierPaiement && $depassement > 0) {
        // Réduire le capital du dernier paiement
        $nouveauCapital = max(0, $dernierPaiement->capital_rembourse - $depassement);
        $nouveauTotal = $nouveauCapital + $dernierPaiement->interets_payes;
        
        $dernierPaiement->capital_rembourse = $nouveauCapital;
        $dernierPaiement->montant_paye = $nouveauTotal;
        $dernierPaiement->save();
        
        Log::info('Correction dépassement capital', [
            'paiement_id' => $dernierPaiement->id,
            'ancien_capital' => $dernierPaiement->getOriginal('capital_rembourse'),
            'nouveau_capital' => $nouveauCapital,
            'depassement_corrige' => $depassement
        ]);
    }
}

private function verifierCalculsCredit(Credit $credit)
{
    $montantAccorde = $credit->montant_accorde;
    
    // Calculer selon vos formules EXACTES
    $montantTotal = Credit::calculerMontantTotalIndividuel($montantAccorde);
    $remboursementHebdo = $montantTotal / 16;
    $interetsTotaux = $montantTotal - $montantAccorde;
    $interetsHebdo = $interetsTotaux / 16;
    $capitalHebdo = $remboursementHebdo - $interetsHebdo;
    
    Log::info('VÉRIFICATION CALCULS CRÉDIT', [
        'credit_id' => $credit->id,
        'montant_accorde' => $montantAccorde,
        'montant_total_calcule' => $montantTotal,
        'remboursement_hebdo_calcule' => $remboursementHebdo,
        'interets_totaux_calcule' => $interetsTotaux,
        'interets_hebdo_calcule' => $interetsHebdo,
        'capital_hebdo_calcule' => $capitalHebdo,
        
        // Comparaison avec les valeurs stockées
        'montant_total_stocke' => $credit->montant_total,
        'remboursement_hebdo_stocke' => $credit->remboursement_hebdo,
        'difference_montant_total' => abs($montantTotal - $credit->montant_total),
        'difference_remboursement_hebdo' => abs($remboursementHebdo - $credit->remboursement_hebdo)
    ]);
    
    // Si les valeurs stockées sont très différentes, les corriger
    if (abs($montantTotal - $credit->montant_total) > 0.01) {
        Log::warning('CORRECTION MONTANT TOTAL', [
            'credit_id' => $credit->id,
            'ancien' => $credit->montant_total,
            'nouveau' => $montantTotal,
            'difference' => $montantTotal - $credit->montant_total
        ]);
        
        $credit->montant_total = $montantTotal;
        $credit->remboursement_hebdo = $remboursementHebdo;
        $credit->save();
    }
}
private function verifierDonneesCredit(Credit $credit)
{
    Log::info('VÉRIFICATION CRÉDIT', [
        'id' => $credit->id,
        'montant_demande' => $credit->montant_demande,
        'montant_accorde' => $credit->montant_accorde,
        'montant_total' => $credit->montant_total,
        'remboursement_hebdo' => $credit->remboursement_hebdo,
        'taux_interet' => $credit->taux_interet,
        'duree_mois' => $credit->duree_mois,
        
        // Calculs
        'montant_total_calcule' => Credit::calculerMontantTotalIndividuel($credit->montant_accorde),
        'remboursement_hebdo_calcule' => $credit->montant_total / 16,
        'capital_hebdo_calcule' => $credit->montant_accorde / 16,
        'interets_hebdo_calcule' => ($credit->montant_total - $credit->montant_accorde) / 16,
    ]);
}

/**
 * Vérifie et corrige le remboursement hebdo selon vos formules EXACTES
 */
private function verifierEtCorrigerRemboursementHebdo(Credit $credit)
{
    // Récupérer le montant accordé
    $montantAccorde = $credit->montant_accorde;
    
    // Calculer selon vos formules EXACTES
    if ($montantAccorde >= 100 && $montantAccorde <= 500) {
        $montantTotalTheorique = $montantAccorde * 0.308666 * 4;
    } elseif ($montantAccorde >= 501 && $montantAccorde <= 1000) {
        $montantTotalTheorique = $montantAccorde * 0.3019166667 * 4;
    } elseif ($montantAccorde >= 1001 && $montantAccorde <= 1599) {
        $montantTotalTheorique = $montantAccorde * 0.30866 * 4;
    } elseif ($montantAccorde >= 2000 && $montantAccorde <= 5000) {
        $montantTotalTheorique = $montantAccorde * 0.2985666667 * 4;
    } else {
        $montantTotalTheorique = $montantAccorde * 0.30 * 4;
    }
    
    // Calculer le remboursement hebdo théorique
    $remboursementHebdoTheorique = $montantTotalTheorique / 16;
    
    // Récupérer le remboursement hebdo stocké
    $remboursementHebdoStocke = $credit->remboursement_hebdo;
    
    // Si différent de plus de 0.01, corriger
    if (abs($remboursementHebdoTheorique - $remboursementHebdoStocke) > 0.01) {
        Log::warning('CORRECTION REMBOURSEMENT HEBDOMADAIRE', [
            'credit_id' => $credit->id,
            'montant_accorde' => $montantAccorde,
            'ancien_remboursement_hebdo' => $remboursementHebdoStocke,
            'nouveau_remboursement_hebdo' => $remboursementHebdoTheorique,
            'difference' => $remboursementHebdoTheorique - $remboursementHebdoStocke
        ]);
        
        // Mettre à jour le crédit
        $credit->remboursement_hebdo = $remboursementHebdoTheorique;
        $credit->save();
    }
    
    return $remboursementHebdoTheorique;
}

  // Calcul du montant dû cette semaine
// Calcul du montant dû cette semaine
private function calculerMontantDuCetteSemaine($credit)
{
    if (!$credit->date_octroi) {
        return 0;
    }
    
    $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
    $semainesEcoulees = $dateDebut->diffInWeeks(now());
    $semaineActuelle = min($semainesEcoulees + 1, 16);
    
    // Pour la dernière semaine, montant restant
    if ($semaineActuelle == 16) {
        $totalDejaPaye = $credit->paiements->sum('montant_paye');
        return max(0, $credit->montant_total - $totalDejaPaye);
    }
    
    // IMPORTANT : VÉRIFIER ET CORRIGER le remboursement hebdo d'abord
    if ($credit instanceof Credit && $credit->type_credit === 'individuel') {
        $remboursementHebdoCorrige = $this->verifierEtCorrigerRemboursementHebdo($credit);
        return $remboursementHebdoCorrige;
    } elseif ($credit instanceof CreditGroupe) {
        return $credit->remboursement_hebdo_total ?? ($credit->montant_total / 16);
    }
    
    return $credit->remboursement_hebdo ?? ($credit->montant_total / 16);
}


    /**
 * Calcule le remboursement hebdomadaire pour un crédit individuel
 */
private function calculerRemboursementHebdoIndividuel(Credit $credit): float
{
    if ($credit->remboursement_hebdo) {
        return $credit->remboursement_hebdo;
    }
    
    // Calculer selon les formules EXACTES du modèle Credit
    $montantAccorde = $credit->montant_accorde;
    
    // Appliquer vos formules exactes
    if ($montantAccorde >= 100 && $montantAccorde <= 500) {
        $montantTotal = $montantAccorde * 0.308666 * 4;
    } elseif ($montantAccorde >= 501 && $montantAccorde <= 1000) {
        $montantTotal = $montantAccorde * 0.3019166667 * 4;
    } elseif ($montantAccorde >= 1001 && $montantAccorde <= 1599) {
        $montantTotal = $montantAccorde * 0.30866 * 4;
    } elseif ($montantAccorde >= 2000 && $montantAccorde <= 5000) {
        $montantTotal = $montantAccorde * 0.2985666667 * 4;
    } else {
        $montantTotal = $montantAccorde * 0.30 * 4; // Par défaut
    }
    
    return $montantTotal / 16;
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

 private function genererEcritureComptablePaiement($compte, $credit, $montant, $repartition, $reference)
{
    $journal = JournalComptable::where('type_journal', 'banque')->first();
    
    if (!$journal) {
        Log::warning('Journal banque non trouvé pour écriture comptable');
        return;
    }

    // DEBUG: Vérifier les valeurs de répartition
    Log::info('DEBUG - Génération écriture comptable', [
        'reference' => $reference,
        'montant_total' => $montant,
        'repartition_capital' => $repartition['capital'],
        'repartition_interets' => $repartition['interets'],
        'somme_repartition' => $repartition['capital'] + $repartition['interets'],
        'compte' => $compte->numero_compte,
        'credit_id' => $credit->id
    ]);

    // Débit: Compte membre (capital) - CORRECTION DU COMPTE
    if ($repartition['capital'] > 0) {
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'paiement_credit_capital',
            'compte_number' => '411000', // Compte débiteur capital
            'libelle' => "Remboursement capital crédit - Client: {$compte->nom} - Crédit ID: {$credit->id}",
            'montant_debit' => $repartition['capital'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'devise' => 'USD',
            'statut' => 'comptabilise',
        ]);
    }

    // Débit: Compte membre (intérêts) - CORRECTION DU COMPTE
    if ($repartition['interets'] > 0) {
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'paiement_credit_interets',
            'compte_number' => '411000', // Compte débiteur intérêts (même compte)
            'libelle' => "Paiement intérêts crédit - Client: {$compte->nom} - Crédit ID: {$credit->id}",
            'montant_debit' => $repartition['interets'],
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'devise' => 'USD',
            'statut' => 'comptabilise',
        ]);
    }

    // Crédit: Compte recouvrement (capital) - CORRECTION DU COMPTE
    if ($repartition['capital'] > 0) {
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'recouvrement_capital',
            'compte_number' => '751100', // Compte créditeur capital
            'libelle' => "Recouvrement capital crédit - Client: {$compte->nom} - Crédit ID: {$credit->id}",
            'montant_debit' => 0,
            'montant_credit' => $repartition['capital'],
            'date_ecriture' => now(),
            'devise' => 'USD',
            'statut' => 'comptabilise',
        ]);
    }

    // Crédit: Compte produits financiers (intérêts) - CORRECTION DU COMPTE
    if ($repartition['interets'] > 0) {
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'revenus_interets',
            'compte_number' => '758100', // Compte créditeur intérêts
            'libelle' => "Revenus intérêts crédit - Client: {$compte->nom} - Crédit ID: {$credit->id}",
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

/**
 * Génère l'écriture comptable pour le paiement groupe
 */
private function genererEcritureComptablePaiementGroupe($compteGroupe, $creditGroupe, $montantTotal)
{
    $journal = JournalComptable::where('type_journal', 'banque')->first();
    
    if (!$journal) {
        Log::warning('Journal banque non trouvé pour écriture comptable groupe');
        return;
    }

    $reference = 'PAY-GROUPE-' . $creditGroupe->id . '-' . now()->format('YmdHis');

    // Débit: Compte recouvrement groupe
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'recouvrement_credit_groupe',
        'compte_number' => '411100', // Compte recouvrement groupe
        'libelle' => "Recouvrement crédit groupe - " . ($compteGroupe->nom ?? 'Groupe'),
        'montant_debit' => $montantTotal,
        'montant_credit' => 0,
        'date_ecriture' => now(),
        'devise' => 'USD',
        'statut' => 'comptabilise',
    ]);

    // Crédit: Compte banque
    EcritureComptable::create([
        'journal_comptable_id' => $journal->id,
        'reference_operation' => $reference,
        'type_operation' => 'depot_banque_groupe',
        'compte_number' => '512100', // Compte banque
        'libelle' => "Dépôt recouvrement groupe - " . ($compteGroupe->nom ?? 'Groupe'),
        'montant_debit' => 0,
        'montant_credit' => $montantTotal,
        'date_ecriture' => now(),
        'devise' => 'USD',
        'statut' => 'comptabilise',
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

    /**
 * Calcule le total des paiements saisis
 */
    public function getTotalPaiementsSaisisProperty()
    {
        return array_sum(array_map('floatval', $this->paiementsMembres));
    }
    protected function getHeaderWidgets(): array
    {
        return [
            RapportStatsWidget::class,
        ];
    }

    /**
     * Génère un rapport PDF complet des crédits
     */
    /**
 * Génère un rapport HTML complet des crédits
 */
private function genererRapportHTML()
{
    try {
        // Récupérer les données combinées
        $combinedCredits = $this->getCombinedCredits();
        
        // Calculer les totaux
        $totaux = $this->calculerTotauxRapport($combinedCredits);
        
        // Préparer les données pour le HTML
        $rapportData = [
            'credits' => $this->preparerDonneesPourRapport($combinedCredits),
            'totaux' => $totaux,
            'date_rapport' => now()->format('d/m/Y'),
            'periode' => request()->get('periode') ?? now()->format('F Y'),
            'date_generation' => now()->format('d/m/Y H:i'),
        ];

        // Générer le HTML
        $html = view('filament.exports.rapport-credits-html', $rapportData)->render();
        
        $fileName = 'rapport_credits_' . now()->format('Ymd_His') . '.html';
        
        // Télécharger le HTML
        return response()->streamDownload(
            function () use ($html) {
                echo $html;
            },
            $fileName,
            [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]
        );

    } catch (\Exception $e) {
        Log::error('Erreur génération HTML: ' . $e->getMessage());
        Notification::make()
            ->title('Erreur')
            ->body('Erreur lors de la génération du rapport: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}

    /**
     * Prépare les données pour le rapport
     */
    private function preparerDonneesPourRapport(Collection $credits): array
    {
        $donnees = [];
        
        foreach ($credits as $credit) {
            // Calculer les montants spécifiques
            $totalPaiements = $this->calculerTotalPaiements($credit);
            $montantRestant = $credit->montant_total - $totalPaiements;
            $interetsAttendus = $credit->montant_total - $credit->montant_accorde;
            
            // Calculer les intérêts déjà payés
            $interetsDejaPayes = $this->calculerInteretsDejaPayes($credit);
            
            // Calculer le capital déjà remboursé
            $capitalDejaRembourse = $this->calculerCapitalDejaRembourse($credit);
            
            $donnees[] = [
                'numero_compte' => $this->getNumeroCompte($credit),
                'nom_complet' => $this->getNomComplet($credit),
                'type_credit' => $credit->type_credit === 'groupe' ? 'Groupe' : 'Individuel',
                'agent' => $credit->agent->name ?? 'N/A',
                'superviseur' => $credit->superviseur->name ?? 'N/A',
                'montant_accorde' => $credit->montant_accorde,
                'montant_total' => $credit->montant_total,
                'interets_attendus' => $interetsAttendus,
                'total_paiements' => $totalPaiements,
                'montant_restant' => $montantRestant,
                'capital_deja_rembourse' => $capitalDejaRembourse,
                'interets_deja_payes' => $interetsDejaPayes,
                'date_octroi' => $credit->date_octroi?->format('d/m/Y') ?? 'N/A',
                'date_echeance' => $credit->date_echeance?->format('d/m/Y') ?? 'N/A',
                'semaines_restantes' => $this->calculerSemainesRestantes($credit),
                'statut' => $this->determinerStatut($credit, $montantRestant),
                'remboursement_hebdo' => $credit->remboursement_hebdo ?? ($credit->montant_total / 16),
                'taux_remboursement' => $credit->montant_total > 0 ? round(($totalPaiements / $credit->montant_total) * 100, 2) : 0,
            ];
        }
        
        return $donnees;
    }

    /**
     * Calcule les totaux du rapport
     */
    private function calculerTotauxRapport(Collection $credits): array
    {
        $totaux = [
            'total_credits' => $credits->count(),
            'total_montant_accorde' => 0,
            'total_montant_total' => 0,
            'total_interets_attendus' => 0,
            'total_paiements' => 0,
            'total_montant_restant' => 0,
            'total_capital_rembourse' => 0,
            'total_interets_payes' => 0,
            'credits_individuels' => 0,
            'credits_groupe' => 0,
            'credits_en_cours' => 0,
            'credits_termines' => 0,
            'credits_en_retard' => 0,
        ];

        foreach ($credits as $credit) {
            $totalPaiements = $this->calculerTotalPaiements($credit);
            $montantRestant = $credit->montant_total - $totalPaiements;
            
            $totaux['total_montant_accorde'] += $credit->montant_accorde;
            $totaux['total_montant_total'] += $credit->montant_total;
            $totaux['total_interets_attendus'] += ($credit->montant_total - $credit->montant_accorde);
            $totaux['total_paiements'] += $totalPaiements;
            $totaux['total_montant_restant'] += $montantRestant;
            $totaux['total_capital_rembourse'] += $this->calculerCapitalDejaRembourse($credit);
            $totaux['total_interets_payes'] += $this->calculerInteretsDejaPayes($credit);
            
            if ($credit->type_credit === 'groupe') {
                $totaux['credits_groupe']++;
            } else {
                $totaux['credits_individuels']++;
            }
            
            // Déterminer le statut
            if ($montantRestant <= 0) {
                $totaux['credits_termines']++;
            } else {
                $totaux['credits_en_cours']++;
                if ($credit->date_echeance && $credit->date_echeance < now()) {
                    $totaux['credits_en_retard']++;
                }
            }
        }

        $totaux['taux_remboursement_global'] = $totaux['total_montant_total'] > 0 
            ? round(($totaux['total_paiements'] / $totaux['total_montant_total']) * 100, 2)
            : 0;

        return $totaux;
    }

    /**
     * Calcule le total des paiements pour un crédit
     */
    private function calculerTotalPaiements($credit): float
    {
        if ($credit->type_credit === 'individuel') {
            return PaiementCredit::where('credit_id', $credit->id)
                ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
                ->sum('montant_paye');
        } else {
            $groupeId = $credit->id - 100000; // Retrouver l'ID réel du groupe
            return PaiementCredit::where('credit_groupe_id', $groupeId)
                ->where('type_paiement', TypePaiement::GROUPE->value)
                ->sum('montant_paye');
        }
    }

    /**
     * Calcule les intérêts déjà payés
     */
    private function calculerInteretsDejaPayes($credit): float
    {
        if ($credit->type_credit === 'individuel') {
            return PaiementCredit::where('credit_id', $credit->id)
                ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
                ->sum('interets_payes');
        } else {
            $groupeId = $credit->id - 100000;
            return PaiementCredit::where('credit_groupe_id', $groupeId)
                ->where('type_paiement', TypePaiement::GROUPE->value)
                ->sum('interets_payes');
        }
    }

    /**
     * Calcule le capital déjà remboursé
     */
    private function calculerCapitalDejaRembourse($credit): float
    {
        if ($credit->type_credit === 'individuel') {
            return PaiementCredit::where('credit_id', $credit->id)
                ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
                ->sum('capital_rembourse');
        } else {
            $groupeId = $credit->id - 100000;
            return PaiementCredit::where('credit_groupe_id', $groupeId)
                ->where('type_paiement', TypePaiement::GROUPE->value)
                ->sum('capital_rembourse');
        }
    }

    /**
     * Calcule les semaines restantes
     */
    private function calculerSemainesRestantes($credit): int
    {
        if (!$credit->date_octroi || !$credit->date_echeance) {
            return 0;
        }

        $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
        $semainesTotal = 16;
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        
        return max(0, $semainesTotal - $semainesEcoulees);
    }

    /**
     * Détermine le statut du crédit
     */
    private function determinerStatut($credit, $montantRestant): string
    {
        if ($montantRestant <= 0) {
            return 'Terminé';
        }
        
        if ($credit->date_echeance && $credit->date_echeance < now()) {
            return 'En retard';
        }
        
        return 'En cours';
    }

    /**
     * Récupère le numéro de compte
     */
    private function getNumeroCompte($credit): string
    {
        if ($credit->type_credit === 'groupe') {
            return $credit->compte->numero_compte ?? 'GS' . str_pad($credit->id - 100000, 5, '0', STR_PAD_LEFT);
        }
        return $credit->compte->numero_compte ?? 'N/A';
    }

    /**
     * Récupère le nom complet
     */
    private function getNomComplet($credit): string
    {
        if ($credit->type_credit === 'groupe') {
            return $credit->compte->nom ?? 'Groupe ' . ($credit->compte->numero_compte ?? 'N/A');
        }
        return ($credit->compte->nom ?? '') . ' ' . ($credit->compte->prenom ?? '');
    }

    /**
     * Récupère le logo en base64
     */
    private function getLogoBase64(): string
    {
        $logoPath = public_path('images/logo.png');
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        // Logo par défaut simple
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#2c5282"/><text x="50" y="50" font-family="Arial" font-size="30" fill="white" text-anchor="middle" dy=".3em">TL</text></svg>');
    }
}