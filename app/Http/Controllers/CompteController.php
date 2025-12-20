<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Credit;
use App\Models\Mouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    // Récupérer tous les mouvements pour les statistiques
    $tousMouvements = Mouvement::where('compte_id', $compte_id)
        ->orWhere('numero_compte', $compte->numero_compte)
        ->get();

    // Calculer les statistiques avec la logique correcte
    $totalDepots = 0;
    $totalRetraits = 0;
    $nombreDepots = 0;
    $nombreRetraits = 0;

    foreach ($tousMouvements as $mouvement) {
        $typeAffichage = \App\Helpers\MouvementHelper::getTypeAffichage($mouvement->type_mouvement);
        $montant = floatval($mouvement->montant);
        
        // Ignorer les mouvements neutres (comme caution_bloquee avec montant 0)
        if ($typeAffichage === 'neutre' && $montant == 0) {
            continue;
        }
        
        if ($typeAffichage === 'depot') {
            $totalDepots += abs($montant);
            $nombreDepots++;
        } elseif ($typeAffichage === 'retrait') {
            $totalRetraits += abs($montant);
            $nombreRetraits++;
        }
        // Les types 'autre' ne sont pas comptés dans les statistiques
    }

    $statsMouvements = [
        'total_depots' => $totalDepots,
        'total_retraits' => $totalRetraits,
        'nombre_depots' => $nombreDepots,
        'nombre_retraits' => $nombreRetraits,
    ];

    // Récupérer les mouvements avec pagination pour l'affichage
    $mouvements = Mouvement::where('compte_id', $compte_id)
        ->orWhere('numero_compte', $compte->numero_compte)
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return view('comptes.details', compact('compte', 'mouvements', 'statsMouvements'));
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