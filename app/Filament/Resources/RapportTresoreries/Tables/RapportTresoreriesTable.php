<?php

namespace App\Filament\Resources\RapportTresoreries\Tables;

use App\Filament\Resources\RapportTresoreries\RapportTresorerieResource;
use App\Models\RapportTresorerie;
use App\Services\TresorerieService;
use Barryvdh\DomPDF\PDF;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RapportTresoreriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_rapport')
                    ->label('N° Rapport')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_rapport')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_depots')
                    ->label('Total Dépôts')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total_retraits')
                    ->label('Total Retraits')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('solde_total_caisses')
                    ->label('Solde Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('nombre_operations')
                    ->label('Opérations')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('statut')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'brouillon',
                        'success' => 'finalise',
                        'primary' => 'valide',
                        'info' => 'transfere_comptabilite',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'brouillon' => 'Brouillon',
                        'finalise' => 'Finalisé',
                        'valide' => 'Validé',
                        'transfere_comptabilite' => 'Transféré Compta',
                    }),

                TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'finalise' => 'Finalisé',
                        'valide' => 'Validé',
                        'transfere_comptabilite' => 'Transféré Comptabilité',
                    ]),

                Filter::make('date_rapport')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Du'),
                        DatePicker::make('date_until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_rapport', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_rapport', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('voir_details')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (RapportTresorerie $record): string => RapportTresorerieResource::getUrl('view', ['record' => $record])),

                Action::make('generer_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (RapportTresorerie $record) {
                        try {
                            $tresorerieService = app(TresorerieService::class);
                            $rapport = $tresorerieService->genererRapportDetaillePDF($record->date_rapport);
                            
                            // Retourner le PDF pour téléchargement
                            return response()->streamDownload(function () use ($rapport) {
                                echo PDF::loadView('pdf.rapport-tresorerie', compact('rapport'))
                                    ->setPaper('A4', 'portrait')
                                    ->output();
                            }, 'rapport-tresorerie-' . $record->date_rapport->format('Y-m-d') . '.pdf');
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body('Impossible de générer le PDF: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('valider')
                    ->label('Valider')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->action(function (RapportTresorerie $record) {
                        $record->update(['statut' => 'valide']);
                        
                        Notification::make()
                            ->title('Rapport validé')
                            ->body('Le rapport a été marqué comme validé')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (RapportTresorerie $record): bool => $record->statut === 'finalise'),

                EditAction::make(),
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
