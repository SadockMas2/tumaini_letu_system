<?php

namespace App\Filament\Resources\DispatchEpargnes\Tables;

use App\Models\Client;
use App\Models\Compte;
use App\Models\User;
use App\Models\CompteTransitoire;
use App\Models\Epargne;
use App\Models\GroupeSolidaire;
use App\Models\CompteEpargne;
use App\Models\Mouvement;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;    

class DispatchEpargnesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Epargne::where('statut', 'en_attente_dispatch'))
            ->columns([
                TextColumn::make('client_nom')
                    ->label('Membre/Groupe')
                    ->searchable()
                    ->description(fn ($record) => $record->type_epargne === 'individuel' ? 'Individuel' : 'Groupe'),
                    
                TextColumn::make('numero_compte_membre')
                    ->label('N° Compte')
                    ->searchable(),
                    
                TextColumn::make('montant')
                    ->label('Montant Collecté')
                    ->money(fn ($record) => $record->devise)
                    ->sortable(),
                    
                TextColumn::make('devise')
                    ->label('Devise')
                    ->badge(),
                    
                TextColumn::make('agent_nom')
                    ->label('Agent Collecteur')
                    ->searchable(),
                    
                TextColumn::make('date_apport')
                    ->label('Date Collecte')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('solde_transitoire')
                    ->label('Solde Agent')
                    ->getStateUsing(function ($record) {
                        // CORRECTION : Prendre le solde existant du compte transitoire selon la devise
                        $compteTransitoire = CompteTransitoire::where('user_id', $record->user_id)
                            ->where('devise', $record->devise)
                            ->first();
                        return $compteTransitoire ? number_format($compteTransitoire->solde, 2) . ' ' . $record->devise : '0.00 ' . $record->devise;
                    })
                    ->color(function ($record) {
                        // CORRECTION : Vérifier le solde existant dans la bonne devise
                        $compteTransitoire = CompteTransitoire::where('user_id', $record->user_id)
                            ->where('devise', $record->devise)
                            ->first();
                        $solde = $compteTransitoire ? $compteTransitoire->solde : 0;
                        return $solde >= $record->montant ? 'success' : 'danger';
                    }),
                    
                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'warning' => 'en_attente_dispatch',
                        'success' => 'valide',
                    ]),
            ])
            ->filters([
                SelectFilter::make('type_epargne')
                    ->label('Type d\'Épargne')
                    ->options([
                        'individuel' => 'Individuelle',
                        'groupe_solidaire' => 'Groupe Solidaire',
                    ]),
                    
                Filter::make('date_collecte')
                    ->schema([
                        DatePicker::make('date_collecte')
                            ->label('Date de Collecte')
                            ->default(today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date_collecte'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', $date),
                        );
                    }),
            ])
            ->recordActions([
                Action::make('dispatcher')
                    ->label('Dispatcher')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(function ($record) {
                        // CORRECTION : Vérifier le solde existant dans la bonne devise
                        $compteTransitoire = CompteTransitoire::where('user_id', $record->user_id)
                            ->where('devise', $record->devise)
                            ->first();
                        $solde = $compteTransitoire ? $compteTransitoire->solde : 0;
                        return $solde >= $record->montant;
                    })
                    ->schema(function (Epargne $record) {
                        $isGroupe = $record->type_epargne === 'groupe_solidaire';
                        
                        // CORRECTION : Prendre le solde existant du compte transitoire selon la devise
                        $compteTransitoire = CompteTransitoire::where('user_id', $record->user_id)
                            ->where('devise', $record->devise)
                            ->first();
                        $soldeAgent = $compteTransitoire ? $compteTransitoire->solde : 0;
                        
                        if ($isGroupe) {
                            $groupe = $record->groupeSolidaire;
                            $membres = $groupe ? $groupe->membres : collect();
                            
                            return [
                                Hidden::make('epargne_id')
                                    ->default($record->id),
                                    
                                TextInput::make('solde_agent')
                                    ->label('Solde du Compte Transitoire')
                                    ->default(number_format($soldeAgent, 2) . ' ' . $record->devise)
                                    ->disabled(),
                                    
                                TextInput::make('type_epargne')
                                    ->label('Type')
                                    ->default('Groupe Solidaire')
                                    ->disabled(),
                                    
                                TextInput::make('nom_beneficiaire')
                                    ->label('Groupe')
                                    ->default($groupe->nom_groupe ?? '')
                                    ->disabled(),
                                    
                                TextInput::make('montant_total')
                                    ->label('Montant à Dispatcher')
                                    ->default($record->montant)
                                    ->disabled(),
                                    
                                TextInput::make('devise')
                                    ->label('Devise')
                                    ->default($record->devise)
                                    ->disabled(),
                                    
                                Repeater::make('repartition')
                                    ->schema([
                                        Select::make('membre_id')
                                            ->label('Membre')
                                            ->options($membres->mapWithKeys(function ($membre) {
                                                $nomComplet = trim($membre->nom . ' ' . $membre->postnom . ' ' . $membre->prenom);
                                                return [$membre->id => $nomComplet ?: 'Inconnu'];
                                            })->toArray())
                                            ->required()
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $client = Client::find($state);
                                                    if ($client) {
                                                        $set('membre_nom', trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom));
                                                    }
                                                }
                                            }),
                                            
                                        TextInput::make('membre_nom')
                                            ->label('Nom')
                                            ->disabled(),
                                            
                                        TextInput::make('montant')
                                            ->label('Montant')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                self::calculerTotalAction($get, $set);
                                            }),
                                    ])
                                    ->columns(3)
                                    ->required()
                                    ->minItems(1),
                                    
                                TextInput::make('total_reparti')
                                    ->label('Total Réparti')
                                    ->numeric()
                                    ->disabled(),
                                    
                                TextInput::make('reste_a_repartir')
                                    ->label('Reste à Répartir')
                                    ->numeric()
                                    ->disabled(),
                            ];
                        } else {
                            return [
                                Hidden::make('epargne_id')
                                    ->default($record->id),
                                    
                                TextInput::make('solde_agent')
                                    ->label('Solde du Compte Transitoire')
                                    ->default(number_format($soldeAgent, 2) . ' ' . $record->devise)
                                    ->disabled(),
                                    
                                TextInput::make('type_epargne')
                                    ->label('Type')
                                    ->default('Individuelle')
                                    ->disabled(),
                                    
                                TextInput::make('nom_beneficiaire')
                                    ->label('Client')
                                    ->default($record->client_nom ?? '')
                                    ->disabled(),
                                    
                                TextInput::make('montant_total')
                                    ->label('Montant à Dispatcher')
                                    ->default($record->montant)
                                    ->disabled(),
                                    
                                TextInput::make('devise')
                                    ->label('Devise')
                                    ->default($record->devise)
                                    ->disabled(),
                            ];
                        }
                    })
                    ->action(function (array $data): void {
                        self::processDispatch($data);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Dispatcher l\'Épargne')
                    ->modalDescription(function ($record) {
                        // CORRECTION : Prendre le solde existant du compte transitoire selon la devise
                        $compteTransitoire = CompteTransitoire::where('user_id', $record->user_id)
                            ->where('devise', $record->devise)
                            ->first();
                        $soldeAgent = $compteTransitoire ? $compteTransitoire->solde : 0;
                        
                        return $record->type_epargne === 'individuel' 
                            ? "Solde agent: {$soldeAgent} {$record->devise} - Confirmer le dispatch vers le compte épargne du membre." 
                            : "Solde agent: {$soldeAgent} {$record->devise} - Répartir le montant collecté vers les comptes épargne des bénéficiaires.";
                    })
                    ->modalSubmitActionLabel('Confirmer le Dispatch'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Calculer le total dans l'action (uniquement pour les groupes)
     */
    private static function calculerTotalAction(callable $get, callable $set): void
    {
        $repartitions = $get('../../repartition') ?? [];
        $total = collect($repartitions)->sum('montant');
        $montantTotal = $get('../../montant_total') ?? 0;
        
        $set('../../total_reparti', $total);
        $set('../../reste_a_repartir', $montantTotal - $total);
    }

    /**
     * Traiter le dispatch
     */
    private static function processDispatch(array $data): void
    {
        try {
            DB::transaction(function () use ($data) {
                $epargne = Epargne::findOrFail($data['epargne_id']);
                $montantTotal = $epargne->montant;
                $isGroupe = $epargne->type_epargne === 'groupe_solidaire';
                
                // CORRECTION : Vérification finale du solde existant dans la bonne devise
                $compteTransitoire = CompteTransitoire::where('user_id', $epargne->user_id)
                    ->where('devise', $epargne->devise)
                    ->first();
                    
                if (!$compteTransitoire || $compteTransitoire->solde < $montantTotal) {
                    throw new \Exception("L'agent ne dispose pas de suffisamment de fonds dans son compte transitoire {$epargne->devise}.");
                }
                
                // Pour les individuels, on prépare une répartition automatique
                if (!$isGroupe) {
                    $data['repartition'] = [
                        [
                            'membre_id' => $epargne->client_id,
                            'membre_nom' => $epargne->client_nom,
                            'montant' => $montantTotal
                        ]
                    ];
                }
                
                $totalReparti = collect($data['repartition'])->sum('montant');
                
                // Vérifier que le total réparti correspond au montant total
                if (abs($totalReparti - $montantTotal) > 0.01) {
                    throw new \Exception("Le total réparti ({$totalReparti}) ne correspond pas au montant collecté ({$montantTotal}).");
                }
                
                // CORRECTION : Débiter le compte transitoire existant de l'agent dans la bonne devise
                $compteTransitoire->solde -= $montantTotal;
                $compteTransitoire->save();
                
                // Créditer les comptes épargne des bénéficiaires
                foreach ($data['repartition'] as $repartition) {
                    $membreId = $repartition['membre_id'];
                    $montantMembre = $repartition['montant'];
                    
                    if ($montantMembre <= 0) continue;
                    
                    // Vérifier si le compte épargne existe déjà
                    $compteEpargne = CompteEpargne::where('client_id', $membreId)
                        ->where('devise', $epargne->devise)
                        ->first();
                    
                    if (!$compteEpargne) {
                        // Créer un nouveau compte épargne
                        $compteEpargne = new CompteEpargne();
                        $compteEpargne->client_id = $membreId;
                        $compteEpargne->devise = $epargne->devise;
                        $compteEpargne->solde = 0;
                        $compteEpargne->numero_compte = CompteEpargne::genererNumeroCompte('individuel');
                        $compteEpargne->type_compte = 'individuel';
                        $compteEpargne->statut = 'actif';
                        $compteEpargne->taux_interet = 2.5;
                        $compteEpargne->solde_minimum = 0;
                        $compteEpargne->conditions = 'Compte épargne standard';
                        $compteEpargne->date_ouverture = now();
                        $compteEpargne->user_id = Auth::id();
                    }
                    
                    // Créditer le compte épargne du membre
                    $ancienSolde = $compteEpargne->solde;
                    $compteEpargne->solde += $montantMembre;
                    $compteEpargne->save();
                }
                
                // Marquer l'épargne comme validée
                $epargne->statut = 'valide';
                $epargne->save();
                
                $typeLabel = $isGroupe ? 'du groupe' : 'individuelle';
                $nouveauSolde = $compteTransitoire->solde;
                
                Notification::make()
                    ->title('Dispatch Réussi')
                    ->body("Le montant de {$montantTotal} {$epargne->devise} a été réparti vers les comptes épargne {$typeLabel}. Solde restant agent: {$nouveauSolde} {$epargne->devise}")
                    ->success()
                    ->send();
            });
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de Dispatch')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}