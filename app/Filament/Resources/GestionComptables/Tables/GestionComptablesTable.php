<?php

namespace App\Filament\Resources\GestionComptables\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GestionComptablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('date_ecriture')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('journal.libelle_journal')
                    ->label('Journal')
                    ->sortable(),
                TextColumn::make('compte_number')
                    ->label('Compte')
                    ->searchable(),
                TextColumn::make('libelle')
                    ->searchable()
                    ->limit(50)
                    ->label('Libellé'),
                TextColumn::make('montant_debit')
                    ->money('USD')
                    ->label('Débit')
                    ->alignRight(),
                TextColumn::make('montant_credit')
                    ->money('USD')
                    ->label('Crédit')
                    ->alignRight(),
                TextColumn::make('reference_operation')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'comptabilise' => 'success',
                        'brouillon' => 'warning',
                        'annule' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('journal_id')
                    ->relationship('journal', 'libelle_journal')
                    ->label('Journal'),
                SelectFilter::make('statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'comptabilise' => 'Comptabilisé',
                        'annule' => 'Annulé',
                    ])
                    ->label('Statut'),
                Filter::make('date_ecriture')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Du'),
                        DatePicker::make('date_until')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q) => $q->whereDate('date_ecriture', '>=', $data['date_from']))
                            ->when($data['date_until'], fn ($q) => $q->whereDate('date_ecriture', '<=', $data['date_until']));
                    })
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
