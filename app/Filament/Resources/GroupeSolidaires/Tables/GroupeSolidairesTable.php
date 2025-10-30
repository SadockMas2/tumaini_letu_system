<?php

namespace App\Filament\Resources\GroupeSolidaires\Tables;

use App\Filament\Resources\GroupeSolidaires\Pages\ManageGroupMembers;

use App\Models\GroupeSolidaire;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GroupeSolidairesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_groupe')
                    ->label('Numéro')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('nom_groupe')->label('Nom du groupe'),
                TextColumn::make('numero_cycle')
                    ->label('Cycle')
                    ->sortable(),

                TextColumn::make('adresse')->label('Adresse'),
                TextColumn::make('membres_count')
                    ->counts('membres')
                    ->label('Nombre de membres'),               
                TextColumn::make('date_debut_cycle')->label('Début'),
                TextColumn::make('date_fin_cycle')->label('Fin'),
                TextColumn::make('created_at')->label('Créé le')->date(),
            ])
        
            ->filters([
                //
            ])

            ->headerActions([
                Action::make('create_groupesolidaire')
                    ->label('Creer un groupe')
                    ->icon('heroicon-o-user-plus')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_groupesolidaire');
                    })
                    ->url(route('filament.admin.resources.groupe-solidaires.create')),
            ])
           
            ->recordActions([
                EditAction::make(),
                Action::make('manage_members')
                    ->label('Voir les membres')
                    ->icon('heroicon-o-users')
                    ->url(fn (GroupeSolidaire $record): string => ManageGroupMembers::getUrl(['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}