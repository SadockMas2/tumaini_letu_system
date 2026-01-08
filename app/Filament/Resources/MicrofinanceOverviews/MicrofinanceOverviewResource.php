<?php

namespace App\Filament\Resources\MicrofinanceOverviews;

use App\Filament\Resources\MicrofinanceOverviewResource\Pages\ManageMicrofinanceOverview;
use App\Filament\Resources\MicrofinanceOverviewResource\Pages\RapportsMicrofinance;
use App\Filament\Resources\MicrofinanceOverviews\Pages\CreateMicrofinanceOverview;
use App\Filament\Resources\MicrofinanceOverviews\Pages\EditMicrofinanceOverview;
use App\Filament\Resources\MicrofinanceOverviews\Pages\ListMicrofinanceOverviews;
use App\Filament\Resources\MicrofinanceOverviews\Schemas\MicrofinanceOverviewForm;
use App\Filament\Resources\MicrofinanceOverviews\Tables\MicrofinanceOverviewsTable;
use App\Models\Compte;
use App\Models\MicrofinanceOverview;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MicrofinanceOverviewResource extends Resource
{
    protected static ?string $model = Compte::class;

    protected static string |UnitEnum| null $navigationGroup = 'Analytique';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Vue Microfinance';



    public static function form(Schema $schema): Schema
    {
        return MicrofinanceOverviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MicrofinanceOverviewsTable::configure($table);
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
            'index' => ManageMicrofinanceOverview::route('/'),
             'rapports' =>RapportsMicrofinance::route('/rapports'),
            // 'analytique' => AnalytiqueMicrofinance::route('/analytique'),


        ];
    }
      public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_autorisation');
    }
}
