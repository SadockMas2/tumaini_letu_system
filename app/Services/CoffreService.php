<?php
// app/Services/CoffreService.php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\RapportCoffre;
use App\Models\MouvementCoffre;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CoffreService
{
    public function genererRapportQuotidien($coffreId, $date, $soldePhysique, $observations = null)
    {
        return RapportCoffre::genererRapportQuotidien($coffreId, $date, $soldePhysique, $observations);
    }

    public function alimenterCoffre($coffreId, $montant, $source, $reference, $devise = 'USD', $description = null)
    {
        $coffre = CashRegister::findOrFail($coffreId);
        
        // Créer mouvement physique
        $mouvement = MouvementCoffre::create([
            'coffre_destination_id' => $coffre->id,
            'type_mouvement' => 'entree',
            'montant' => $montant,
            'devise' => $devise,
            'source_type' => $source,
            'reference' => $reference,
            'description' => $description ?? "Alimentation depuis {$source}",
            'date_mouvement' => now(),
            'operateur_id' => Auth::id()
        ]);

        // Mettre à jour le solde du coffre
        $coffre->solde_actuel += $montant;
        $coffre->save();

        return $mouvement;
    }

    public function transfererVersComptable($coffreId, $montant, $devise = 'USD', $motif = 'Transfert comptable')
    {
        $coffre = CashRegister::findOrFail($coffreId);
        
        if ($coffre->solde_actuel < $montant) {
            throw new \Exception('Solde insuffisant dans le coffre');
        }

        // Créer mouvement physique
        $mouvement = MouvementCoffre::create([
            'coffre_source_id' => $coffre->id,
            'type_mouvement' => 'sortie',
            'montant' => $montant,
            'devise' => $devise,
            'destination_type' => 'comptable',
            'reference' => 'TRANSF-COMPT-' . now()->format('YmdHis'),
            'description' => "Transfert vers comptable - {$motif}",
            'date_mouvement' => now(),
            'operateur_id' => Auth::id()
        ]);

        // Mettre à jour le solde du coffre
        $coffre->solde_actuel -= $montant;
        $coffre->save();

        return $mouvement;
    }

    
    
// Dans votre CoffreService.php, remplacez la méthode genererRapportGlobal par :

public function genererRapportGlobal($dateRapport = null, $inclureMouvements = true)
{
    $date = $dateRapport ? Carbon::parse($dateRapport) : Carbon::now();
    $dateDebutJour = $date->copy()->startOfDay();
    $dateFinJour = $date->copy()->endOfDay();
    
    // Récupérer les coffres
    $coffreUSD = CashRegister::where('devise', 'USD')->first();
    $coffreCDF = CashRegister::where('devise', 'CDF')->first();
    
    // Si pas de coffres trouvés
    if (!$coffreUSD || !$coffreCDF) {
        throw new \Exception('Coffres USD et/ou CDF non trouvés');
    }
    
    // Initialiser le tableau avec mouvements_detail
    $rapport = [
        'date_rapport' => $date->format('d/m/Y'),
        'total_coffres' => 2,
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
        'mouvements_detail' => [] // AJOUTEZ CETTE LIGNE
    ];
    
    // Calculer les soldes POUR LA DATE SPÉCIFIÉE (pas le solde actuel)
    // Pour USD
    $soldeInitialUSD = $coffreUSD->solde_initial ?? 0;
    
    $entreesUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'entree')
        ->where('date_mouvement', '<', $dateFinJour)
        ->sum('montant');
    
    $sortiesUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'sortie')
        ->where('date_mouvement', '<', $dateFinJour)
        ->sum('montant');
    
    $soldeFinalUSD = $soldeInitialUSD + $entreesUSD - $sortiesUSD;
    
    // Calculer le solde à minuit (solde initial du jour)
    $entreesAvantJourUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'entree')
        ->where('date_mouvement', '<', $dateDebutJour)
        ->sum('montant');
    
    $sortiesAvantJourUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'sortie')
        ->where('date_mouvement', '<', $dateDebutJour)
        ->sum('montant');
    
    $soldeMinuitUSD = $soldeInitialUSD + $entreesAvantJourUSD - $sortiesAvantJourUSD;
    
    // Entrées et sorties DU JOUR SPÉCIFIQUE
    $entreesJourUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'entree')
        ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
        ->sum('montant');
    
    $sortiesJourUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'sortie')
        ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
        ->sum('montant');
    
    // Pour CDF
    $soldeInitialCDF = $coffreCDF->solde_initial ?? 0;
    
    $entreesCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'entree')
        ->where('date_mouvement', '<', $dateFinJour)
        ->sum('montant');
    
    $sortiesCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'sortie')
        ->where('date_mouvement', '<', $dateFinJour)
        ->sum('montant');
    
    $soldeFinalCDF = $soldeInitialCDF + $entreesCDF - $sortiesCDF;
    
    // Calculer le solde à minuit (solde initial du jour)
    $entreesAvantJourCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'entree')
        ->where('date_mouvement', '<', $dateDebutJour)
        ->sum('montant');
    
    $sortiesAvantJourCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'sortie')
        ->where('date_mouvement', '<', $dateDebutJour)
        ->sum('montant');
    
    $soldeMinuitCDF = $soldeInitialCDF + $entreesAvantJourCDF - $sortiesAvantJourCDF;
    
    // Entrées et sorties DU JOUR SPÉCIFIQUE
    $entreesJourCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'entree')
        ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
        ->sum('montant');
    
    $sortiesJourCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'sortie')
        ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
        ->sum('montant');
    
    // Récupérer les mouvements détaillés si demandé
    if ($inclureMouvements) {
        // Mouvements USD du jour
        $mouvementsUSD = MouvementCoffre::where('devise', 'USD')
            ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
            ->with('operateur')
            ->orderBy('date_mouvement')
            ->get();
        
        foreach ($mouvementsUSD as $mouvement) {
            $rapport['mouvements_detail'][] = [
                'heure' => $mouvement->date_mouvement->format('H:i'),
                'coffre' => $coffreUSD->nom,
                'type' => $mouvement->type_mouvement === 'entree' ? 'depot' : 'retrait',
                'montant' => $mouvement->montant,
                'devise' => $mouvement->devise,
                'description' => $mouvement->description,
                'source_destination' => $mouvement->type_mouvement === 'entree' 
                    ? ($mouvement->source_type ?? 'N/A')
                    : ($mouvement->destination_type ?? 'N/A'),
                'reference' => $mouvement->reference,
                'operateur' => $mouvement->operateur->name ?? 'N/A'
            ];
        }
        
        // Mouvements CDF du jour
        $mouvementsCDF = MouvementCoffre::where('devise', 'CDF')
            ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
            ->with('operateur')
            ->orderBy('date_mouvement')
            ->get();
        
        foreach ($mouvementsCDF as $mouvement) {
            $rapport['mouvements_detail'][] = [
                'heure' => $mouvement->date_mouvement->format('H:i'),
                'coffre' => $coffreCDF->nom,
                'type' => $mouvement->type_mouvement === 'entree' ? 'depot' : 'retrait',
                'montant' => $mouvement->montant,
                'devise' => $mouvement->devise,
                'description' => $mouvement->description,
                'source_destination' => $mouvement->type_mouvement === 'entree' 
                    ? ($mouvement->source_type ?? 'N/A')
                    : ($mouvement->destination_type ?? 'N/A'),
                'reference' => $mouvement->reference,
                'operateur' => $mouvement->operateur->name ?? 'N/A'
            ];
        }
    }
    
    // Compter les opérations
    $operationsUSD = $inclureMouvements 
        ? count($mouvementsUSD ?? []) 
        : MouvementCoffre::where('devise', 'USD')
            ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
            ->count();
    
    $operationsCDF = $inclureMouvements 
        ? count($mouvementsCDF ?? []) 
        : MouvementCoffre::where('devise', 'CDF')
            ->whereBetween('date_mouvement', [$dateDebutJour, $dateFinJour])
            ->count();
    
    // Compléter le rapport
    $rapport['usd'] = [
        'solde_total' => $soldeFinalUSD,
        'total_entrees' => $entreesJourUSD,
        'total_sorties' => $sortiesJourUSD,
        'coffres' => [[
            'nom' => $coffreUSD->nom,
            'responsable' => $coffreUSD->responsable->name ?? 'Non assigné',
            'solde_initial' => $soldeMinuitUSD,
            'solde_final' => $soldeFinalUSD,
            'entrees' => $entreesJourUSD,
            'sorties' => $sortiesJourUSD,
            'operations' => $operationsUSD,
            'devise' => 'USD'
        ]]
    ];
    
    $rapport['cdf'] = [
        'solde_total' => $soldeFinalCDF,
        'total_entrees' => $entreesJourCDF,
        'total_sorties' => $sortiesJourCDF,
        'coffres' => [[
            'nom' => $coffreCDF->nom,
            'responsable' => $coffreCDF->responsable->name ?? 'Non assigné',
            'solde_initial' => $soldeMinuitCDF,
            'solde_final' => $soldeFinalCDF,
            'entrees' => $entreesJourCDF,
            'sorties' => $sortiesJourCDF,
            'operations' => $operationsCDF,
            'devise' => 'CDF'
        ]]
    ];
    
    return $rapport;
}

