<?php

namespace App\Filament\Resources\CompteTransitoires\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompteTransitoiresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(function () {
                return Auth::user()->compteTransitoires()->getQuery();
            })
            ->columns([
                TextColumn::make('user.name')->label('Agent'),
                TextColumn::make('devise'),
                TextColumn::make('solde')->money('usd', true),
                TextColumn::make('statut'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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