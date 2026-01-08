<?php

namespace App\Filament\Resources\CompteEpargnes\Tables;

use App\Filament\Exports\CompteEpargneExporter;
use App\Models\CompteEpargne;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CompteEpargnesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Vos colonnes ici (même qu'avant)
                TextColumn::make('numero_compte')
                    ->label('Numéro Compte')
                    ->sortable(),
                    
                TextColumn::make('type_compte')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individuel' => 'success',
                        'groupe_solidaire' => 'primary',
                    }),
                    
                TextColumn::make('nom_complet')
                    ->label('Titulaire')
                    ->sortable(),

                TextColumn::make('groupeSolidaire.nom_groupe')
                    ->label('Groupe')
                    ->visible(fn ($record) => $record && $record->type_compte === 'groupe_solidaire')
                    ->sortable(),
                    
                TextColumn::make('solde')
                    ->label('Solde')
                    ->money(fn ($record) => $record ? $record->devise : 'USD')
                    ->sortable(),
                    
                TextColumn::make('devise')
                    ->label('Devise')
                    ->sortable(),
                    
                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'inactif' => 'warning',
                        'suspendu' => 'danger',
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Ajoutez ce filtre de recherche en PREMIER
                \Filament\Tables\Filters\Filter::make('recherche_rapide')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('search')
                            ->label('Recherche rapide')
                            ->placeholder('Numéro, nom, groupe...')
                            ->live()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['search'] ?? null,
                                fn (Builder $query, $search): Builder => $query->where(function($q) use ($search) {
                                    $q->where('numero_compte', 'LIKE', "%{$search}%")
                                      ->orWhere('type_compte', 'LIKE', "%{$search}%")
                                      ->orWhere('devise', 'LIKE', "%{$search}%")
                                      ->orWhere('statut', 'LIKE', "%{$search}%")
                                      ->orWhereRaw("CAST(solde AS CHAR) LIKE ?", ["%{$search}%"])
                                      
                                      ->orWhereHas('client', function($clientQuery) use ($search) {
                                          $clientQuery->where('nom', 'LIKE', "%{$search}%")
                                                     ->orWhere('postnom', 'LIKE', "%{$search}%")
                                                     ->orWhere('prenom', 'LIKE', "%{$search}%");
                                      })
                                      
                                      ->orWhereHas('groupeSolidaire', function($groupeQuery) use ($search) {
                                          $groupeQuery->where('nom_groupe', 'LIKE', "%{$search}%");
                                      });
                                })
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['search'] ?? null)) {
                            return null;
                        }
                        
                        return 'Recherche: ' . $data['search'];
                    })
                    ->columnSpanFull(),
                    
                SelectFilter::make('type_compte')
                    ->options([
                        'individuel' => 'Individuel',
                        'groupe_solidaire' => 'Groupe Solidaire',
                    ]),
                    
                SelectFilter::make('devise')
                    ->options([
                        'USD' => 'USD',
                        'CDF' => 'CDF',
                    ]),
                    
                SelectFilter::make('statut')
                    ->options([
                        'actif' => 'Actif',
                        'inactif' => 'Inactif',
                        'suspendu' => 'Suspendu',
                    ]),
            ])
            ->headerActions([

                Action::make('rapport_epargne')
    ->label('📊 Rapport Épargne')
    ->color('success')
    ->icon('heroicon-o-document-chart-bar')
    ->url(route('rapport.epargne.filtre'))  // <- Pointez vers le formulaire de filtrage
    ->openUrlInNewTab()
    ->visible(function () {
        /** @var User|null $user */
        $user = Auth::user();
        return $user && $user->can('view_comptespecial'); // Adaptez la permission
    }),
                ExportAction::make()
                    ->exporter(CompteEpargneExporter::class)
                    ->label('Exporter')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                Action::make('voir_details_epargne')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('comptes-epargne.details', ['compte_epargne_id' => $record->id]))
                    ->visible(fn () => Auth::user()?->can('view_client')),
            ]);
    }
}