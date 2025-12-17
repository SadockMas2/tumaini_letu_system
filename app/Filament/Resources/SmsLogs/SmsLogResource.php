<?php

namespace App\Filament\Resources\SmsLogs;

use App\Filament\Resources\SmsLogs\Pages\CreateSmsLog;
use App\Filament\Resources\SmsLogs\Pages\EditSmsLog;
use App\Filament\Resources\SmsLogs\Pages\ListSmsLogs;
use App\Filament\Resources\SmsLogs\Schemas\SmsLogForm;
use App\Filament\Resources\SmsLogs\Tables\SmsLogsTable;
use App\Models\SmsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SmsLogResource extends Resource
{
    protected static ?string $model = SmsLog::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-chat-bubble-left-right';

    protected static string |UnitEnum|null $navigationGroup = 'Communications';
    protected static ?string $navigationLabel = 'Journaux SMS';
    protected static ?int $navigationSort = 1;
    public static function form(Schema $schema): Schema
    {
        return SmsLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsLogsTable::configure($table);
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
           'index' => Pages\ListSmsLogs::route('/'),
            'create' => Pages\CreateSmsLog::route('/create'),
            'edit' => Pages\EditSmsLog::route('/{record}/edit'),
            // 'view' => Pages\ViewSmsLog::route('/{record}'),
        ];
    }

      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

        public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_retrait');
    }
    protected static bool $shouldRegisterNavigation = true;
    // public static function canCreate(): bool
    // {
    //     return false; // Les SMS sont créés automatiquement, pas manuellement
    // }
}
