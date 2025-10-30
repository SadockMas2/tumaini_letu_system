<?php

namespace App\Filament\Resources\Users\Schemas;


use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom complet')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Adresse email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->required(fn ($operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->maxLength(255),

                Section::make('Photo de profil')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->directory('users')
                            ->avatar()
                            ->maxSize(2048),
                    ]),
            
                Section::make('Rôles et Permissions')
                    ->schema([
                        Select::make('roles')
                            ->label('Rôles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->options(fn () => Role::where('guard_name', 'filament')->pluck('name', 'id'))
                            ->preload()
                            ->searchable()
                            ->required()
                            ->loadingMessage('Chargement des rôles...')
                            ->noSearchResultsMessage('Aucun rôle trouvé')
                            ->dehydrated(true),

                        Select::make('permissions')
                            ->label('Permissions spécifiques')
                            ->multiple()
                            ->relationship('permissions', 'name')
                            ->options(fn () => Permission::where('guard_name', 'filament')->pluck('name', 'id'))
                            ->preload()
                            ->searchable()
                            ->loadingMessage('Chargement des permissions...')
                            ->noSearchResultsMessage('Aucune permission trouvée')
                            ->helperText('Permissions directes (en plus des permissions des rôles)')
                            ->dehydrated(true),
                    ]),
            ]);
    }
}