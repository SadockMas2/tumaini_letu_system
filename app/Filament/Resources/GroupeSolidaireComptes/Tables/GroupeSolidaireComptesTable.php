<?php

namespace App\Filament\Resources\GroupeSolidaireComptes\Tables;

use App\Filament\Resources\Mouvements\MouvementResource;
use App\Models\Compte;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GroupeSolidaireComptesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_compte')
                    ->label('Numéro Compte')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nom')
                    ->label('Nom du Groupe')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('groupeSolidaire.nom_groupe')
                    ->label('Groupe Associé')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('devise')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'USD' => 'success',
                        'CDF' => 'warning',
                    }),

                TextColumn::make('solde')
                    ->label('Solde')
                    ->money(fn (Compte $record) => $record->devise === 'USD' ? 'USD' : 'CDF')
                    ->sortable(),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'inactif' => 'danger',
                        'suspendu' => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
               SelectFilter::make('devise')
                    ->options([
                        'USD' => 'USD',
                        'CDF' => 'CDF',
                    ])
                    ->label('Devise'),


                SelectFilter::make('statut')
                    ->options([
                        'actif' => 'Actif',
                        'inactif' => 'Inactif',
                        'suspendu' => 'Suspendu',
                    ])
                    ->label('Statut'),

            Filter::make('created_at')

            ->schema([
                        DatePicker::make('created_from')
                            ->label('Du'),
                        DatePicker::make('created_until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),


            ])

            ->headerActions([
                Action::make('create_compte')
                    ->label('Ouvrir un compte du groupe')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_compte');
                    })
                    ->url(route('filament.admin.resources.groupe-solidaire-comptes.create')), // ✅ Correct pour création
            ])
           
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('mouvements')
                    ->label('Mouvements')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(fn (Compte $record): string => MouvementResource::getUrl('index', ['tableFilters[compte_id][value]' => $record->id])),
            ])
            ->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
