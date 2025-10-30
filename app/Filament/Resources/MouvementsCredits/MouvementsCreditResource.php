<?php

namespace App\Filament\Resources\MouvementsCredits;

use App\Filament\Resources\MouvementsCredits\Pages\CreateMouvementsCredit;
use App\Filament\Resources\MouvementsCredits\Pages\EditMouvementsCredit;
use App\Filament\Resources\MouvementsCredits\Pages\ListMouvementsCredits;
use App\Filament\Resources\MouvementsCredits\Schemas\MouvementsCreditForm;
use App\Filament\Resources\MouvementsCredits\Tables\MouvementsCreditsTable;
use App\Models\MouvementsCredit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MouvementsCreditResource extends Resource
{
    protected static ?string $model = \App\Models\MouvementCredit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    public static function form(Schema $schema): Schema
    {
        return MouvementsCreditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MouvementsCreditsTable::configure($table);
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
            'index' => ListMouvementsCredits::route('/'),
            'create' => CreateMouvementsCredit::route('/create'),
            'edit' => EditMouvementsCredit::route('/{record}/edit'),
        ];
    }
}
