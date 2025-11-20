<?php

namespace App\Filament\Resources\CompteEpargnes\Tables;

use App\Models\CompteEpargne;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompteEpargnesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_compte')
                    ->label('Numéro Compte')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('type_compte')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individuel' => 'success',
                        'groupe_solidaire' => 'primary',
                    }),
                    
                // Colonne unique pour afficher le nom complet selon le type de compte
                TextColumn::make('nom_complet')
                    ->label('Titulaire')
                    ->getStateUsing(function (CompteEpargne $record) {
                        if ($record->type_compte === 'individuel' && $record->client) {
                            return $record->client->nom_complet;
                        } elseif ($record->type_compte === 'groupe_solidaire' && $record->groupeSolidaire) {
                            return $record->groupeSolidaire->nom_groupe . ' (Groupe)';
                        }
                        return 'N/A';
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('groupeSolidaire.nom_groupe')
                    ->label('Groupe')
                    ->visible(fn ($record) => $record && $record->type_compte === 'groupe_solidaire')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('solde')
                    ->label('Solde')
                    ->money(fn ($record) => $record ? $record->devise : 'USD')
                    ->sortable(),
                    
                TextColumn::make('devise')
                    ->label('Devise')
                    ->sortable(),
                    
                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'inactif' => 'warning',
                        'suspendu' => 'danger',
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type_compte')
                    ->options([
                        'individuel' => 'Individuel',
                        'groupe_solidaire' => 'Groupe Solidaire',
                    ]),
                    
                SelectFilter::make('devise')
                    ->options([
                        'USD' => 'USD',
                        'CDF' => 'CDF',
                    ]),
                    
                SelectFilter::make('statut')
                    ->options([
                        'actif' => 'Actif',
                        'inactif' => 'Inactif',
                        'suspendu' => 'Suspendu',
                    ]),
            ])
            ->recordActions([
                // Action pour voir les détails du compte épargne
                Action::make('voir_details_epargne')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('comptes-epargne.details', ['compte_epargne_id' => $record->id]))
                    ->visible(fn () => Auth::user()?->can('view_comptetransitoire')),

                // // Action pour voir les mouvements du compte épargne
                // Action::make('voir_mouvements_epargne')
                //     ->label('Relevé')
                //     ->icon('heroicon-o-document-text')
                //     ->color('primary')
                //     ->url(fn ($record) => route('comptes-epargne.mouvements', ['compte_epargne_id' => $record->id]))
                //     ->visible(fn () => Auth::user()?->can('view_comptetransitoire')),

         
            ])
            ->toolbarActions([
                BulkActionGroup::make([
               
                ]),
            ]);
    }
}