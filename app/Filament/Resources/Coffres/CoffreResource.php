<?php

namespace App\Filament\Resources\Coffres;

use App\Filament\Resources\CoffreResource\Pages\ManageCoffre;
use App\Filament\Resources\Coffres\Pages\CreateCoffre;
use App\Filament\Resources\Coffres\Pages\EditCoffre;
use App\Filament\Resources\Coffres\Pages\ListCoffres;
use App\Filament\Resources\Coffres\Schemas\CoffreForm;
use App\Filament\Resources\Coffres\Tables\CoffresTable;
use App\Models\CashRegister;
use App\Models\Coffre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CoffreResource extends Resource
{    protected static ?string $model = CashRegister::class;
    protected static string |BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'Gestion Coffre';
    protected static string |UnitEnum|null $navigationGroup = 'Trésorerie';
    protected static ?string $slug = 'coffre';

    public static function form(Schema $schema): Schema
    {
        return CoffreForm::configure($schema);
    }

      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 

    public static function table(Table $table): Table
    {
        return CoffresTable::configure($table);
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
            'index' => ManageCoffre::route('/'),
            'create' => CreateCoffre::route('/create'),
            'edit' => EditCoffre::route('/{record}/edit'),
        ];
    }
        public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_comptespecial');
    }

}
