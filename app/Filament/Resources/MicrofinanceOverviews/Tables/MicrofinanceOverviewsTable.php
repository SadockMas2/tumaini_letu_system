<?php

namespace App\Filament\Resources\MicrofinanceOverviews\Tables;

use App\Models\Compte;
use App\Models\CompteEpargne;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MicrofinanceOverviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
          TextColumn::make('numero_compte')
                    ->label('Numéro Compte')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type_compte')
                    ->label('Type Compte')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individuel' => 'success',
                        'groupe_solidaire' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('nom_complet')
                    ->label('Client/Groupe')
                    ->getStateUsing(function (Compte $record) {
                        if ($record->type_compte === 'individuel') {
                            return $record->nom . ' ' . $record->prenom;
                        }
                        return $record->nom . ' (Groupe)';
                    })
                    ->searchable(),

                TextColumn::make('solde')
                    ->label('Solde Compte')
                    ->money('USD')
                    ->color(fn ($record) => $record->solde < 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('credits_count')
                    ->label('Crédits Actifs')
                    ->getStateUsing(fn ($record) => $record->credits()->where('statut_demande', 'approuve')->count())
                    ->alignCenter(),

                TextColumn::make('total_credits_encours')
                    ->label('Total Crédits En Cours')
                    ->getStateUsing(function ($record) {
                        return $record->credits()
                            ->where('statut_demande', 'approuve')
                            ->sum('montant_total');
                    })
                    ->money('USD')
                    ->color('warning'),

                TextColumn::make('solde_epargne')
                    ->label('Épargne')
                    ->getStateUsing(function ($record) {
                        $compteEpargne = CompteEpargne::where('client_id', $record->client_id)
                            ->orWhere('groupe_solidaire_id', $record->groupe_solidaire_id)
                            ->first();
                        return $compteEpargne ? $compteEpargne->solde : 0;
                    })
                    ->money('USD')
                    ->color('success'),

                TextColumn::make('statut')
                    ->label('Statut')
                    ->colors([
                        'success' => 'actif',
                        'danger' => 'inactif',
                        'warning' => 'suspendu',
                    ]),
            ])
            ->filters([
                SelectFilter::make('type_compte')
                    ->options([
                        'individuel' => 'Individuel',
                        'groupe_solidaire' => 'Groupe Solidaire',
                    ])
                    ->label('Type de Compte'),

                SelectFilter::make('statut')
                    ->options([
                        'actif' => 'Actif',
                        'inactif' => 'Inactif',
                        'suspendu' => 'Suspendu',
                    ])
                    ->label('Statut Compte'),

                TernaryFilter::make('created_at')
                    ->label('Date Création'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
