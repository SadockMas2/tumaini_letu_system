<?php

namespace App\Filament\Resources\Comptes\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Credit;
use App\Models\CreditGroupe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ComptesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_compte')
                    ->label('Compte')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'Groupe' : 'Individuel')
                    ->color(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'success' : 'primary'),
                
                TextColumn::make('type_compte')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'GROUPE' : 'INDIVIDUEL')
                    ->color(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'success' : 'primary'),
                
                TextColumn::make('numero_membre')->label('Numéro Membre')->searchable()->sortable(),
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('postnom')->label('Post-nom')->searchable()->sortable(),
                TextColumn::make('prenom')->label('Prénom')->searchable()->sortable(),
                
                TextColumn::make('devise')
                    ->label('Devise')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'USD' => 'success', 'CDF' => 'warning', default => 'gray',
                    }),
                
                TextColumn::make('solde')
                    ->label('Solde')
                    ->money(fn ($record) => $record->devise)
                    ->color(fn ($record) => $record->solde > 0 ? 'success' : 'danger')
                    ->sortable(),
                
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success', 'inactif' => 'danger', 'suspendu' => 'warning', default => 'gray',
                    }),
                
                TextColumn::make('credits_count')
                    ->label('Crédits Actifs')
                    ->counts('credits')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'primary' : 'gray'),
                
                TextColumn::make('credits_en_attente_count')
                    ->label('Dem. Attente')
                    ->getStateUsing(fn ($record) => $record->credits()->where('statut_demande', 'en_attente')->count())
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'gray'),
            ])
            ->recordActions([
                // Actions principales
                ViewAction::make()->label('')->tooltip('Voir'),
                EditAction::make()->label('')->tooltip('Modifier'),

                // Voir Détails - ADAPTÉ POUR GROUPES
                Action::make('voir_details')
                    ->label('')
                    ->tooltip(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'Détails Groupe' : 'Détails Compte')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                     ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_compte');
                    })
                    ->url(fn ($record) => route('comptes.details', ['compte_id' => $record->id])),

                // Demander Crédit - ADAPTÉ POUR GROUPES
                Action::make('demande_credit')
                    ->label('')
                    ->tooltip(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'Demander Crédit Groupe' : 'Demander Crédit')
                    ->color('primary')
                    ->icon('heroicon-o-credit-card')
                     ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_comptespecial');
                    })
                    ->url(fn ($record) => route('credits.create', ['compte_id' => $record->id])),

                // Payer Crédit - ADAPTÉ POUR GROUPES
                Action::make('payer_credit')
                    ->label('')
                    ->tooltip(fn ($record) => str_starts_with($record->numero_compte, 'GS') ? 'Payer Crédit Groupe' : 'Payer Crédit')
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(function ($record) {
                        if (str_starts_with($record->numero_compte, 'GS')) {
                            // Pour les groupes: vérifier s'il y a des crédits groupe actifs
                            return CreditGroupe::where('compte_id', $record->id)
                                ->where('statut_demande', 'approuve')
                                ->where('montant_total', '>', 0)
                                ->exists();
                        } else {
                            // Pour les individuels: vérifier s'il y a des crédits individuels actifs
                            return $record->credits()
                                ->where('statut_demande', 'approuve')
                                ->where('montant_total', '>', 0)
                                ->exists();
                        }
                    })
                    ->url(fn ($record) => route('credits.payment', ['compte_id' => $record->id])),

                // Accorder Crédit Individuel - UNIQUEMENT POUR INDIVIDUELS
                Action::make('accorder_credit')
                    ->label('')
                    ->tooltip('Accorder Crédit Individuel')
                    ->color('warning')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn ($record) => 
                        !str_starts_with($record->numero_compte, 'GS') && // Uniquement comptes individuels
                        Credit::where('compte_id', $record->id)->where('statut_demande', 'en_attente')->exists()
                    )
                     ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_comptespecial');
                    })
                    ->url(function ($record) {
                        $creditEnAttente = Credit::where('compte_id', $record->id)->where('statut_demande', 'en_attente')->first();
                        return $creditEnAttente 
                            ? route('credits.approval', ['credit_id' => $creditEnAttente->id])
                            : null;
                    }),

                // Accorder Crédit Groupe - UNIQUEMENT POUR GROUPES
                Action::make('accorder_credit_groupe')
                    ->label('')
                    ->tooltip('Accorder Crédit Groupe')
                    ->color('orange')
                    ->icon('heroicon-o-user-group')
                    ->visible(fn ($record) => 
                        str_starts_with($record->numero_compte, 'GS') &&
                        CreditGroupe::where('compte_id', $record->id)->where('statut_demande', 'en_attente')->exists()
                    )
                     ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_comptespecial');
                    })
                    ->url(function ($record) {
                        $creditGroupeEnAttente = CreditGroupe::where('compte_id', $record->id)
                            ->where('statut_demande', 'en_attente')
                            ->first();
                        
                        return $creditGroupeEnAttente 
                            ? route('credits.approval-groupe', $creditGroupeEnAttente->id)
                            : null;
                    }),

                // NOUVEAU: Voir État Répartition Groupe
                Action::make('voir_repartition_groupe')
                    ->label('')
                    ->tooltip('État Répartition Groupe')
                    ->color('purple')
                    ->icon('heroicon-o-document-chart-bar')
                    ->visible(fn ($record) => 
                        str_starts_with($record->numero_compte, 'GS') &&
                        CreditGroupe::where('compte_id', $record->id)->where('statut_demande', 'approuve')->exists()
                    )
                     ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_comptespecial');
                    })
                    ->url(function ($record) {
                        $creditGroupeApprouve = CreditGroupe::where('compte_id', $record->id)
                            ->where('statut_demande', 'approuve')
                            ->first();
                        
                        return $creditGroupeApprouve 
                            ? route('credits.details-groupe', $creditGroupeApprouve->id)
                            : null;
                    }),

                // NOUVEAU: Voir Échéanciers Groupe
                Action::make('voir_echeanciers_groupe')
                    ->label('')
                    ->tooltip('Échéanciers Groupe')
                    ->color('green')
                    ->icon('heroicon-o-calendar')
                    ->visible(fn ($record) => 
                        str_starts_with($record->numero_compte, 'GS') &&
                        CreditGroupe::where('compte_id', $record->id)->where('statut_demande', 'approuve')->exists()
                    )
                     ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_comptespecial');
                    })
                    ->url(function ($record) {
                        $creditGroupeApprouve = CreditGroupe::where('compte_id', $record->id)
                            ->where('statut_demande', 'approuve')
                            ->first();
                        
                        return $creditGroupeApprouve 
                            ? route('credits.echeanciers-groupe', $creditGroupeApprouve->id)
                            : null;
                    }),

                DeleteAction::make()->label('')->tooltip('Supprimer'),
            ])
            ->headerActions([
                Action::make('create_compte')
                    ->label('Ouvrir un compte')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_compte');
                    })
                    ->url(route('filament.admin.resources.comptes.create')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Action::make('create')
                    ->label('Créer un compte')
                    ->url(route('filament.admin.resources.comptes.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('numero_compte', 'asc');
    }
}