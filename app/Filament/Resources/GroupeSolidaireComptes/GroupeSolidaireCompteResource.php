<?php

namespace App\Filament\Resources\GroupeSolidaireComptes;

use App\Filament\Resources\GroupeSolidaireComptes\Pages\CreateGroupeSolidaireCompte;
use App\Filament\Resources\GroupeSolidaireComptes\Pages\EditGroupeSolidaireCompte;
use App\Filament\Resources\GroupeSolidaireComptes\Pages\ListGroupeSolidaireComptes;
use App\Filament\Resources\GroupeSolidaireComptes\Schemas\GroupeSolidaireCompteForm;
use App\Filament\Resources\GroupeSolidaireComptes\Tables\GroupeSolidaireComptesTable;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Compte;
use App\Models\GroupeSolidaireCompte;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class GroupeSolidaireCompteResource extends Resource
{
    protected static ?string $model = Compte::class;

    protected static ?string $navigationLabel = 'Comptes Groupes Solidaires';
    protected static string|BackedEnum|null $navigationIcon =  'heroicon-o-user-group';
    protected static string|UnitEnum|null $navigationGroup =  '👨‍💼 GESTION DES MEMBRES';
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type_compte', 'groupe_solidaire');
    }

      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 
    public static function form(Schema $schema): Schema
    {
        return GroupeSolidaireCompteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupeSolidaireComptesTable::configure($table);
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
            'index' => ListGroupeSolidaireComptes::route('/'),
            'create' => CreateGroupeSolidaireCompte::route('/create'),
            'edit' => EditGroupeSolidaireCompte::route('/{record}/edit'),
        ];
    }

     public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_compte');
    }

    
        public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_compte');
    }

    // 🔒 Contrôle des accès aux actions


     public static function canEdit($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_compte');
    }

    public static function canDelete($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_compte');
    }
}
