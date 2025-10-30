<?php

namespace App\Filament\Resources\TypeComptes\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Container\Attributes\Auth;
use PhpParser\Node\Stmt\Label;

class TypeComptesTable
{
    public static function configure(Table $table): Table
    {
        return $table
             ->columns([
                TextColumn::make('designation')->label('Désignation')->sortable(),
                TextColumn::make('description')->label('Description')->limit(50),
                TextColumn::make('created_at')->label('Créé le')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Modifié le')->dateTime()->sortable(),
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
