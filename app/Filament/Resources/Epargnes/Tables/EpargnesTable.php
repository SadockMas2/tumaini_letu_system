<?php

namespace App\Filament\Resources\Epargnes\Tables;

use App\Models\User;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Illuminate\Support\Facades\Auth;

class EpargnesTable
{
    public static function configure(Table $table): Table
    {
        return $table
          ->query(function () {
                return Auth::user()->Epargne()->getQuery();
            })
        
            ->columns([
                TextColumn::make('numero_compte_membre')
                    ->label('N° compte')
                    ->searchable(),

                TextColumn::make('client_nom')
                    ->label('Membre/Groupe')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type_epargne')
                    ->label('Type')
                    ->colors([
                        'success' => 'individuel',
                        'warning' => 'groupe_solidaire',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'individuel' ? 'Individuel' : 'Groupe'),

                TextColumn::make('agent_nom')
                    ->label('Agent')
                    ->searchable(),

                TextColumn::make('cycle.numero_cycle')
                    ->label('Cycle')
                    ->sortable(),

                // COLONNE MONTANT D'ÉPARGNE - AMÉLIORÉE
                TextColumn::make('montant')
                    ->label('Montant Épargne')
                    ->money(fn ($record) => $record->devise)
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->description(fn ($record) => 'Solde initial du cycle')
                    ->tooltip('Montant correspondant au solde initial du cycle'),

                TextColumn::make('solde_apres_membre')
                    ->label('Solde après dépôt')
                    ->money(fn ($record) => $record->devise)
                    ->sortable(),

                TextColumn::make('devise')
                    ->colors([
                        'primary' => 'USD',
                        'success' => 'CDF',
                    ]),

                TextColumn::make('date_apport')
                    ->label('Date dépôt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('statut')
                    ->colors([
                        'warning' => 'en_attente_dispatch',
                        'info' => 'en_attente_validation',
                        'success' => 'valide',
                        'danger' => 'rejet',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'en_attente_dispatch' => 'En attente dispatch',
                        'en_attente_validation' => 'En attente validation',
                        'valide' => 'Validé',
                        'rejet' => 'Rejeté',
                        default => $state
                    }),

                // AJOUT : Colonne pour voir le solde initial du cycle (optionnel)
                TextColumn::make('cycle.solde_initial')
                    ->label('Solde Initial Cycle')
                    ->money(fn ($record) => $record->devise)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Pour vérification'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                // Action::make('create_epargne')
                //     ->label('Epargner')
                //     ->icon('heroicon-o-currency-dollar')
                //     ->visible(function () {
                //         /** @var User|null $user */
                //         $user = Auth::user();
                //         return $user && $user->can('create_epargne');
                //     })
                //     ->url(route('filament.admin.resources.epargnes.create')), // ✅ Correct pour création
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_epargne')
                    ->label('Type d\'Épargne')
                    ->options([
                        'individuel' => 'Individuelle',
                        'groupe_solidaire' => 'Groupe Solidaire',
                    ]),

                Tables\Filters\SelectFilter::make('devise')
                    ->label('Devise')
                    ->options([
                        'USD' => 'USD',
                        'CDF' => 'CDF',
                    ]),

                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'en_attente_dispatch' => 'En attente dispatch',
                        'en_attente_validation' => 'En attente validation',
                        'valide' => 'Validé',
                        'rejet' => 'Rejeté',
                    ]),

                // FILTRE PAR MONTANT (optionnel)
                Tables\Filters\Filter::make('montant_min')
                    ->schema([
                        TextInput::make('montant_min')
                            ->label('Montant minimum')
                            ->numeric()
                            ->placeholder('Ex: 100'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['montant_min'], fn ($query, $montant) => 
                                $query->where('montant', '>=', $montant)
                            );
                    }),

                Tables\Filters\Filter::make('montant_max')
                    ->schema([
                        TextInput::make('montant_max')
                            ->label('Montant maximum')
                            ->numeric()
                            ->placeholder('Ex: 1000'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['montant_max'], fn ($query, $montant) => 
                                $query->where('montant', '<=', $montant)
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),

                // ACTION POUR VOIR LES DÉTAILS DU CYCLE
                Action::make('voir_cycle')
                    ->label('')
                    ->tooltip('Voir le cycle')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                  
                    ->url(fn ($record) => $record->cycle 
                        ? route('filament.admin.resources.cycles.edit', $record->cycle->id)
                        : null
                    )
                    ->visible(fn ($record) => !is_null($record->cycle)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date_apport', 'desc');
    }
}