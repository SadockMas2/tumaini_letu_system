<?php

namespace App\Filament\Resources\CompteTransitoires\Tables;

use App\Models\CompteTransitoire;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompteTransitoiresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(CompteTransitoire::query()) // Afficher tous les comptes transitoires
            ->columns([
                TextColumn::make('user.name')
                    ->label('Agent')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('agent_nom')
                    ->label('Nom Agent')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('devise')
                    ->label('Devise')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'USD' => 'success',
                        'CDF' => 'warning', 
                        default => 'gray',
                    }),
                
                TextColumn::make('solde')
                    ->label('Solde Total')
                    ->money(fn ($record) => $record->devise)
                    ->color(fn ($record) => $record->solde > 0 ? 'success' : 'danger')
                    ->sortable(),
                
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'inactif' => 'danger',
                        'suspendu' => 'warning',
                        default => 'gray',
                    }),
                
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
                // Filtres optionnels
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Nouveau Compte Agent')
                    ->icon('heroicon-o-user-plus')
                    ->url(route('filament.admin.resources.compte-transitoires.create')),
            ])
            ->recordActions([
                EditAction::make(),
                // Action pour voir les mouvements de l'agent
                Action::make('voir_mouvements')
                    ->label('Mouvements')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('filament.admin.resources.mouvements.index', ['tableFilters[compte_transitoire_id]' => $record->id])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}