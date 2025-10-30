<?php

namespace App\Filament\Resources\GestionTresoreries\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GestionTresoreriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
           ->columns([
            TextColumn::make('nom')
                    ->searchable()
                    ->sortable(),
            TextColumn::make('solde_actuel')
                    ->money('USD')
                    ->label('Solde Actuel'),
            TextColumn::make('responsable.name')
                    ->label('Responsable'),
            TextColumn::make('plafond_journalier')
                    ->money('USD')
                    ->label('Plafond Journalier'),
            TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'inactif' => 'danger',
                        'bloque' => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->options([
                        'actif' => 'Actif',
                        'inactif' => 'Inactif',
                        'bloque' => 'Bloqué',
                    ])
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('voir_mouvements')
                    ->label('Mouvements')
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn ($record) => MouvementCoffreResource::getUrl('index', ['coffre_id' => $record->id])),
            ])
            ->toolbarActions([]);
    }
}
