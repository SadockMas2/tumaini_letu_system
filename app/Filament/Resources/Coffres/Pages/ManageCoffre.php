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
    ->action(function (array $data) {
        try {
            // Utiliser le service pour générer le rapport
            $coffreService = app(CoffreService::class);
            $rapport = $coffreService->genererRapportGlobal(
                $data['date_rapport'],
                $data['inclure_mouvements']
            );
            
            // Inclure le logo
            $rapport['logo_base64'] = self::getLogoBase64();

            $html = view('pdf.rapport-coffres-global', [
                'rapport' => $rapport,
                'date_rapport' => $data['date_rapport'],
                'inclure_mouvements' => $data['inclure_mouvements']
            ])->render();

            $filename = 'rapport-global-coffres-' . Carbon::parse($data['date_rapport'])->format('Y-m-d') . '.html';
            
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

    // Dans ManageCoffre.php, ajoutez une nouvelle action
// Action::make('evolution_coffres')
//     ->label('Évolution des Coffres')
//     ->icon('heroicon-o-chart-bar')
//     ->color('success')
//     ->schema([
//         DatePicker::make('date_debut')
//             ->label('Date de début')
//             ->default(now()->subMonth())
//             ->required(),
//         DatePicker::make('date_fin')
//             ->label('Date de fin')
//             ->default(now())
//             ->required(),
//     ])
//     ->action(function (array $data) {
//         try {
//             $coffreService = app(CoffreService::class);
//             $evolution = $coffreService->getEvolutionGlobale(
//                 $data['date_debut'],
//                 $data['date_fin']
//             );
            
//             $html = view('pdf.evolution-coffres', [
//                 'evolution' => $evolution
//             ])->render();

//             $filename = 'evolution-coffres-' . 
//                 Carbon::parse($data['date_debut'])->format('Y-m-d') . '_a_' .
//                 Carbon::parse($data['date_fin'])->format('Y-m-d') . '.html';
            
//             return response()->streamDownload(function () use ($html) {
//                 echo $html;
//             }, $filename);

//         } catch (\Exception $e) {
//             Notification::make()
//                 ->title('Erreur')
//                 ->body('Impossible de générer le rapport d\'évolution: ' . $e->getMessage())
//                 ->danger()
//                 ->send();
//         }
//     }),

// Dans ManageCoffre.php, modifiez l'action rapport_mouvements_periode
Action::make('rapport_mouvements_periode')
    ->label('Rapport Mouvements Période')
    ->icon('heroicon-o-document-text')
    ->color('gray')
    ->schema([
        DatePicker::make('date_debut')
            ->label('Date de début')
            ->default(now()->subWeek())
            ->required(),
        DatePicker::make('date_fin')
            ->label('Date de fin')
            ->default(now())
            ->required(),
        Select::make('devise')
            ->label('Devise')
            ->options([
                '' => 'Toutes',
                'USD' => 'USD seulement',
                'CDF' => 'CDF seulement'
            ])
            ->default(''),
    ])
    ->action(function (array $data) {
        try {
            $coffreService = app(CoffreService::class);
            $resultats = $coffreService->getMouvementsParPeriode(
                $data['date_debut'],
                $data['date_fin'],
                $data['devise'] ?: null
            );
            
            // Calculer le nombre de jours dans la période
            $dateDebut = Carbon::parse($data['date_debut']);
            $dateFin = Carbon::parse($data['date_fin']);
            $periodeJours = $dateDebut->diffInDays($dateFin) + 1;
            
            $html = view('pdf.mouvements-periode', [
                'periode' => $resultats['periode'],
                'mouvements' => $resultats['mouvements'],
                'total_usd_entrees' => $resultats['total_usd_entrees'],
                'total_usd_sorties' => $resultats['total_usd_sorties'],
                'total_cdf_entrees' => $resultats['total_cdf_entrees'],
                'total_cdf_sorties' => $resultats['total_cdf_sorties'],
                'count_total' => $resultats['count_total'],
                'count_usd' => $resultats['count_usd'],
                'count_cdf' => $resultats['count_cdf'],
                'periode_jours' => $periodeJours,
                'logo_base64' => self::getLogoBase64() // AJOUTEZ CETTE LIGNE
            ])->render();

            $filename = 'mouvements-coffres-' . 
                Carbon::parse($data['date_debut'])->format('Y-m-d') . '_a_' .
                Carbon::parse($data['date_fin'])->format('Y-m-d') . '.html';
            
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