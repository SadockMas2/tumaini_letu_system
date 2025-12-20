<?php
// app/Services/TresorerieService.php

namespace App\Services;

use App\Models\Caisse;
use App\Models\Mouvement;
use App\Models\RapportTresorerie;
use App\Models\RapportTresorerieCaisse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TresorerieService
{
    /**
     * Générer un rapport de trésorerie à tout moment (sans vérification de date)
     */
    public function genererRapportTresorerie($date = null, $forcer = false)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        
        return DB::transaction(function () use ($date, $forcer) {
            // Si forcer = false, vérifier si un rapport existe déjà
            if (!$forcer) {
                $rapportExistant = RapportTresorerie::whereDate('date_rapport', $date)->first();
                if ($rapportExistant) {
                    throw new \Exception('Un rapport existe déjà pour cette date. Utilisez l\'option "forcer" pour regénérer.');
                }
            }

            // Créer le rapport
            $rapport = RapportTresorerie::create([
                'date_rapport' => $date,
                'statut' => 'finalise'
            ]);

            // Récupérer uniquement les grandes caisses
            $grandesCaisses = Caisse::where('type_caisse', 'like', '%grande%')->get();
            
            $totalDepotsUSD = 0;
            $totalRetraitsUSD = 0;
            $totalDepotsCDF = 0;
            $totalRetraitsCDF = 0;
            $nombreOperations = 0;
            $soldeTotalUSD = 0;
            $soldeTotalCDF = 0;

            foreach ($grandesCaisses as $caisse) {
                // Récupérer les mouvements de la journée
                $mouvements = Mouvement::where('caisse_id', $caisse->id)
                    ->whereDate('created_at', $date)
                    ->get();

                $depotsCaisse = $mouvements->where('type', 'depot')->sum('montant');
                $retraitsCaisse = $mouvements->where('type', 'retrait')->sum('montant');
                $operationsCaisse = $mouvements->count();

                // Calculer le solde initial (solde actuel - mouvements du jour)
                $mouvementsJour = $depotsCaisse - $retraitsCaisse;
                $soldeInitial = $caisse->solde - $mouvementsJour;
                
                // S'assurer que les soldes ne sont pas négatifs
                $soldeInitial = max(0, $soldeInitial);
                $soldeFinal = $caisse->solde;

                // Créer le détail pour cette caisse
                RapportTresorerieCaisse::create([
                    'rapport_tresorerie_id' => $rapport->id,
                    'caisse_id' => $caisse->id,
                    'type_caisse' => $caisse->type_caisse,
                    'solde_initial' => $soldeInitial,
                    'solde_final' => $soldeFinal,
                    'nombre_operations' => $operationsCaisse,
                    'total_mouvements' => $depotsCaisse + $retraitsCaisse
                ]);

                // Ajouter aux totaux par devise
                if ($caisse->devise === 'USD') {
                    $totalDepotsUSD += $depotsCaisse;
                    $totalRetraitsUSD += $retraitsCaisse;
                    $soldeTotalUSD += $caisse->solde;
                } elseif ($caisse->devise === 'CDF') {
                    $totalDepotsCDF += $depotsCaisse;
                    $totalRetraitsCDF += $retraitsCaisse;
                    $soldeTotalCDF += $caisse->solde;
                }

                $nombreOperations += $operationsCaisse;
            }

            // Mettre à jour les totaux du rapport
            $rapport->update([
                'total_depots' => $totalDepotsUSD + $totalDepotsCDF,
                'total_retraits' => $totalRetraitsUSD + $totalRetraitsCDF,
                'nombre_operations' => $nombreOperations,
                'solde_total_caisses' => $soldeTotalUSD + $soldeTotalCDF,
                'statut' => 'finalise',
                'observations' => "USD: Dépots " . number_format($totalDepotsUSD, 2) . 
                                " - Retraits " . number_format($totalRetraitsUSD, 2) . 
                                " - Solde " . number_format($soldeTotalUSD, 2) . 
                                " | CDF: Dépots " . number_format($totalDepotsCDF, 2) . 
                                " - Retraits " . number_format($totalRetraitsCDF, 2) . 
                                " - Solde " . number_format($soldeTotalCDF, 2)
            ]);

            return $rapport;
        });
    }

    /**
     * Générer un rapport instantané (sans sauvegarde en base)
     */
/**
 * Générer un rapport instantané (sans sauvegarde en base)
 */
