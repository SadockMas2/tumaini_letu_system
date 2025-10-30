<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\TypeCompte;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_membre')
                    ->label('Numéro membre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nom')->searchable(),
                TextColumn::make('postnom')->searchable(),
                TextColumn::make('prenom')->searchable(),
                TextColumn::make('date_naissance')->date()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('image')
                    ->label('Photo du membre'),


                TextColumn::make('telephone')->searchable(),
                TextColumn::make('adresse')->searchable(),
                TextColumn::make('activites')->searchable(),
                TextColumn::make('ville')->searchable(),
                TextColumn::make('pays')->searchable(),
                TextColumn::make('code_postal')->searchable(),
                TextColumn::make('id_createur')->numeric()->sortable(),
                TextColumn::make('status')->searchable(),
                TextColumn::make('identifiant_national')->searchable(),
                TextColumn::make('type_client')->searchable(),
                TextColumn::make('etat_civil')->searchable(),
                TextColumn::make('type_compte')
                    ->label('Type de compte')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('signature')
                    ->label('Signature'),

                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
              ->headerActions([
                Action::make('clients.create')
                ->Label('Ajouter un membre')
                ->icon('heroicon-o-user-plus')
                ->visible(function () {
                    /** @var User|null $user */
                    $user = Auth::user();
                    return $user && $user->can('create_client');
                })
                 ->url(route('filament.admin.resources.clients.create')),
            ])

            
            ->recordActions([
               
            ])
          ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}