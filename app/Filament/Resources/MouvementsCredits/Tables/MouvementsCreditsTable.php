<?php

namespace App\Filament\Resources\MouvementsCredits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MouvementsCreditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
            TextColumn::make('credit.client.nom')
                ->label('Membre'),
            TextColumn::make('role_source')->label('Source'),
            TextColumn::make('role_dest')->label('Destination'),
            TextColumn::make('montant')->money('USD'),
            TextColumn::make('statut')->badge()
                ->colors([
                    'primary' => 'en_attente',
                    'success' => 'valide',
                    'danger' => 'rejete',
                ]),
            TextColumn::make('created_at')->label('Date')->dateTime()
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
