<?php

namespace App\Filament\Resources\SmsCampaigns;

use App\Filament\Resources\SmsCampaigns\Pages\CreateSmsCampaign;
use App\Filament\Resources\SmsCampaigns\Pages\EditSmsCampaign;
use App\Filament\Resources\SmsCampaigns\Pages\ListSmsCampaigns;
use App\Filament\Resources\SmsCampaigns\Schemas\SmsCampaignForm;
use App\Filament\Resources\SmsCampaigns\Tables\SmsCampaignsTable;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SmsCampaignResource extends Resource
{
    protected static ?string $model = SmsLog::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    protected static string|UnitEnum|null $navigationGroup = 'Communications';
    protected static ?string $navigationLabel = 'Campagnes SMS';
    protected static ?int $navigationSort = 2;
    
    public static function canCreate(): bool { return true; }

         public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_retrait');
    }
    public static function form(Schema $schema): Schema
    {
        return SmsCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsCampaignsTable::configure($table);
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
            'index' => ListSmsCampaigns::route('/'),
            'create' => CreateSmsCampaign::route('/create'),
            //    'view' => Pages\ViewSmsCampaign::route('/{record}'),
        ];
  
    }

    
}
