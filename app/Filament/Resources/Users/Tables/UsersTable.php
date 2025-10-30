<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom complet')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Adresse email')
                    ->searchable(),

                ImageColumn::make('image')
                    ->label('Photo de profil'),

                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(', ')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                Action::make('create_user')
                    ->label('Creer un Agent')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_user');
                    })
                    ->url(route('filament.admin.resources.users.create')), // ✅ Correct pour création
            ])

            ->filters([])

            ->recordActions([
                EditAction::make('edit_user')
                    ->label('Modifier')
                    ->visible(function ($record) {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('edit_user');
                    }),
                  
                
                DeleteAction::make('delete_user')
                    ->label('Supprimer')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function ($record) {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('delete_user');
                    }),
                 
                    
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function () {
                            /** @var User|null $user */
                            $user = Auth::user();
                            return $user && $user->can('delete_user');
                        }),
                ]),
            ]);
    }
}