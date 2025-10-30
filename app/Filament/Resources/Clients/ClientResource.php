<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Filament\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    // ✅ Navigation
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Membres';
     protected static string|UnitEnum|null $navigationGroup = '👨‍💼 GESTION DES MEMBRES';

       public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 

    public static function getNavigationBadgeColor(): ?string
    {
         return static::getModel()::count() > 10
         ? 'warning'
         :'primary' ;
    }
    // 🔒 Masquer la ressource si l'utilisateur n'a pas la permission
    public static function canViewInNavigation(): bool
    {
         /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_users');
    }

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    

        public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_client');
    }

    // 🔒 Contrôle des accès aux actions
    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_client');
    }

     public static function canEdit($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_client');
    }

    public static function canDelete($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_user');
    }


    
}
