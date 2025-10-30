<?php

namespace App\Filament\Resources\DispatchEpargnes;

// use App\Filament\Resources\DispatchEpargneResource\Pages\ListDispatchEpargnes;
use App\Filament\Resources\DispatchEpargneResource\Pages\ListDispatchEpargnes;
use App\Filament\Resources\DispatchEpargnes\Pages\CreateDispatchEpargne;
use App\Filament\Resources\DispatchEpargnes\Pages\EditDispatchEpargne;
// use App\Filament\Resources\DispatchEpargnes\Pages\ListDispatchEpargness;
// use App\Filament\Resources\DispatchEpargnes\Schemas\DispatchEpargneForm;
// use App\Filament\Resources\DispatchEpargnes\Tables\DispatchEpargnesTable;
// // use App\Filament\Resources\DispatchEpargnes\Tables\DispatchEpargnesTable;
// use App\Filament\Resources\DispatchEpargnes\Schemas\DispatchEpargneForm;
// use App\Filament\Resources\DispatchEpargnes\Tables\DispatchEpargnesTable;
use App\Filament\Resources\DispatchEpargnes\Schemas\DispatchEpargneForm;
use App\Filament\Resources\DispatchEpargnes\Tables\DispatchEpargnesTable;
use App\Models\DispatchEpargne;
use App\Models\Epargne;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class DispatchEpargneResource extends Resource
{
  protected static ?string $model = Epargne::class;

    protected static string|BackedEnum|null $navigationIcon = "heroicon-o-archive-box-arrow-down";

        protected static ?string $navigationLabel = 'Dispatch Epargnes';

     protected static string|UnitEnum|null $navigationGroup = '💰 EPARGNES';

    public static function form(Schema $schema): Schema
    {
        return DispatchEpargneForm::configure($schema);
    }
     public static function table(Table $table): Table
    {
        return DispatchEpargnesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDispatchEpargnes::route('/'),
        ];
    }

          public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_epargne');
    }

    
        public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('create_epargne');
    }

    // 🔒 Contrôle des accès aux actions


     public static function canEdit($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_epargne');
    }

    public static function canDelete($record = null): bool
    {
          /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_epargne');
    }
}