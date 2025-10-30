<?php

namespace App\Filament\Resources\CoffreForts\Tables;

use App\Filament\Resources\RapportCoffres\RapportCoffreResource;
use App\Models\CoffreFort;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// class CoffreFortsTable
// {
//     public static function configure(Table $table): Table
//     {
//         return $table
//             ->columns([
//                 TextColumn::make('nom_coffre')
//                     ->searchable(),
//                 TextColumn::make('devise'),
//                 TextColumn::make('solde_actuel')
//                     ->money('USD')
//                     ->sortable(),
//                 TextColumn::make('responsable.name')
//                     ->label('Responsable'),
//                 TextColumn::make('agence'),
//                 IconColumn::make('est_actif')
//                     ->boolean(),
//             ])
//             ->filters([
//                 //
//             ])
//         ->recordActions([
//                 Action::make('alimenter')
//                     ->icon('heroicon-o-plus-circle')
//                     ->color('success')
//                     ->schema([
//                         TextInput::make('montant')
//                             ->numeric()
//                             ->required()
//                             ->prefix('$'),
//                         TextInput::make('provenance')
//                             ->label('Provenance')
//                             ->required(),
//                         TextInput::make('motif')
//                             ->required(),
//                         TextInput::make('reference')
//                             ->required(),
//                         Textarea::make('observations')
//                             ->rows(3),
//                     ])
//                     ->action(function (array $data, CoffreFort $record): void {
//                         $record->alimenter(
//                             $data['montant'],
//                             $data['provenance'],
//                             $data['motif'],
//                             $data['reference'],
//                             $data['observations']
//                         );
//                     }),
//                 Action::make('retirer')
//                     ->icon('heroicon-o-minus-circle')
//                     ->color('danger')
//                     ->schema([
//                         TextInput::make('montant')
//                             ->numeric()
//                             ->required()
//                             ->prefix('$'),
//                         TextInput::make('destination')
//                             ->label('Destination')
//                             ->required(),
//                         TextInput::make('motif')
//                             ->required(),
//                         TextInput::make('reference')
//                             ->required(),
//                         Textarea::make('observations')
//                             ->rows(3),
//                     ])
//                     ->action(function (array $data, CoffreFort $record): void {
//                         $record->retirer(
//                             $data['montant'],
//                             $data['destination'],
//                             $data['motif'],
//                             $data['reference'],
//                             $data['observations']
//                         );
//                     }),
//                 Action::make('rapport')
//                     ->icon('heroicon-o-document-text')
//                     ->color('info')
//                     ->url(fn (CoffreFort $record) => RapportCoffreResource::getUrl('index', ['coffre_fort_id' => $record->id])),
//                 EditAction::make(),
//             ])
//             ->toolbarActions([
//                 BulkActionGroup::make([
//                     DeleteBulkAction::make(),
//                 ]),
            
//             ]);
//     }
// }
