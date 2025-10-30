<?php

namespace App\Filament\Resources\Credits\Credits;

use App\Filament\Resources\Credits\Credits\Pages\CreateCredit;
use App\Filament\Resources\Credits\Credits\Pages\EditCredit;
use App\Filament\Resources\Credits\Credits\Pages\ListCredits;
use App\Filament\Resources\Credits\Credits\Schemas\CreditForm;
use App\Filament\Resources\Credits\Credits\Tables\CreditsTable;
use App\Models\Credit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CreditResource extends Resource
{
    protected static ?string $model = Credit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CreditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditsTable::configure($table);
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
            'index' => ListCredits::route('/'),
            'create' => CreateCredit::route('/create'),
            'edit' => EditCredit::route('/{record}/edit'),
        ];
    }
}
