<?php

namespace App\Filament\Resources\Comptabilites;

use App\Filament\Resources\ComptabiliteResource\Pages\ManageComptabilite;
use App\Filament\Resources\Comptabilites\Pages\CreateComptabilite;
use App\Filament\Resources\Comptabilites\Pages\EditComptabilite;
use App\Filament\Resources\Comptabilites\Pages\ListComptabilites;
use App\Filament\Resources\Comptabilites\Schemas\ComptabiliteForm;
use App\Filament\Resources\Comptabilites\Tables\ComptabilitesTable;
use App\Models\Comptabilite;
use App\Models\EcritureComptable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ComptabiliteResource extends Resource
{
protected static ?string $model = EcritureComptable::class;
    protected static string |BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Comptabilité';
    protected static string |UnitEnum|null $navigationGroup = 'Comptabilité';
    protected static ?string $slug = 'comptabilite';


    public static function form(Schema $schema): Schema
    {
        return ComptabiliteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComptabilitesTable::configure($table);
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
             'index' => ManageComptabilite::route('/'),
            'create' => CreateComptabilite::route('/create'),
            'edit' => EditComptabilite::route('/{record}/edit'),
        ];
    }
}
