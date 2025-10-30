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
                    
                TextColumn::make('client.nom_complet')
                    ->label('Client')
                    ->visible(fn ($record) => $record && $record->type_compte === 'individuel')
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
                    
                TextColumn::make('taux_interet')
                    ->label('Taux %')
                    ->suffix('%')
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('depot')
                    ->label('Dépôt')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                     ->action(function (CompteEpargne $record, array $data) {
                        if ($record->crediter($data['montant'], $data['description'])) {
                            // Message de succès
                           Notification::make()
                                ->title('Dépôt réussi')
                                ->body("Le montant de {$data['montant']} {$record->devise} a été crédité sur le compte {$record->numero_compte}")
                                ->success()
                                ->send();
                        } else {
                            // Message d'erreur
                           Notification::make()
                                ->title('Erreur de dépôt')
                                ->body('Impossible d\'effectuer le dépôt')
                                ->danger()
                                ->send();
                        }
                    })
                    ->schema([
                        TextInput::make('montant')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Textarea::make('description')
                            ->label('Description'),
                    ]),
                    
                Action::make('retrait')
                    ->label('Retrait')
                    ->icon('heroicon-o-minus')
                    ->color('warning')
                    ->action(function (CompteEpargne $record, array $data) {
                        if ($record->debiter($data['montant'], $data['description'])) {
                            // Message de succès
                          Notification::make()
                                ->title('Retrait réussi')
                                ->body("Le montant de {$data['montant']} {$record->devise} a été retiré du compte {$record->numero_compte}")
                                ->success()
                                ->send();
                        } else {
                            // Message d'erreur
                        Notification::make()
                                ->title('Erreur de retrait')
                                ->body('Impossible d\'effectuer le retrait - solde insuffisant ou compte inactif')
                                ->danger()
                                ->send();
                        }
                    })
                    ->schema([
                        TextInput::make('montant')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                             ->maxValue(fn (CompteEpargne $record) => $record->solde),
                        Textarea::make('description')
                            ->label('Description'),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
