<?php

namespace App\Filament\Resources\Coffres\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Filament\Actions\DeleteAction;

class CoffresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('solde_actuel')
                    ->money(fn ($record) => $record->devise)
                    ->label('Solde Actuel'),
                TextColumn::make('devise')
                    ->badge()
                    ->color(fn ($state) => $state === 'USD' ? 'success' : 'warning'),
                TextColumn::make('responsable.name')
                    ->label('Responsable'),
            ])
            
            ->filters([])
            ->headerActions([
                Action::make('coffre.create')
                ->Label('Ajouter un coffre')
                ->icon('heroicon-o-user-plus')
                ->visible(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    return $user && $user->can('create_client');
                })
                 ->url(route('filament.admin.resources.coffre.create')),
            ])

            ->recordActions([
                // EditAction::make(),
               
            ])
            ->toolbarActions([]);
    }
}
