<?php

namespace App\Filament\Resources\CoffreForts;

use App\Filament\Resources\CoffreForts\Pages\CreateCoffreFort;
use App\Filament\Resources\CoffreForts\Pages\EditCoffreFort;
use App\Filament\Resources\CoffreForts\Pages\ListCoffreForts;
use App\Filament\Resources\CoffreForts\Schemas\CoffreFortForm;
use App\Filament\Resources\CoffreForts\Tables\CoffreFortsTable;
use App\Models\CoffreFort;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

// class CoffreFortResource extends Resource
// {
//     protected static ?string $model = CoffreFort::class;

//         protected static ?string $navigationLabel = 'Gestion Trésorerie';
//     protected static string|UnitEnum|null $navigationGroup = 'Trésorerie';
//     protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
//      protected static ?string $slug = 'gestion-tresorerie';


//     public static function form(Schema $schema): Schema
//     {
//         return CoffreFortForm::configure($schema);
//     }

//     public static function table(Table $table): Table
//     {
//         return CoffreFortsTable::configure($table);
//     }

//     public static function getRelations(): array
//     {
//         return [
//             //  'index' => Pages\ManageGestionTresorerie::route('/'),
//         ];
//     }

    

//     public static function getPages(): array
//     {
//         return [
//             'index' => ListCoffreForts::route('/'),
//             'create' => CreateCoffreFort::route('/create'),
//             'edit' => EditCoffreFort::route('/{record}/edit'),
//         ];
//     }

//                 public static function canViewAny(): bool
//     {
//         /** @var \App\Models\User|null $user */
//         $user = Auth::user();
//         return $user && $user->can('view_compte');
//     }
    

    
// }
