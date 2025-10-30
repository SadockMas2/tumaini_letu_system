<?php

namespace App\Filament\Resources\Comptes;

use App\Filament\Resources\Comptes\Pages\CreateCompte;
use App\Filament\Resources\Comptes\Pages\EditCompte;
use App\Filament\Resources\Comptes\Pages\ListComptes;
use App\Filament\Resources\Comptes\Schemas\CompteForm;
use App\Filament\Resources\Comptes\Tables\ComptesTable;
use App\Models\Compte;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CompteResource extends Resource
{
    protected static ?string $model = Compte::class;

      protected static ?string $navigationLabel = 'Compte membre';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string|UnitEnum|null $navigationGroup =  '👨‍💼 GESTION DES MEMBRES';

       public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 

    public static function form(Schema $schema): Schema
    {
        return CompteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComptesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComptes::route('/'),
            'create' => CreateCompte::route('/create'),
            'edit' => EditCompte::route('/{record}/edit'),
        ];
    }

    
        public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_compte');
    }

    // 🔒 Contrôle des accès aux actions
    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_compte');
    }

     public static function canEdit($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_ccmpte');
    }

    public static function canDelete($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_compte');
    }
}
