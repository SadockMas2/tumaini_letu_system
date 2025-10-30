<?php

namespace App\Filament\Resources\Mouvements\Schemas;

use App\Models\Compte;

use App\Models\Mouvement;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class MouvementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations du mouvement')
                    ->schema([
                        Select::make('compte_id')
                            ->label('Compte')
                            ->options(Compte::all()->pluck('numero_compte', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $compte = Compte::find($state);
                                    if ($compte) {
                                        // Nom du client
                                        $set('client_nom', $compte->type_compte === 'groupe_solidaire' 
                                            ? $compte->nom . ' (Groupe)'
                                            : trim($compte->nom . ' ' . ($compte->postnom ?? '') . ' ' . ($compte->prenom ?? ''))
                                        );
                                        
                                        // Mettre à jour l'affichage des soldes
                                        self::mettreAJourAffichageSoldes($set, $state);
                                    }
                                }
                            }),

                        // Affichage des soldes
                        Grid::make(3)
                            ->schema([
                                TextInput::make('solde_total_display')
                                    ->label('Solde Total')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default('0.00 USD')
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                
                                TextInput::make('solde_disponible_display')
                                    ->label('Solde Disponible')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default('0.00 USD')
                                    ->extraAttributes([
                                        'class' => 'bg-green-50 text-green-700 font-bold border-green-200'
                                    ]),
                                
                                TextInput::make('caution_bloquee_display')
                                    ->label('Caution Bloquée')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default('0.00 USD')
                                    ->extraAttributes([
                                        'class' => 'bg-orange-50 text-orange-700 border-orange-200'
                                    ]),
                            ]),

                        TextInput::make('client_nom')
                            ->label('Nom du client')
                            ->disabled()
                            ->dehydrated(),

                        Select::make('type')
                            ->label('Type de mouvement')
                            ->options([
                                'depot' => 'Dépôt',
                                'retrait' => 'Retrait',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state, $get) {
                                if ($state === 'retrait') {
                                    $set('nom_deposant', 'Retrait');
                                }
                                
                                // Mettre à jour l'affichage des soldes
                                if ($get('compte_id')) {
                                    self::mettreAJourAffichageSoldes($set, $get('compte_id'));
                                }
                            }),

                        TextInput::make('nom_deposant')
                            ->label('Nom du déposant/retirant')
                            ->required()
                            ->placeholder(fn ($get) => $get('type') === 'depot' ? 'Nom du déposant' : 'Nom du retirant'),

                        TextInput::make('montant')
                            ->label('Montant (USD)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->rules([
                                fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                    if ($get('type') === 'retrait' && $get('compte_id')) {
                                        $compte = Compte::find($get('compte_id'));
                                        if ($compte) {
                                            $soldeDisponible = (float) Mouvement::getSoldeDisponible($compte->id);
                                            $montant = (float) $value;
                                            
                                            // Validation stricte côté client
                                            if ($montant > $soldeDisponible) {
                                                $fail("❌ RETRAIT IMPOSSIBLE\n\nMontant maximum autorisé: " . number_format($soldeDisponible, 2) . " USD\nCaution bloquée: " . number_format(Mouvement::getCautionBloquee($compte->id), 2) . " USD");
                                            }
                                            
                                            // Empêcher le retrait total
                                            $soldeApres = (float) $compte->solde - $montant;
                                            if ($soldeApres <= 1) {
                                                $maxAutorise = (float) $compte->solde - 1;
                                                $fail("❌ RETRAIT IMPOSSIBLE\n\nVous devez maintenir un solde minimum.\nMontant maximum: " . number_format($maxAutorise, 2) . " USD");
                                            }
                                        }
                                    }
                                }
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state, $get) {
                                // Ajuster automatiquement le montant si nécessaire
                                if ($get('compte_id') && $get('type') === 'retrait') {
                                    $compte = Compte::find($get('compte_id'));
                                    if ($compte) {
                                        $soldeDisponible = (float) Mouvement::getSoldeDisponible($compte->id);
                                        $montant = (float) $state;
                                        $maxAutorise = min($soldeDisponible, (float) $compte->solde - 1);
                                        
                                        if ($montant > $maxAutorise) {
                                            $set('montant', $maxAutorise);
                                        }
                                    }
                                }
                            }),

                        TextInput::make('description')
                            ->label('Description')
                            ->nullable()
                            ->maxLength(255),

                        // Champs cachés pour la logique interne
                        Hidden::make('solde_total'),
                        Hidden::make('solde_disponible'),
                        Hidden::make('caution_bloquee'),
                        Hidden::make('operateur_id')
                            ->default(fn () => Auth::id()),
                    ]),
            ]);
    }

    /**
     * Méthode pour mettre à jour l'affichage des soldes
     */
    private static function mettreAJourAffichageSoldes($set, $compteId)
    {
        $compte = Compte::find($compteId);
        if ($compte) {
            $soldeTotal = (float) $compte->solde;
            $soldeDisponible = (float) Mouvement::getSoldeDisponible($compteId);
            $cautionBloquee = (float) Mouvement::getCautionBloquee($compteId);
            
            $set('solde_total_display', number_format($soldeTotal, 2) . ' USD');
            $set('solde_disponible_display', number_format($soldeDisponible, 2) . ' USD');
            $set('caution_bloquee_display', number_format($cautionBloquee, 2) . ' USD');
            
            // Stocker les valeurs pour la validation
            $set('solde_total', $soldeTotal);
            $set('solde_disponible', $soldeDisponible);
            $set('caution_bloquee', $cautionBloquee);
        }
    }
}