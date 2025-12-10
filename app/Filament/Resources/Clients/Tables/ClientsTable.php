<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
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
use Filament\Tables\Columns\ImageColumn;
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
                ImageColumn::make('image')
                    ->label('Photo du membre')
                    ->circular()
                    ->defaultImageUrl(asset('/images/default-avatar.png')),

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
                ImageColumn::make('signature')
                    ->label('Signature')
                    ->defaultImageUrl(url('/images/default-signature.png')),

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

     
     Action::make('galerie_clients')
    ->label('Galerie des membres')
    ->icon('heroicon-o-photo')
    ->url(route('galerie.clients')) // ← Utilisez le nom de route
    ->openUrlInNewTab() // Recommandé pour ouvrir dans un nouvel onglet
    ->visible(fn () => Auth::user()?->can('view_client')),

                    

                Action::make('clients.create')
                    ->label('Ajouter un membre')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_client');
                    })
                    ->url(route('filament.admin.resources.clients.create')),
            ])
            ->recordActions([

                EditAction::make(),



                ViewAction::make() 
                    ->label('Voir détails')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Détails du membre: {$record->nom} {$record->prenom}")
                    ->modalContent(function ($record) {
                        return view('filament.clients.view-modal', [
                            'client' => $record
                        ]);
                    })
                    ->modalWidth('4xl')
                    ->slideOver()
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('view_client');
                    }),

                    
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Action::make(),
                ]),
            ]);
    }
}