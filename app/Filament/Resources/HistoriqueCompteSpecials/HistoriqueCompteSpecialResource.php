<?php

namespace App\Filament\Resources\HistoriqueCompteSpecials;

use App\Filament\Resources\HistoriqueCompteSpecials\Pages\CreateHistoriqueCompteSpecial;
use App\Filament\Resources\HistoriqueCompteSpecials\Pages\EditHistoriqueCompteSpecial;
use App\Filament\Resources\HistoriqueCompteSpecials\Pages\ListHistoriqueCompteSpecials;
use App\Filament\Resources\HistoriqueCompteSpecials\Schemas\HistoriqueCompteSpecialForm;
use App\Filament\Resources\HistoriqueCompteSpecials\Tables\HistoriqueCompteSpecialsTable;

use App\Models\HistoriqueCompteSpecial;
use BackedEnum;
use Illuminate\Support\Facades\Auth; // ✅ Correction ici
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HistoriqueCompteSpecialResource extends Resource
{
    protected static ?string $model = HistoriqueCompteSpecial::class;

    protected static ?string $navigationLabel = 'Historique_compte TUMAINI';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = '💳 COMPTE SPECIAL TUMAINI';

      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 
    public static function form(Schema $schema): Schema
    {
        return HistoriqueCompteSpecialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistoriqueCompteSpecialsTable::configure($table);
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
            'index' => ListHistoriqueCompteSpecials::route('/'),
            'create' => CreateHistoriqueCompteSpecial::route('/create'),
            // 'edit' => EditHistoriqueCompteSpecial::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_comptespecial');
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_comptespecial');
    }

    public static function canEdit($record = null): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_comptespecial');
    }

    public static function canDelete($record = null): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_comptespecial');
    }
}