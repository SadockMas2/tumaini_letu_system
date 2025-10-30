<?php

namespace App\Filament\Resources\CompteSpecials;

use App\Filament\Resources\CompteSpecials\Pages\CreateCompteSpecial;
use App\Filament\Resources\CompteSpecials\Pages\EditCompteSpecial;
use App\Filament\Resources\CompteSpecials\Pages\ListCompteSpecials;
use App\Filament\Resources\CompteSpecials\Schemas\CompteSpecialForm;
use App\Filament\Resources\CompteSpecials\Tables\CompteSpecialsTable;
use App\Models\CompteSpecial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CompteSpecialResource extends Resource
{
    protected static ?string $model = CompteSpecial::class;

    protected static ?string $navigationLabel = 'Compte TUMAINI-LETU';
    protected static string|UnitEnum|null $navigationGroup = '💳 COMPTE SPECIAL TUMAINI';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';



    public static function form(Schema $schema): Schema
    {
        return CompteSpecialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompteSpecialsTable::configure($table);
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
            'index' => ListCompteSpecials::route('/'),
            'create' => CreateCompteSpecial::route('/create'),
            'edit' => EditCompteSpecial::route('/{record}/edit'),
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

    // 🔒 Contrôle des accès aux actions


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


