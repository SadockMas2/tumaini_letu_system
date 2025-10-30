<?php

namespace App\Filament\Resources\GestionComptables;

use App\Filament\Resources\GestionComptables\Pages\CreateGestionComptable;
use App\Filament\Resources\GestionComptables\Pages\EditGestionComptable;
use App\Filament\Resources\GestionComptables\Pages\ListGestionComptables;
use App\Filament\Resources\GestionComptables\Schemas\GestionComptableForm;
use App\Filament\Resources\GestionComptables\Tables\GestionComptablesTable;
use App\Filament\Resources\GestionComptableResource\Pages\ManageGestionComptable;
use App\Models\EcritureComptable;
use App\Models\GestionComptable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class GestionComptableResource extends Resource
{
    protected static ?string $model = EcritureComptable::class;

     protected static ?string $navigationLabel = 'Comptabilité';
    protected static string|UnitEnum|null $navigationGroup = 'COMPTABILITE';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
     protected static ?string $slug = 'gestion-comptable';


    public static function form(Schema $schema): Schema
    {
        return GestionComptableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GestionComptablesTable::configure($table);
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
             'index' => ManageGestionComptable::route('/'),
            'create' => CreateGestionComptable::route('/create'),
            'edit' => EditGestionComptable::route('/{record}/edit'),
        ];
    }

    
                public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_compte');
    }
}