// Dans CoffreService.php, ajoutez cette méthode
public function getMouvementsParPeriode($dateDebut, $dateFin = null, $devise = null)
{
    $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();
    $dateDebut = Carbon::parse($dateDebut);
    
    $query = MouvementCoffre::with('operateur')
        ->whereBetween('date_mouvement', [
            $dateDebut->startOfDay(),
            $dateFin->endOfDay()
        ])
        ->orderBy('date_mouvement');
    
    if ($devise) {
        $query->where('devise', $devise);
    }
    
    $mouvements = $query->get();
    
    // Calculer les totaux
    $totalEntreesUSD = $mouvements->where('devise', 'USD')
        ->where('type_mouvement', 'entree')
        ->sum('montant');
    
    $totalSortiesUSD = $mouvements->where('devise', 'USD')
        ->where('type_mouvement', 'sortie')
        ->sum('montant');
    
    $totalEntreesCDF = $mouvements->where('devise', 'CDF')
        ->where('type_mouvement', 'entree')
        ->sum('montant');
    
    $totalSortiesCDF = $mouvements->where('devise', 'CDF')
        ->where('type_mouvement', 'sortie')
        ->sum('montant');
    
    return [
        'periode' => [
            'debut' => $dateDebut->format('d/m/Y'),
            'fin' => $dateFin->format('d/m/Y')
        ],
        'mouvements' => $mouvements,
        'total_usd_entrees' => $totalEntreesUSD,
        'total_usd_sorties' => $totalSortiesUSD,
        'total_cdf_entrees' => $totalEntreesCDF,
        'total_cdf_sorties' => $totalSortiesCDF,
        'count_total' => $mouvements->count(),
        'count_usd' => $mouvements->where('devise', 'USD')->count(),
        'count_cdf' => $mouvements->where('devise', 'CDF')->count()
    ];
}


