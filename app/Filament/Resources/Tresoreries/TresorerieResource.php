<?php

namespace App\Filament\Resources\Tresoreries;

use App\Filament\Resources\TresorerieResource\Pages\ManageTresorerie;
use App\Filament\Resources\Tresoreries\Pages\CreateTresorerie;
use App\Filament\Resources\Tresoreries\Pages\EditTresorerie;
use App\Filament\Resources\Tresoreries\Pages\ListTresoreries;
use App\Filament\Resources\Tresoreries\Schemas\TresorerieForm;
use App\Filament\Resources\Tresoreries\Tables\TresoreriesTable;
use App\Models\Caisse;
use App\Models\Tresorerie;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TresorerieResource extends Resource
{
    protected static ?string $model = Caisse::class;
   protected static string |BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Trésorerie';
    protected static string |UnitEnum|null $navigationGroup = 'Trésorerie';
    protected static ?string $slug = 'tresorerie';

      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 
    public static function form(Schema $schema): Schema
    {
        return TresorerieForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TresoreriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTresorerie::route('/'),
            'create' => CreateTresorerie::route('/create'),
            // 'edit' => EditTresorerie::route('/{record}/edit'),
        ];
    }
       public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_compte');
    }
}
