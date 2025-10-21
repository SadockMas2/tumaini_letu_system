<?php

namespace App\Filament\Resources\Cycles;

use App\Filament\Resources\Cycles\Pages\CreateCycle;
use App\Filament\Resources\Cycles\Pages\EditCycle;
use App\Filament\Resources\Cycles\Pages\ListCycles;
use App\Filament\Resources\Cycles\Schemas\CycleForm;
use App\Filament\Resources\Cycles\Tables\CyclesTable;
use App\Models\Cycle;
use App\Services\CycleService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CycleResource extends Resource
{
    protected static ?string $model = Cycle::class;

    protected static string|BackedEnum|null $navigationIcon = "heroicon-o-archive-box-arrow-down";
    protected static ?string $navigationLabel = 'Cycles';
    protected static string|UnitEnum|null $navigationGroup = '💰 EPARGNES';

      public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
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
        return $user && $user->can('create_cycle');
    }

    public static function canEdit($record = null): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('edit_cycle');
    }

    public static function canDelete($record = null): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('delete_cycle');
    }

    public static function form(Schema $schema): Schema
    {
        return CycleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CyclesTable::configure($table);
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
            'index' => ListCycles::route('/'),
            'create' => CreateCycle::route('/create'),
            'edit' => EditCycle::route('/{record}/edit'),
        ];
    }

    // CORRECTION : Utiliser la méthode create avec callback
    // public static function create($data): Model
    // {
    //     try {
    //         $cycleService = app(CycleService::class);
    //         $cycle = $cycleService->creerCycle($data);
            
    //         Notification::make()
    //             ->title('Cycle créé avec succès')
    //             ->body("Le cycle a été ouvert et {$cycle->solde_initial} {$cycle->devise} ont été débités du compte transitoire de l'agent.")
    //             ->success()
    //             ->send();
                
    //         return $cycle;
            
    //     } catch (\Exception $e) {
    //         Notification::make()
    //             ->title('Erreur')
    //             ->body($e->getMessage())
    //             ->danger()
    //             ->send();
                
    //         throw $e;
    //     }
    // }
}