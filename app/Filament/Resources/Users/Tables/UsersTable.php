<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Exports\UserExporter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
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
                    ->label('Créer un Agent')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_user');
                    })
                    ->url(route('filament.admin.resources.users.create')),
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
                
         
            ])
            
            ->toolbarActions([
                // ✅ CORRECTION : Déplacer l'exportation ici si vous voulez l'exporter en masse
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(UserExporter::class)
                  
                    
                   
                ]),
            ]);
    }
}