public function rapportInstantanee($date = null)
{
    $date = $date ? Carbon::parse($date) : Carbon::now();
    
    // Récupérer uniquement les grandes caisses
    $grandesCaisses = Caisse::where('type_caisse', 'like', '%grande%')
        ->with(['mouvements' => function($query) use ($date) {
            $query->whereDate('created_at', $date)
                  ->orderBy('created_at', 'asc');
        }])
        ->get();

    $rapport = [
        'date_rapport' => $date->format('d/m/Y'),
        'date_generation' => Carbon::now()->format('d/m/Y H:i:s'),
        'logo_base64' => $this->getLogoBase64(),
        'type' => 'instantanee',
        'usd' => [
            'solde_total' => 0,
            'depots' => 0,
            'retraits' => 0,
            'delaistages' => 0,
            'operations' => 0,
            'variation_nette' => 0, // NOUVEAU: variation nette (+/-)
            'caisses' => []
        ],
        'cdf' => [
            'solde_total' => 0,
            'depots' => 0,
            'retraits' => 0,
            'delaistages' => 0,
            'operations' => 0,
            'variation_nette' => 0, // NOUVEAU: variation nette (+/-)
            'caisses' => []
        ],
        'mouvements_detail' => []
    ];

    foreach ($grandesCaisses as $caisse) {
        $mouvements = $caisse->mouvements;
        
        // Séparer les dépôts (+) et retraits (-)
        $depots = $mouvements->where('type', 'depot')->sum('montant');
        $retraits = $mouvements->where('type', 'retrait')->sum('montant');
        
        // NOUVEAU: Calcul spécifique des délaistages
        $delaistages = $mouvements->where('type_mouvement', 'delaisage_comptabilite')->sum('montant');
        
        $operations = $mouvements->count();

        $mouvementsJour = $depots - $retraits;
        $soldeInitial = $caisse->solde - $mouvementsJour;

        $caisseData = [
            'nom' => $caisse->nom,
            'solde_initial' => $soldeInitial,
            'solde_final' => $caisse->solde,
            'depots' => $depots,
            'retraits' => $retraits,
            'delaistages' => $delaistages,
            'operations' => $operations,
            'variation_nette' => $mouvementsJour, // NOUVEAU: variation (+/-)
            'mouvements' => $mouvements->map(function($mouvement) {
                // Déterminer le signe selon le type
                $signe = $mouvement->type === 'depot' ? '+' : '-';
                $montantAvecSigne = $signe . ' ' . number_format($mouvement->montant, 2);
                
                return [
                    'type' => $mouvement->type,
                    'type_mouvement' => $mouvement->type_mouvement,
                    'montant' => $mouvement->montant,
                    'montant_avec_signe' => $montantAvecSigne, // NOUVEAU: avec signe
                    'description' => $mouvement->description,
                    'nom_deposant' => $mouvement->nom_deposant,
                    'client_nom' => $mouvement->client_nom,
                    'operateur' => $mouvement->operateur->name ?? 'N/A',
                    'heure' => $mouvement->created_at->format('H:i:s'),
                    'solde_avant' => $mouvement->solde_avant,
                    'solde_apres' => $mouvement->solde_apres,
                    'signe' => $signe // NOUVEAU: pour le formatage
                ];
            })->values()
        ];

        if ($caisse->devise === 'USD') {
            $rapport['usd']['solde_total'] += $caisse->solde;
            $rapport['usd']['depots'] += $depots;
            $rapport['usd']['retraits'] += $retraits;
            $rapport['usd']['delaistages'] += $delaistages;
            $rapport['usd']['operations'] += $operations;
            $rapport['usd']['variation_nette'] += $mouvementsJour; // NOUVEAU
            $rapport['usd']['caisses'][] = $caisseData;
        } elseif ($caisse->devise === 'CDF') {
            $rapport['cdf']['solde_total'] += $caisse->solde;
            $rapport['cdf']['depots'] += $depots;
            $rapport['cdf']['retraits'] += $retraits;
            $rapport['cdf']['delaistages'] += $delaistages;
            $rapport['cdf']['operations'] += $operations;
            $rapport['cdf']['variation_nette'] += $mouvementsJour; // NOUVEAU
            $rapport['cdf']['caisses'][] = $caisseData;
        }

        // Ajouter aux mouvements détaillés avec signes
        foreach ($mouvements as $mouvement) {
            $signe = $mouvement->type === 'depot' ? '+' : '-';
            $montantAvecSigne = $signe . ' ' . number_format($mouvement->montant, 2);
            
            $rapport['mouvements_detail'][] = [
                'caisse' => $caisse->nom,
                'devise' => $caisse->devise,
                'type' => $mouvement->type,
                'type_mouvement' => $mouvement->type_mouvement,
                'montant' => $mouvement->montant,
                'montant_avec_signe' => $montantAvecSigne, // NOUVEAU
                'description' => $mouvement->description,
                'nom_deposant' => $mouvement->nom_deposant,
                'client_nom' => $mouvement->client_nom,
                'operateur' => $mouvement->operateur->name ?? 'N/A',
                'heure' => $mouvement->created_at->format('H:i:s'),
                'solde_avant' => $mouvement->solde_avant,
                'solde_apres' => $mouvement->solde_apres,
                'signe' => $signe, // NOUVEAU
                'couleur' => $mouvement->type === 'depot' ? 'success' : 'danger' // Pour le CSS
            ];
        }
    }

    // Trier les mouvements par heure
    usort($rapport['mouvements_detail'], function($a, $b) {
        return strtotime($a['heure']) - strtotime($b['heure']);
    });

    // Calculer les totaux globaux avec signes
    $rapport['total_global'] = [
        'solde_total' => $rapport['usd']['solde_total'] + $rapport['cdf']['solde_total'],
        'total_depots' => $rapport['usd']['depots'] + $rapport['cdf']['depots'],
        'total_retraits' => $rapport['usd']['retraits'] + $rapport['cdf']['retraits'],
        'total_operations' => $rapport['usd']['operations'] + $rapport['cdf']['operations'],
        'variation_nette' => $rapport['usd']['variation_nette'] + $rapport['cdf']['variation_nette']
    ];

    return $rapport;
}
    /**
     * Générer un rapport pour une période spécifique
     */
    public function rapportPeriode($dateDebut, $dateFin = null)
    {
        $dateDebut = Carbon::parse($dateDebut);
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();

        $grandesCaisses = Caisse::where('type_caisse', 'like', '%grande%')->get();

        $rapport = [
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'date_generation' => Carbon::now()->format('d/m/Y H:i:s'),
            'type' => 'periode',
            'usd' => [
                'solde_total' => 0,
                'depots' => 0,
                'retraits' => 0,
                'operations' => 0
            ],
            'cdf' => [
                'solde_total' => 0,
                'depots' => 0,
                'retraits' => 0,
                'operations' => 0
            ],
            'evolution_journaliere' => []
        ];

        // Générer l'évolution jour par jour
        $dateCourante = $dateDebut->copy();
        while ($dateCourante <= $dateFin) {
            $jourData = [
                'date' => $dateCourante->format('d/m/Y'),
                'usd' => ['depots' => 0, 'retraits' => 0, 'solde' => 0],
                'cdf' => ['depots' => 0, 'retraits' => 0, 'solde' => 0]
            ];

            foreach ($grandesCaisses as $caisse) {
                $mouvements = Mouvement::where('caisse_id', $caisse->id)
                    ->whereDate('created_at', $dateCourante)
                    ->get();

                $depots = $mouvements->where('type', 'depot')->sum('montant');
                $retraits = $mouvements->where('type', 'retrait')->sum('montant');

                if ($caisse->devise === 'USD') {
                    $jourData['usd']['depots'] += $depots;
                    $jourData['usd']['retraits'] += $retraits;
                    $jourData['usd']['solde'] += $caisse->solde;
                } elseif ($caisse->devise === 'CDF') {
                    $jourData['cdf']['depots'] += $depots;
                    $jourData['cdf']['retraits'] += $retraits;
                    $jourData['cdf']['solde'] += $caisse->solde;
                }
            }

            $rapport['evolution_journaliere'][] = $jourData;
            $dateCourante->addDay();
        }

        // Totaux pour la période
        foreach ($grandesCaisses as $caisse) {
            $mouvements = Mouvement::where('caisse_id', $caisse->id)
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->get();

            $depots = $mouvements->where('type', 'depot')->sum('montant');
            $retraits = $mouvements->where('type', 'retrait')->sum('montant');
            $operations = $mouvements->count();

            if ($caisse->devise === 'USD') {
                $rapport['usd']['solde_total'] += $caisse->solde;
                $rapport['usd']['depots'] += $depots;
                $rapport['usd']['retraits'] += $retraits;
                $rapport['usd']['operations'] += $operations;
            } elseif ($caisse->devise === 'CDF') {
                $rapport['cdf']['solde_total'] += $caisse->solde;
                $rapport['cdf']['depots'] += $depots;
                $rapport['cdf']['retraits'] += $retraits;
                $rapport['cdf']['operations'] += $operations;
            }
        }

        return $rapport;
    }

    /**
     * Générer un rapport PDF détaillé
     */
    public function genererRapportDetaillePDF($date = null)
    {
        return $this->rapportInstantanee($date);
    }

      /**
     * Convertir une image en base64 pour l'inclure dans le PDF
     */
    private function getLogoBase64()
    {
        $logoPath = public_path('images/logo-tumaini1.png');
        
        // Essayer différents formats d'image
        $possiblePaths = [
            public_path('images/logo-tumaini1.png'),
            public_path('images/logo-tumaini1.jpg'),
            public_path('images/logo-tumaini1.jpeg'),
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('storage/images/logo-tumaini1.png'),
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $logoPath = $path;
                break;
            }
        }
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $imageInfo = getimagesize($logoPath);
            $mimeType = $imageInfo['mime'] ?? 'image/png';
            
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
        
        // Retourner une image placeholder si le logo n'est pas trouvé
        return $this->getDefaultLogo();
    }
    

       /**
     * Générer un logo par défaut en base64
     */
    private function getDefaultLogo()
    {
        $svg = '<svg width="140" height="70" xmlns="http://www.w3.org/2000/svg">
                <rect width="140" height="70" fill="#f0f0f0" stroke="#ccc" stroke-width="1"/>
                <text x="70" y="35" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="12" fill="#666">
                    TUMAINI LETU
                </text>
                <text x="70" y="50" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="8" fill="#999">
                    MICROFINANCE
                </text>
            </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
}