<?php

namespace App\Filament\Resources\Epargnes;


use App\Filament\Resources\Epargnes\Pages\CreateEpargne;
use App\Filament\Resources\Epargnes\Pages\EditEpargne;
use App\Filament\Resources\Epargnes\Pages\ListEpargnes;
use App\Filament\Resources\Epargnes\Schemas\EpargneForm;
use App\Filament\Resources\Epargnes\Tables\EpargnesTable;
use App\Models\Epargne;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class EpargneResource extends Resource
{
    protected static ?string $model = Epargne::class;

    protected static string|BackedEnum|null $navigationIcon = "heroicon-o-archive-box-arrow-down";

        protected static ?string $navigationLabel = 'Toutes les épargnes';

     protected static string|UnitEnum|null $navigationGroup = '💰 EPARGNES';
     
         public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    } 

    public static function form(Schema $schema): Schema
    {
        return EpargneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EpargnesTable::configure($table);
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
            'index' => ListEpargnes::route('/'),
            'create' => CreateEpargne::route('/create'),
            'edit' => EditEpargne::route('/{record}/edit'),
        ];
    }

         public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_cycle');
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
