<?php

namespace App\Filament\Resources\CompteEpargnes\Tables;

use App\Filament\Exports\CompteEpargneExporter;
use App\Models\CompteEpargne;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
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
                    
                    ->sortable(),  // PAS de searchable() ici
                    
                TextColumn::make('type_compte')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individuel' => 'success',
                        'groupe_solidaire' => 'primary',
                    }),
                    
                // Colonne pour afficher le nom complet
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
                    ->sortable(),  // PAS de searchable() ici

                TextColumn::make('groupeSolidaire.nom_groupe')
                    ->label('Groupe')
                    ->visible(fn ($record) => $record && $record->type_compte === 'groupe_solidaire')
                    ->sortable(),  // PAS de searchable() ici
                    
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
            ->headerActions([
                ExportAction::make()
                    ->exporter(CompteEpargneExporter::class)
                    ->label('Exporter')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                Action::make('voir_details_epargne')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('comptes-epargne.details', ['compte_epargne_id' => $record->id]))
                    ->visible(fn () => Auth::user()?->can('view_comptetransitoire')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportAction::make()
                        ->exporter(CompteEpargneExporter::class)
                ]),
            ]);
    }
}