private function calculerSoldeInitial($coffreId, $dateDebut)
{
    // Récupérer le coffre avec son solde initial
    $coffre = CashRegister::find($coffreId);
    
    if (!$coffre) {
        return 0;
    }
    
    // Le solde initial du coffre (enregistré lors de sa création)
    $soldeBase = $coffre->solde_initial ?? 0;
    
    // CORRECTION : Utiliser les bons champs pour les mouvements
    // Entrées : où ce coffre est destination
    $totalEntrees = MouvementCoffre::where('coffre_destination_id', $coffreId)
        ->where('date_mouvement', '<', $dateDebut)
        ->where('devise', $coffre->devise)
        ->sum('montant');
    
    // Sorties : où ce coffre est source
    $totalSorties = MouvementCoffre::where('coffre_source_id', $coffreId)
        ->where('date_mouvement', '<', $dateDebut)
        ->where('devise', $coffre->devise)
        ->sum('montant');

    // Solde à minuit de la date sélectionnée
    return $soldeBase + $totalEntrees - $totalSorties;
}





  public function getSoldeCoffreAtDate($coffreId, $date)
    {
        $date = Carbon::parse($date);
        $dateDebut = $date->copy()->startOfDay();
        
        return $this->calculerSoldeInitial($coffreId, $dateDebut);
    }


    // Dans CoffreService.php
public function getEvolutionCoffre($coffreId, $dateDebut, $dateFin = null)
{
    $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();
    $dateDebut = Carbon::parse($dateDebut);
    
    // Calculer le solde au début de la période
    $soldeDebut = $this->calculerSoldeInitial($coffreId, $dateDebut->copy()->startOfDay());
    
    // Calculer le solde à la fin de la période
    $soldeFin = $this->calculerSoldeInitial(
        $coffreId, 
        $dateFin->copy()->addDay()->startOfDay() // Jour suivant pour avoir le solde FINAL
    );
    
    // Récupérer tous les mouvements pendant la période
    $mouvements = MouvementCoffre::where(function($query) use ($coffreId, $dateDebut, $dateFin) {
            $query->where('coffre_destination_id', $coffreId)
                  ->orWhere('coffre_source_id', $coffreId);
        })
        ->whereBetween('date_mouvement', [
            $dateDebut->startOfDay(),
            $dateFin->endOfDay()
        ])
        ->orderBy('date_mouvement')
        ->get();
    
    $entrees = $mouvements->where('coffre_destination_id', $coffreId)->sum('montant');
    $sorties = $mouvements->where('coffre_source_id', $coffreId)->sum('montant');
    
    return [
        'coffre' => CashRegister::find($coffreId),
        'periode' => [
            'debut' => $dateDebut->format('d/m/Y'),
            'fin' => $dateFin->format('d/m/Y')
        ],
        'solde_debut' => $soldeDebut,
        'solde_fin' => $soldeFin,
        'evolution' => $soldeFin - $soldeDebut,
        'taux_evolution' => $soldeDebut != 0 ? (($soldeFin - $soldeDebut) / $soldeDebut) * 100 : 0,
        'entrees' => $entrees,
        'sorties' => $sorties,
        'operations' => $mouvements->count(),
        'mouvements' => $mouvements
    ];
}

