<?php

namespace App\Filament\Resources\Mouvements;

use App\Filament\Resources\Mouvements\Pages\CreateMouvement;
use App\Filament\Resources\Mouvements\Pages\EditMouvement;
use App\Filament\Resources\Mouvements\Pages\ListMouvements;
use App\Filament\Resources\Mouvements\Schemas\MouvementForm;
use App\Filament\Resources\Mouvements\Tables\MouvementsTable;
use App\Models\Mouvement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MouvementResource extends Resource
{
    protected static ?string $model = Mouvement::class;

    protected static ?string $navigationLabel = 'Mouvements-Comptes';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = '👨‍💼 GESTION DES MEMBRES';
   


       public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 
     public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_mouvement');
    }

    
        public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_mouvement');
    }

    // 🔒 Contrôle des accès aux actions


     public static function canEdit($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_mouvement');
    }

    public static function canDelete($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_mouvement');
    }
    public static function form(Schema $schema): Schema
    {
        return MouvementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MouvementsTable::configure($table);
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
            'index' => ListMouvements::route('/'),
            'create' => CreateMouvement::route('/create'),
            // 'edit' => EditMouvement::route('/{record}/edit'),
        ];
    }
}
