<?php

namespace App\Filament\Resources\Credits\Credits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('client.nom')
                ->label('Membre')
                ->sortable()
                ->searchable(),

            // TextColumn::make('cycle.numero_cycle')
            //     ->label('Cycle'),

            TextColumn::make('montant_principal')
                ->label('Montant principal'),

            TextColumn::make('taux_interet')
                ->label('Intérêt (%)'),
                
            TextColumn::make('devise')->label('Devise'),

            TextColumn::make('montant_total')
                ->label('Montant total')
                ->getStateUsing(fn($record) => $record->montant_principal * (1 + $record->taux_interet / 100)),

            TextColumn::make('statut')
                ->badge()
                ->colors([
                    'success' => 'remboursé',
                    'warning' => 'en_cours',
                    'danger' => 'retard',
                ]),
        ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