public function getEvolutionGlobale($dateDebut, $dateFin = null)
{
    $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();
    $dateDebut = Carbon::parse($dateDebut);
    
    $coffres = CashRegister::all();
    $evolution = [
        'periode' => [
            'debut' => $dateDebut->format('d/m/Y'),
            'fin' => $dateFin->format('d/m/Y')
        ],
        'total_usd_debut' => 0,
        'total_usd_fin' => 0,
        'total_cdf_debut' => 0,
        'total_cdf_fin' => 0,
        'coffres' => []
    ];
    
    foreach ($coffres as $coffre) {
        $soldeDebut = $this->calculerSoldeInitial($coffre->id, $dateDebut->copy()->startOfDay());
        $soldeFin = $this->calculerSoldeInitial(
            $coffre->id, 
            $dateFin->copy()->addDay()->startOfDay()
        );
        
        // Mouvements pendant la période
        $mouvements = MouvementCoffre::where(function($query) use ($coffre, $dateDebut, $dateFin) {
                $query->where('coffre_destination_id', $coffre->id)
                      ->orWhere('coffre_source_id', $coffre->id);
            })
            ->whereBetween('date_mouvement', [
                $dateDebut->startOfDay(),
                $dateFin->endOfDay()
            ])
            ->get();
        
        $entrees = $mouvements->where('coffre_destination_id', $coffre->id)->sum('montant');
        $sorties = $mouvements->where('coffre_source_id', $coffre->id)->sum('montant');
        
        $coffreData = [
            'id' => $coffre->id,
            'nom' => $coffre->nom,
            'devise' => $coffre->devise,
            'solde_debut' => $soldeDebut,
            'solde_fin' => $soldeFin,
            'evolution' => $soldeFin - $soldeDebut,
            'entrees' => $entrees,
            'sorties' => $sorties,
            'operations' => $mouvements->count()
        ];
        
        if ($coffre->devise === 'USD') {
            $evolution['total_usd_debut'] += $soldeDebut;
            $evolution['total_usd_fin'] += $soldeFin;
        } else {
            $evolution['total_cdf_debut'] += $soldeDebut;
            $evolution['total_cdf_fin'] += $soldeFin;
        }
        
        $evolution['coffres'][] = $coffreData;
    }
    
    $evolution['evolution_usd'] = $evolution['total_usd_fin'] - $evolution['total_usd_debut'];
    $evolution['evolution_cdf'] = $evolution['total_cdf_fin'] - $evolution['total_cdf_debut'];
    
    return $evolution;
}


// Dans CoffreService.php
public function verifierCalculs($dateRapport = null)
{
    $date = $dateRapport ? Carbon::parse($dateRapport) : Carbon::now();
    $dateDebutJour = $date->copy()->startOfDay();
    $dateFinJour = $date->copy()->endOfDay();
    
    $coffreUSD = CashRegister::where('devise', 'USD')->first();
    $coffreCDF = CashRegister::where('devise', 'CDF')->first();
    
    // Totaux pour USD
    $totalEntreesUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'entree')
        ->sum('montant');
    
    $totalSortiesUSD = MouvementCoffre::where('devise', 'USD')
        ->where('type_mouvement', 'sortie')
        ->sum('montant');
    
    $soldeCalculeUSD = ($coffreUSD->solde_initial ?? 0) + $totalEntreesUSD - $totalSortiesUSD;
    
    // Totaux pour CDF
    $totalEntreesCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'entree')
        ->sum('montant');
    
    $totalSortiesCDF = MouvementCoffre::where('devise', 'CDF')
        ->where('type_mouvement', 'sortie')
        ->sum('montant');
    
    $soldeCalculeCDF = ($coffreCDF->solde_initial ?? 0) + $totalEntreesCDF - $totalSortiesCDF;
    
    return [
        'USD' => [
            'solde_table' => $coffreUSD->solde_actuel,
            'solde_initial_table' => $coffreUSD->solde_initial,
            'solde_calcule' => $soldeCalculeUSD,
            'entrees_total' => $totalEntreesUSD,
            'sorties_total' => $totalSortiesUSD,
            'difference' => $coffreUSD->solde_actuel - $soldeCalculeUSD
        ],
        'CDF' => [
            'solde_table' => $coffreCDF->solde_actuel,
            'solde_initial_table' => $coffreCDF->solde_initial,
            'solde_calcule' => $soldeCalculeCDF,
            'entrees_total' => $totalEntreesCDF,
            'sorties_total' => $totalSortiesCDF,
            'difference' => $coffreCDF->solde_actuel - $soldeCalculeCDF
        ]
    ];
}

}