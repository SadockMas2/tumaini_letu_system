<?php

namespace App\Filament\Resources\Mouvements\Tables;

use App\Models\User;
use App\Models\Mouvement;
use App\Models\HistoriqueMouvementCaisse;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MouvementsTable
{
    public static function configure(Table $table): Table
    {
        // Calcul des totaux pour la journée en cours groupés par devise
        $today = Carbon::today();
        $totalsByCurrency = Mouvement::whereDate('created_at', $today)
            ->select(
                'devise',
                DB::raw('SUM(CASE WHEN type = "depot" THEN montant ELSE 0 END) as total_depots'),
                DB::raw('SUM(CASE WHEN type = "retrait" THEN montant ELSE 0 END) as total_retraits')
            )
            ->groupBy('devise')
            ->get();

        // Préparer l'affichage des totaux par devise
        $totalsDisplay = [];
        foreach ($totalsByCurrency as $total) {
            $soldeJournee = $total->total_depots - $total->total_retraits;
            $totalsDisplay[] = "{$total->devise}: Dépots " . number_format($total->total_depots, 2, ',', ' ') . 
                             " - Retraits " . number_format($total->total_retraits, 2, ',', ' ') . 
                             " - Solde " . number_format($soldeJournee, 2, ',', ' ');
        }

        $totalsLabel = count($totalsDisplay) > 0 ? implode(' | ', $totalsDisplay) : 'Aucun mouvement aujourd\'hui';

        return $table
        ->defaultSort('created_at', 'desc')
            ->columns([
                 TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero_compte')->label('Compte')->sortable(),
                TextColumn::make('client_nom')->label('Client')->sortable(),
                TextColumn::make('nom_deposant')->label('Nom du déposant/retirant')->sortable(),
                TextColumn::make('type')->label('Type')->sortable(),
                TextColumn::make('montant')
                    ->label('Montant')
                    ->sortable()
                    ->formatStateUsing(function ($state, Mouvement $record) {
                        return number_format($state, 2, ',', ' ') . ' ' . $record->devise;
                    }),
                TextColumn::make('solde_apres')
                    ->label('Solde après')
                    ->sortable()
                    ->formatStateUsing(function ($state, Mouvement $record) {
                        return number_format($state, 2, ',', ' ') . ' ' . $record->devise;
                    }),
                TextColumn::make('operateur.name')
                    ->label('Opérateur')
                    ->sortable(),
                TextColumn::make('description')->label('Description')->toggleable(),
                TextColumn::make('devise')
                    ->label('Devise')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Affichage des totaux par devise
                Action::make('totaux_journee')
                    ->label($totalsLabel)
                    ->disabled()
                    ->color('info')
                    ->extraAttributes(['class' => 'cursor-default']),
                
                Action::make('create_compte')
                    ->label('Depot / Retrait')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('create_compte');
                    })
                    ->url(route('filament.admin.resources.mouvements.create')),
                
                // Action pour générer le rapport journalier
                Action::make('rapport_journalier')
                    ->label('Rapport Journalier')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->url(fn () => route('mouvement.rapport-journalier', ['date' => $today->format('Y-m-d')]))
                    ->openUrlInNewTab()
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('cloturer_caisse');
                    }),
                
                // Action pour clôturer la journée
                Action::make('cloturer_journee')
                    ->label('Clôturer Journée')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->action(function () {
                        return self::cloturerJournee();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer la journée')
                    ->modalDescription('Êtes-vous sûr de vouloir clôturer la journée ? Cette action est irréversible et transférera tous les mouvements vers l\'historique.')
                    ->visible(function () {
                        /** @var User|null $user */
                        $user = Auth::user();
                        return $user && $user->can('cloturer_caisse');
                    }),
            ])
            ->filters([
                   Filter::make('created_at')

            ->schema([
                        DatePicker::make('created_from')
                            ->label('Du'),
                        DatePicker::make('created_until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                // Action pour imprimer le bordereau
                Action::make('imprimer')
                    ->label('Imprimer Bordereau')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (Mouvement $record) => route('mouvement.bordereau', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Méthode pour clôturer la journée
     */
    private static function cloturerJournee()
    {
        try {
            DB::transaction(function () {
                $today = Carbon::today();
                
                // Récupérer tous les mouvements de la journée groupés par devise
                $mouvements = Mouvement::whereDate('created_at', $today)->get();
                
                if ($mouvements->isEmpty()) {
                    throw new \Exception('Aucun mouvement à clôturer pour aujourd\'hui.');
                }
                
                // Calcul des totaux par devise
                $totalsByCurrency = [];
                foreach ($mouvements as $mouvement) {
                    $devise = $mouvement->devise;
                    if (!isset($totalsByCurrency[$devise])) {
                        $totalsByCurrency[$devise] = [
                            'total_depots' => 0,
                            'total_retraits' => 0,
                            'nombre_operations' => 0
                        ];
                    }
                    
                    if ($mouvement->type === 'depot') {
                        $totalsByCurrency[$devise]['total_depots'] += $mouvement->montant;
                    } else {
                        $totalsByCurrency[$devise]['total_retraits'] += $mouvement->montant;
                    }
                    $totalsByCurrency[$devise]['nombre_operations']++;
                }
                
                // Créer les enregistrements de clôture par devise dans l'historique
                foreach ($totalsByCurrency as $devise => $totals) {
                    $soldeFinal = $totals['total_depots'] - $totals['total_retraits'];
                    
                    HistoriqueMouvementCaisse::create([
                        'date_cloture' => $today,
                        'total_depots' => $totals['total_depots'],
                        'total_retraits' => $totals['total_retraits'],
                        'solde_final' => $soldeFinal,
                        'nombre_operations' => $totals['nombre_operations'],
                        'devise' => $devise,
                        'cloture_par' => Auth::id(),
                    ]);
                }
                
                // Marquer les mouvements comme clôturés (au lieu de les supprimer)
                Mouvement::whereDate('created_at', $today)
                    ->update(['est_cloture' => true]);
            });
            
            return redirect()->back()->with('success', 'Journée clôturée avec succès.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la clôture : ' . $e->getMessage());
        }
    }
}