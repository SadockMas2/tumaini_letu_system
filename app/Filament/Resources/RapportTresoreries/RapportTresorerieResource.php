<?php

namespace App\Filament\Resources\RapportTresoreries;

use App\Filament\Resources\RapportTresorerieResource\Pages\ViewRapportTresorerie;
use App\Filament\Resources\RapportTresoreries\Pages\CreateRapportTresorerie;
use App\Filament\Resources\RapportTresoreries\Pages\EditRapportTresorerie;
use App\Filament\Resources\RapportTresoreries\Pages\ListRapportTresoreries;
use App\Filament\Resources\RapportTresoreries\Schemas\RapportTresorerieForm;
use App\Filament\Resources\RapportTresoreries\Tables\RapportTresoreriesTable;
use App\Models\RapportTresorerie;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RapportTresorerieResource extends Resource
{
    protected static ?string $model = RapportTresorerie::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RapportTresorerieForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RapportTresoreriesTable::configure($table);
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
            'index' => ListRapportTresoreries::route('/'),
            'create' => CreateRapportTresorerie::route('/create'),
            'edit' => EditRapportTresorerie::route('/{record}/edit'),
            'view' => ViewRapportTresorerie::route('/{record}'),
        ];
    }
}
