<?php

namespace App\Filament\Resources\GestionTresoreries;

use App\Filament\Resources\GestionTresorerieResource\Pages\ManageGestionTresorerie;
use App\Filament\Resources\GestionTresoreries\Pages\CreateGestionTresorerie;
use App\Filament\Resources\GestionTresoreries\Pages\EditGestionTresorerie;
use App\Filament\Resources\GestionTresoreries\Pages\ListGestionTresoreries;
use App\Models\CashRegister;
use App\Filament\Resources\GestionTresoreries\Schemas\GestionTresorerieForm;
use App\Filament\Resources\GestionTresoreries\Tables\GestionTresoreriesTable;
use App\Models\GestionTresorerie;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class GestionTresorerieResource extends Resource
{
    protected static ?string $model = CashRegister::class;
    protected static string |BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Gestion Trésorerie';
    protected static string |UnitEnum| null $navigationGroup = 'Trésorerie';
    protected static ?string $slug = 'gestion-tresorerie';

    public static function form(Schema $schema): Schema
    {
        return GestionTresorerieForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GestionTresoreriesTable::configure($table);
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
            'index' => ManageGestionTresorerie::route('/'),
           
            'create' => CreateGestionTresorerie::route('/create'),
            'edit' => EditGestionTresorerie::route('/{record}/edit'),
            
         
        ];
    }
       public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_compte');
    }
}
