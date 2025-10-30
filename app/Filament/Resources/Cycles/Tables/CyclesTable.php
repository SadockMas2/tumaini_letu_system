<?php

namespace App\Filament\Resources\Cycles\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                 TextColumn::make('numero_cycle')
                    ->label('N° Cycle')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('client_nom')
                    ->label('Client/Groupe')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type_cycle')
                    ->label('Type')
                    ->colors([
                        'success' => 'individuel',
                        'warning' => 'groupe_solidaire',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'individuel' ? 'Individuel' : 'Groupe'),

                TextColumn::make('devise')
                    ->colors([
                        'primary' => 'USD',
                        'success' => 'CDF',
                    ]),

                TextColumn::make('solde_initial')
                    ->label('Solde Initial')
                    ->money(fn ($record) => $record->devise)
                    ->sortable(),

                TextColumn::make('date_debut')
                    ->label('Début')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_fin')
                    ->label('Fin')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('statut')
                    ->colors([
                        'success' => 'ouvert',
                        'danger' => 'clôturé',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

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
            ->filters([
               SelectFilter::make('type_cycle')
                    ->label('Type de Cycle')
                    ->options([
                        'individuel' => 'Individuel',
                        'groupe_solidaire' => 'Groupe Solidaire',
                    ]),

              SelectFilter::make('devise')
                    ->label('Devise')
                    ->options([
                        'USD' => 'USD',
                        'CDF' => 'CDF',
                    ]),

             SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'ouvert' => 'Ouvert',
                        'clôturé' => 'Clôturé',
                    ]),
            ])

            ->headerActions([
                Action::make('create_epargne')
                    ->label('Creer un cycle')
                    ->icon('heroicon-o-calendar-days')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_epargne');
                    })
                    ->url(route('filament.admin.resources.cycles.create')), // ✅ Correct pour création
            ])
            
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),

                 Action::make('cloturer')
                    ->label('Clôturer')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->statut = 'clôturé';
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->statut === 'ouvert'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
