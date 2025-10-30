<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du Rôle')
                    ->schema([
                
                TextInput::make('name')
                            ->label('Nom du rôle')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                Select::make('guard_name')
                            ->label('Guard')
                            ->options([
                                'web' => 'Web',
                                'api' => 'API',
                            ])
                            ->default('web')
                            ->required(),
                    ])->columns(2),

                Section::make('Permissions')
                    ->schema([
                CheckboxList::make('permissions')
                            ->label('')
                            ->relationship('permissions', 'name')
                            ->searchable()
                            ->columns(3)
                            ->gridDirection('row')
                            ->required()

                    
            ])
         ]);
    }
}
