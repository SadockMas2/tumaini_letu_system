<?php

namespace App\Filament\Resources\Mouvements\Tables;

use App\Models\User;
use App\Models\Mouvement;
use App\Models\HistoriqueMouvementCaisse;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MouvementsTable
{
    public static function configure(Table $table): Table
    {
        // Calcul des totaux pour la journée en cours
        $today = Carbon::today();
        $totals = Mouvement::whereDate('created_at', $today)
            ->select(
                DB::raw('SUM(CASE WHEN type = "depot" THEN montant ELSE 0 END) as total_depots'),
                DB::raw('SUM(CASE WHEN type = "retrait" THEN montant ELSE 0 END) as total_retraits')
            )
            ->first();

        $totalDepots = $totals->total_depots ?? 0;
        $totalRetraits = $totals->total_retraits ?? 0;
        $soldeJournee = $totalDepots - $totalRetraits;

        return $table
            ->columns([
                TextColumn::make('numero_compte')->label('Compte')->sortable(),
                TextColumn::make('client_nom')->label('Client')->sortable(),
                TextColumn::make('nom_deposant')->label('Nom du déposant/retirant')->sortable(),
                TextColumn::make('type')->label('Type')->sortable(),
                TextColumn::make('montant')
                    ->label('Montant')
                    ->sortable()
                    ->money('USD'),
                TextColumn::make('solde_apres')
                    ->label('Solde après')
                    ->sortable()
                    ->money('USD'),
                TextColumn::make('operateur.name')
                    ->label('Opérateur')
                    ->sortable(),
                TextColumn::make('description')->label('Description')->toggleable(),
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
                // Affichage des totaux
                Action::make('totaux_journee')
                    ->label("Totaux journée - Dépots: " . number_format($totalDepots, 2, ',', ' ') . " USD - Retraits: " . number_format($totalRetraits, 2, ',', ' ') . " USD - Solde: " . number_format($soldeJournee, 2, ',', ' ') . " USD")
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
                //
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
                
                // Récupérer tous les mouvements de la journée
                $mouvements = Mouvement::whereDate('created_at', $today)->get();
                
                if ($mouvements->isEmpty()) {
                    throw new \Exception('Aucun mouvement à clôturer pour aujourd\'hui.');
                }
                
                // Calcul des totaux
                $totalDepots = $mouvements->where('type', 'depot')->sum('montant');
                $totalRetraits = $mouvements->where('type', 'retrait')->sum('montant');
                $soldeFinal = $totalDepots - $totalRetraits;
                
                // Créer l'enregistrement de clôture dans l'historique
                HistoriqueMouvementCaisse::create([
                    'date_cloture' => $today,
                    'total_depots' => $totalDepots,
                    'total_retraits' => $totalRetraits,
                    'solde_final' => $soldeFinal,
                    'nombre_operations' => $mouvements->count(),
                    'cloture_par' => Auth::id(),
                ]);
                
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