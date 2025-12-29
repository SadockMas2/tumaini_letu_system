<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Credit;
use App\Models\Mouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteController extends Controller
{
  public function index()
    {
        return redirect('/admin/comptes');
    }

public function details($compte_id)
{
    $compte = Compte::with([
        'credits' => function($query) {
            $query->orderBy('created_at', 'desc');
        },
        'creditsGroupe' => function($query) {
            $query->orderBy('created_at', 'desc');
        },
        'mouvements' => function($query) {
            $query->orderBy('created_at', 'desc');
        }
    ])->findOrFail($compte_id);

    // Récupérer tous les mouvements
    $tousMouvements = Mouvement::where('compte_id', $compte_id)
        ->orWhere('numero_compte', $compte->numero_compte)
        ->orderBy('created_at', 'asc')
        ->get();

    // === LOGIQUE SIMPLIFIÉE POUR MONTANTS POSITIFS ===
    $soldeCalcule = 0;
    $totalDepots = 0;
    $totalRetraits = 0;
    $nombreDepots = 0;
    $nombreRetraits = 0;

    foreach ($tousMouvements as $mouvement) {
        $typeAffichage = \App\Helpers\MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
        $montant = floatval($mouvement->montant); // Toujours positif maintenant
        
        // LOGIQUE SIMPLE :
        if ($typeAffichage === 'depot') {
            // DÉPÔT : ajouter
            $soldeCalcule += $montant;
            $totalDepots += $montant;
            $nombreDepots++;
        } elseif ($typeAffichage === 'retrait') {
            // RETRAIT : soustraire
            $soldeCalcule -= $montant;
            $totalRetraits += $montant;
            $nombreRetraits++;
        } elseif ($typeAffichage === 'neutre') {
            // NEUTRE : ignorer (sauf si montant non nul pour certains cas)
            // Pour caution_bloquee avec montant 0, on ignore
            if (abs($montant) > 0.01) {
                $soldeCalcule += $montant;
            }
        } else {
            // AUTRE : ajouter tel quel
            $soldeCalcule += $montant;
        }
    }

    $soldeCalcule = round($soldeCalcule, 2);
    $soldeBase = round(floatval($compte->solde), 2);
    $difference = $soldeCalcule - $soldeBase;

    $statsMouvements = [
        'total_depots' => $totalDepots,
        'total_retraits' => $totalRetraits,
        'nombre_depots' => $nombreDepots,
        'nombre_retraits' => $nombreRetraits,
        'solde_calcule' => $soldeCalcule,
        'solde_base' => $soldeBase,
        'difference' => $difference
    ];

    $mouvements = Mouvement::where('compte_id', $compte_id)
        ->orWhere('numero_compte', $compte->numero_compte)
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return view('comptes.details', compact('compte', 'mouvements', 'statsMouvements'));
}

// Dans App\Http\Controllers\CompteController
public function rapportComptes()
{
    // Récupérer tous les comptes avec les relations nécessaires
    $comptes = Compte::with([
        'mouvements' => function($query) {
            $query->orderBy('created_at', 'desc');
        },
        'credits' => function($query) {
            $query->where('statut_demande', 'approuve');
        },
        'creditsGroupe' => function($query) {
            $query->where('statut_demande', 'approuve');
        }
    ])
    ->orderBy('numero_compte')
    ->get();

    // Calculer les totaux
    $totaux = [
        'usd' => [
            'solde_total' => 0,
            'nombre_comptes' => 0,
            'credits_actifs' => 0,
            'depots_jour' => 0,
            'retraits_jour' => 0
        ],
        'cdf' => [
            'solde_total' => 0,
            'nombre_comptes' => 0,
            'credits_actifs' => 0,
            'depots_jour' => 0,
            'retraits_jour' => 0
        ]
    ];

    // Pour chaque compte, calculer les statistiques
    foreach ($comptes as $compte) {
        $devise = strtolower($compte->devise);
        
        // Solde total par devise
        $totaux[$devise]['solde_total'] += floatval($compte->solde);
        $totaux[$devise]['nombre_comptes']++;
        
        // Nombre de crédits actifs
        $creditsActifs = str_starts_with($compte->numero_compte, 'GS') 
            ? $compte->creditsGroupe->where('montant_total', '>', 0)->count()
            : $compte->credits->where('montant_total', '>', 0)->count();
        
        $totaux[$devise]['credits_actifs'] += $creditsActifs;
        
        // Dépôts et retraits du jour
        $aujourdhui = now()->format('Y-m-d');
        $mouvementsJour = $compte->mouvements->filter(function($mouvement) use ($aujourdhui) {
            return $mouvement->created_at->format('Y-m-d') === $aujourdhui;
        });
        
        foreach ($mouvementsJour as $mouvement) {
            $typeAffichage = \App\Helpers\MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
            $montant = floatval($mouvement->montant);
            
            if ($typeAffichage === 'depot') {
                $totaux[$devise]['depots_jour'] += $montant;
            } elseif ($typeAffichage === 'retrait') {
                $totaux[$devise]['retraits_jour'] += $montant;
            }
        }
    }

    // Préparer les données pour la vue
    $rapport = [
        'date_rapport' => now()->format('d/m/Y'),
        'heure_generation' => now()->format('H:i:s'),
        'nombre_total_comptes' => $comptes->count(),
        'comptes' => $comptes,
        'totaux' => $totaux,
        'logo_base64' => $this->getLogoBase64() // Méthode pour encoder le logo
    ];

    return view('rapports.comptes', compact('rapport'));
}

private function getLogoBase64()
{
    // Chemin vers votre logo
    $logoPath = public_path('images/logo-tumaini1.png');
    
    if (file_exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = file_get_contents($logoPath);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    
    // Logo par défaut si non trouvé
    return 'data:image/svg+xml;base64,' . base64_encode('
        <svg xmlns="http://www.w3.org/2000/svg" width="140" height="70" viewBox="0 0 140 70">
            <rect width="140" height="70" fill="#f0f0f0"/>
            <text x="70" y="35" text-anchor="middle" fill="#333" font-family="Arial" font-size="12">
                TUMAINI LETU
            </text>
        </svg>
    ');
}


public function exportReleve($compte_id)
{
    $compte = Compte::findOrFail($compte_id);
    $mouvements = Mouvement::where('compte_id', $compte_id)
        ->orWhere('numero_compte', $compte->numero_compte)
        ->orderBy('created_at', 'desc')
        ->get();

    // Calculer les statistiques pour l'export
    $statsMouvements = [
        'total_depots' => $mouvements->where('type', 'depot')->sum('montant'),
        'total_retraits' => $mouvements->where('type', 'retrait')->sum('montant'),
        'nombre_depots' => $mouvements->where('type', 'depot')->count(),
        'nombre_retraits' => $mouvements->where('type', 'retrait')->count(),
    ];

    $html = view('comptes.export-releve', compact('compte', 'mouvements', 'statsMouvements'))->render();
    
    return response()->streamDownload(function () use ($html) {
        echo $html;
    }, 'releve-compte-' . $compte->numero_compte . '-' . now()->format('d-m-Y') . '.html');
}

    // Nouvelle méthode pour accorder un crédit
    public function accorderCredit($credit_id)
    {
        $credit = Credit::with('compte')->findOrFail($credit_id);

        return view('credits.accorder', compact('credit'));
    }

    // Traiter l'approbation du crédit
    public function traiterApprobation(Request $request, $credit_id)
    {
        $request->validate([
            'action' => 'required|in:approuver,rejeter',
            'motif_rejet' => 'required_if:action,rejeter'
        ]);

        try {
            DB::beginTransaction();

            $credit = Credit::with('compte')->findOrFail($credit_id);
            $compte = $credit->compte;

            if ($request->action === 'approuver') {
                // Approuver le crédit
                $credit->update([
                    'statut_demande' => 'approuve',
                    'date_approbation' => now(),
                    'statut' => 'en_cours',
                    'motif_rejet' => null
                ]);

                // Augmenter le solde du compte
                $nouveauSolde = $compte->solde + $credit->montant_principal;
                $compte->update(['solde' => $nouveauSolde]);

                DB::commit();

                return redirect('/admin/comptes')
                    ->with('success', 'Crédit approuvé avec succès! Le solde du compte a été mis à jour.');

            } else {
                // Rejeter le crédit
                $credit->update([
                    'statut_demande' => 'rejete',
                    'date_approbation' => now(),
                    'motif_rejet' => $request->motif_rejet
                ]);

                DB::commit();

                return redirect('/admin/comptes')
                    ->with('info', 'Crédit rejeté avec succès.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    // Annuler une demande de crédit
    public function annulerDemande($credit_id)
    {
        try {
            $credit = Credit::findOrFail($credit_id);
            
            if (!$credit->estEnAttente()) {
                return redirect()->back()
                    ->with('error', 'Seules les demandes en attente peuvent être annulées.');
            }

            $credit->update([
                'statut_demande' => 'annule'
            ]);

            return redirect()->back()
                ->with('success', 'Demande de crédit annulée avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }
}