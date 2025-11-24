<?php
// app/Filament/Resources/CoffreResource/Pages/ManageCoffre.php

namespace App\Filament\Resources\CoffreResource\Pages;

use App\Filament\Resources\Coffres\CoffreResource;
use App\Models\CashRegister;
use App\Models\MouvementCoffre;
use App\Services\CoffreService;
use App\Services\ComptabilityService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManageCoffre extends ManageRecords
{
    protected static string $resource = CoffreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Alimentation depuis Banque/Partenaire
            Action::make('alimenter_banque')
                ->label('Alimentation Banque/Partenaire')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre'),
                    TextInput::make('montant')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Montant'),
                    Select::make('devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required()
                        ->default('USD'),
                    Select::make('source')
                        ->options([
                            'banque' => 'Banque',
                            'partenaire' => 'Partenaire',
                            'autre' => 'Autre'
                        ])
                        ->required()
                        ->label('Source'),
                    TextInput::make('reference')
                        ->required()
                        ->label('Référence'),
                    Textarea::make('description')
                        ->label('Description'),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            $coffre = CashRegister::find($data['coffre_id']);
                            
                            // Créer mouvement physique
                            $mouvement = $coffre->alimenter(
                                $data['montant'],
                                $data['source'],
                                $data['reference'],
                                $data['description']
                            );

                            // Enregistrement comptable OBLIGATOIRE
                            app(ComptabilityService::class)->enregistrerAlimentationCoffre(
                                $mouvement->id, 
                                $data['reference']
                            );
                        });

                        Notification::make()
                            ->title('Alimentation réussie')
                            ->body("Coffre alimenté de {$data['montant']} {$data['devise']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // 2. Transfert vers Comptabilité
            Action::make('transferer_comptabilite')
                ->label('Transfert vers Comptabilité')
                ->icon('heroicon-o-arrow-right')
                ->color('warning')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre Source'),
                    TextInput::make('montant')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Montant'),
                    Select::make('devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required()
                        ->default('USD'),
                    TextInput::make('motif')
                        ->required()
                        ->label('Motif du transfert'),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            $coffre = CashRegister::find($data['coffre_id']);
                            
                            // Créer mouvement physique
                            $mouvement = $coffre->transfererVersComptabilite(
                                $data['montant'],
                                $data['motif']
                            );

                            // Enregistrement comptable OBLIGATOIRE
                            app(ComptabilityService::class)->enregistrerTransfertCoffreVersComptable($mouvement->id);
                        });

                        Notification::make()
                            ->title('Transfert réussi')
                            ->body("{$data['montant']} {$data['devise']} transférés vers la comptabilité")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // 3. Rapport Global des Coffres
            Action::make('rapport_coffres_global')
                ->label('Rapport Global des Coffres')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->schema([
                    DatePicker::make('date_rapport')
                        ->label('Date du rapport')
                        ->default(now())
                        ->required(),
                    Toggle::make('inclure_mouvements')
                        ->label('Inclure le détail des mouvements')
                        ->default(true),
                ])
                // Dans l'action rapport_coffres_global
