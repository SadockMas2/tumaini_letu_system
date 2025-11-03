<?php

namespace App\Filament\Resources\Tresoreries\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TresoreriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type_caisse')
                    ->badge()
                    ->color(fn ($state) => $state === 'petite_caisse' ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state === 'petite_caisse' ? 'Petite Caisse' : 'Grande Caisse'),
                
                TextColumn::make('nom')
                    ->label('Nom de la Caisse')
                    ->searchable(),
                
                // CORRECTION: Utilisez 'solde' au lieu de 'solde_actuel'
                TextColumn::make('solde')
                    ->money(fn ($record) => $record->devise)
                    ->label('Solde Actuel')
                    ->color(fn ($record) => $record->solde > 0 ? 'success' : 'danger'),
                
                TextColumn::make('plafond')
                    ->money(fn ($record) => $record->devise)
                    ->label('Plafond'),
                
                TextColumn::make('devise')
                    ->badge()
                    ->color(fn ($state) => $state === 'USD' ? 'success' : 'warning'),
                
                IconColumn::make('statut')
                    ->label('Statut')
                    ->boolean()
                    // CORRECTION: Utilisez 'solde' au lieu de 'solde_actuel'
                    ->getStateUsing(fn ($record) => $record->solde > 0)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('historique_mouvements')
                    ->label('Historique')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn ($record) => route('filament.admin.resources.mouvements.index', ['tableFilters[caisse_id][value]' => $record->id])),
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