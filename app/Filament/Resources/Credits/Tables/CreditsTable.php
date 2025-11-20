<?php

namespace App\Filament\Resources\Credits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CreditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('compte.numero_compte')
                    ->searchable()
                    ->sortable()
                    ->label('Compte'),
                
                textColumn::make('type_credit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individuel' => 'info',
                        'groupe' => 'success',
                        default => 'gray',
                    }),
                
                TextColumn::make('montant_demande')
                    ->money('USD')
                    ->sortable(),
                
                TextColumn::make('montant_accorde')
                    ->money('USD')
                    ->sortable(),
                
                TextColumn::make('agent.name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('superviseur.name')
                    ->label('Superviseur')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('statut_demande')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approuve' => 'success',
                        'en_attente' => 'warning',
                        'rejete' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('date_octroi')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type_credit')
                    ->options([
                        'individuel' => 'Individuel',
                        'groupe' => 'Groupe',
                    ]),
                
                SelectFilter::make('statut_demande')
                    ->options([
                        'en_attente' => 'En Attente',
                        'approuve' => 'Approuvé',
                        'rejete' => 'Rejeté',
                    ]),
                
                SelectFilter::make('agent_id')
                    ->relationship('agent', 'name')
                    ->label('Agent'),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbactions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
