<?php

namespace App\Filament\Resources\CompteTransitoires\Tables;

use App\Models\CompteTransitoire;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CompteTransitoiresTable
{
    public static function configure(Table $table): Table
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        return $table
            ->query(function () use ($user) {
                $query = CompteTransitoire::query();
                
                // Si l'utilisateur a le rôle super_admin ou ChefBureau, voir tous les comptes
                if ($user && ($user->hasRole('super_admin') || $user && ($user->hasRole('ControleurAuditeur') ||  $user->hasRole('ChefBureau')))) {
                    return $query;
                }
                
                // Sinon, filtrer pour afficher seulement le compte de l'utilisateur connecté
                if ($user) {
                    return $query->where('agent_nom', $user->name);
                }
                
                // Si aucun utilisateur n'est connecté, ne rien afficher
                return $query->where('id', 0);
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Agent')
                    ->sortable()
                    ->searchable()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin') || Auth::user()?->hasRole('ChefBureau')),
                
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
                    ->url(route('filament.admin.resources.compte-transitoires.create'))
                    ->visible(fn () => Auth::user()?->hasRole('super_admin') || Auth::user()?->hasRole('ChefBureau')),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => 
                        Auth::user()?->hasRole('super_admin') || 
                        Auth::user()?->hasRole('ChefBureau') ||
                        $record->agent_nom === Auth::user()?->name
                    ),
                
                // Action pour voir les mouvements de l'agent
                Action::make('voir_mouvements')
                    ->label('Mouvements')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('filament.admin.resources.mouvements.index', ['tableFilters[compte_transitoire_id]' => $record->id])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasRole('super_admin') || Auth::user()?->hasRole('ChefBureau')),
                ]),
            ]);
    }
}