->action(function (array $data) {
    try {
        // Récupérer tous les coffres
        $coffres = CashRegister::all();
        
        // Récupérer les mouvements pour tous les coffres
        $mouvements = $data['inclure_mouvements'] ? 
            MouvementCoffre::whereDate('date_mouvement', $data['date_rapport'])
                ->with(['coffre', 'operateur']) // CORRECTION: utiliser 'coffre' au lieu de 'coffreSource'
                ->get() : collect([]);

        // Générer les données du rapport global
        $rapportData = self::genererRapportGlobalCoffres($coffres, $mouvements, $data['date_rapport']);
        
        // Inclure le logo
        $rapportData['logo_base64'] = self::getLogoBase64();

        $html = view('pdf.rapport-coffres-global', [
            'rapport' => $rapportData,
            'coffres' => $coffres,
            'mouvements' => $mouvements,
            'date_rapport' => $data['date_rapport'],
            'inclure_mouvements' => $data['inclure_mouvements']
        ])->render();

        $filename = 'rapport-global-coffres-' . $data['date_rapport'] . '.html';
        
        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename);

    } catch (\Exception $e) {
        Notification::make()
            ->title('Erreur')
            ->body('Impossible de générer le rapport: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}),
        ];
    }
private static function genererRapportGlobalCoffres($coffres, $mouvements, $dateRapport)
{
    // Convertir la date en objet Carbon si c'est une chaîne
    if (is_string($dateRapport)) {
        $dateRapport = Carbon::parse($dateRapport);
    }
    
    $rapport = [
        'date_rapport' => $dateRapport->format('d/m/Y'),
        'total_coffres' => $coffres->count(),
        'usd' => [
            'solde_total' => 0,
            'total_entrees' => 0,
            'total_sorties' => 0,
            'coffres' => []
        ],
        'cdf' => [
            'solde_total' => 0,
            'total_entrees' => 0,
            'total_sorties' => 0,
            'coffres' => []
        ],
        'mouvements_detail' => []
    ];

    foreach ($coffres as $coffre) {
        // CORRECTION: Rechercher les mouvements par coffre_id
        $mouvementsCoffre = $mouvements->where('coffre_id', $coffre->id);
        
        // CORRECTION: Calcul des entrées et sorties
        $entrees = $mouvementsCoffre->where('type_mouvement', 'entree')->sum('montant');
        $sorties = $mouvementsCoffre->where('type_mouvement', 'sortie')->sum('montant');
        
        $coffreData = [
            'nom' => $coffre->nom,
            'solde_initial' => $coffre->solde_actuel - $entrees + $sorties,
            'solde_final' => $coffre->solde_actuel,
            'entrees' => $entrees,
            'sorties' => $sorties,
            'operations' => $mouvementsCoffre->count(),
            'responsable' => $coffre->responsable->name ?? 'Non assigné'
        ];

        if ($coffre->devise === 'USD') {
            $rapport['usd']['coffres'][] = $coffreData;
            $rapport['usd']['solde_total'] += $coffre->solde_actuel;
            $rapport['usd']['total_entrees'] += $entrees;
            $rapport['usd']['total_sorties'] += $sorties;
        } else {
            $rapport['cdf']['coffres'][] = $coffreData;
            $rapport['cdf']['solde_total'] += $coffre->solde_actuel;
            $rapport['cdf']['total_entrees'] += $entrees;
            $rapport['cdf']['total_sorties'] += $sorties;
        }
    }

    // Détail des mouvements - CORRECTION SIMPLIFIÉE
    foreach ($mouvements as $mouvement) {
        // Convertir la date du mouvement en Carbon si nécessaire
        $dateMouvement = $mouvement->date_mouvement;
        if (is_string($dateMouvement)) {
            $dateMouvement = Carbon::parse($dateMouvement);
        }
        
        // CORRECTION: Utiliser directement la relation coffre
        $coffreNom = $mouvement->coffre ? $mouvement->coffre->nom : 'N/A';
        
        $rapport['mouvements_detail'][] = [
            'heure' => $dateMouvement->format('H:i'),
            'coffre' => $coffreNom,
            'type' => $mouvement->type_mouvement === 'entree' ? 'depot' : 'retrait',
            'montant' => $mouvement->montant,
            'devise' => $mouvement->devise,
            'description' => $mouvement->description,
            'source_destination' => $mouvement->source_type ?? $mouvement->destination_type ?? 'N/A',
            'reference' => $mouvement->reference,
            'operateur' => $mouvement->operateur->name ?? 'Système'
        ];
    }

    return $rapport;
}

    private static function getLogoBase64()
    {
        $logoPath = public_path('images/logo-tumaini1.png'); // Chemin vers votre logo
        if (file_exists($logoPath)) {
            $imageData = base64_encode(file_get_contents($logoPath));
            $src = 'data: '.mime_content_type($logoPath).';base64,'.$imageData;
            return $src;
        }
        return ''; // Retourne vide si le logo n'existe pas
    }
}