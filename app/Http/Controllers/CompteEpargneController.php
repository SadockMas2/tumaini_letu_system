<?php

namespace App\Http\Controllers;

use App\Models\CompteEpargne;
use App\Models\Epargne;
use App\Models\Mouvement;
use App\Models\MouvementEpargne;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CompteEpargneController extends Controller
{

 

public function details($compte_epargne_id)
{
    $compte = CompteEpargne::with(['client', 'groupeSolidaire'])
        ->findOrFail($compte_epargne_id);

    // RÉCUPÉRER SEULEMENT LES ÉPARGNES AVEC LA MÊME DEVISE QUE LE COMPTE
    if ($compte->type_compte === 'individuel' && $compte->client_id) {
        $epargnes = Epargne::where('client_id', $compte->client_id)
            ->where('statut', 'valide')
            ->where('devise', $compte->devise)  // ← AJOUTER CE FILTRE
            ->with(['cycle'])
            ->orderBy('created_at', 'desc')
            ->get();
    } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
        $epargnes = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
            ->where('statut', 'valide')
            ->where('devise', $compte->devise)  // ← AJOUTER CE FILTRE
            ->with(['cycle'])
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        $epargnes = collect();
    }

    // Récupérer les retraits POUR CE COMPTE SPÉCIFIQUE
    $retraits = Mouvement::where('compte_epargne_id', $compte_epargne_id)
        ->where('type', 'retrait')
        ->with('operateur')
        ->orderBy('created_at', 'desc')
        ->get();

    // Convertir les épargnes en format commun AVEC INFO CYCLE
    $transactionsEpargnes = $epargnes->map(function($epargne) {
        return [
            'id' => $epargne->id,
            'type' => 'depot',
            'reference' => $epargne->reference ?? 'EPARG-' . $epargne->id,
            'description' => 'Épargne ' . ($epargne->type_epargne === 'groupe_solidaire' ? 'groupe' : 'individuelle'),
            'montant' => $epargne->montant,
            'devise' => $epargne->devise,
            'nom_deposant' => $epargne->agent_nom ?? null,
            'date_operation' => $epargne->created_at,
            'created_at' => $epargne->created_at,
            'source' => 'epargne_ancien',
            'operateur' => null,
            'agent_nom' => $epargne->agent_nom ?? 'Système',
            'cycle' => $epargne->cycle ? [
                'id' => $epargne->cycle->id,
                'numero_cycle' => $epargne->cycle->numero_cycle,
                'nom' => $epargne->cycle->nom ?? 'Cycle ' . $epargne->cycle->numero_cycle
            ] : null,
            'cycle_id' => $epargne->cycle_id
        ];
    });

    // Convertir les retraits en format commun
    $transactionsRetraits = $retraits->map(function($retrait) {
        return [
            'id' => $retrait->id,
            'type' => 'retrait',
            'reference' => $retrait->reference ?? 'RET-' . $retrait->id,
            'description' => $retrait->description ?? 'Retrait épargne',
            'montant' => $retrait->montant,
            'devise' => $retrait->devise,
            'nom_deposant' => $retrait->nom_deposant ?? null,
            'date_operation' => $retrait->created_at,
            'created_at' => $retrait->created_at,
            'source' => 'mouvement',
            'operateur' => $retrait->operateur,
            'agent_nom' => optional($retrait->operateur)->name ?? 'Système',
            'cycle' => null,
            'cycle_id' => null
        ];
    });

    // Fusionner et trier toutes les transactions
    $toutesTransactions = $transactionsEpargnes->merge($transactionsRetraits)
        ->sortByDesc('date_operation')
        ->values();

    // Pagination manuelle
    $perPage = 20;
    $currentPage = request()->get('page', 1);
    $paginatedTransactions = collect($toutesTransactions)->forPage($currentPage, $perPage);
    $mouvements = new \Illuminate\Pagination\LengthAwarePaginator(
        $paginatedTransactions,
        $toutesTransactions->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url()]
    );

    // Statistiques PAR CYCLE POUR CETTE DEVISE
    $statistiquesCycles = [];
    if ($compte->type_compte === 'individuel' && $compte->client_id) {
        $statistiquesCycles = Epargne::where('client_id', $compte->client_id)
            ->where('statut', 'valide')
            ->where('devise', $compte->devise)  // ← AJOUTER CE FILTRE
            ->with('cycle')
            ->get()
            ->groupBy('cycle_id')
            ->map(function($epargnesParCycle, $cycleId) {
                $cycle = $epargnesParCycle->first()->cycle;
                return [
                    'cycle_id' => $cycleId,
                    'numero_cycle' => $cycle ? $cycle->numero_cycle : 'N/A',
                    'nom_cycle' => $cycle ? ($cycle->nom ?? 'Cycle ' . $cycle->numero_cycle) : 'Cycle inconnu',
                    'nombre_epargnes' => $epargnesParCycle->count(),
                    'total_montant' => $epargnesParCycle->sum('montant'),
                    'devise' => $epargnesParCycle->first()->devise ?? 'USD'
                ];
            })->values();
    }

    // Statistiques générales POUR CETTE DEVISE
    $statsMouvements = [
        'total_depots' => $epargnes->sum('montant'),
        'total_retraits' => $retraits->sum('montant'),
        'nombre_depots' => $epargnes->count(),
        'nombre_retraits' => $retraits->count(),
    ];

    
   // Dans le contrôleur, avant de retourner la vue, ajoutez cette vérification
$mixedDevises = false;
if ($compte->type_compte === 'individuel' && $compte->client_id) {
    $nombreDevises = Epargne::where('client_id', $compte->client_id)
        ->where('statut', 'valide')
        ->distinct('devise')
        ->count('devise');
    
    $mixedDevises = $nombreDevises > 1;
}

return view('comptes-epargne.details', compact(
    'compte', 
    'statsMouvements', 
    'mouvements',
    'epargnes',
    'statistiquesCycles',
    'mixedDevises'  // ← Ajouter cette variable
));
}
// Mettez à jour aussi la méthode mouvements()
public function mouvements($compte_epargne_id)
{
    $compte = CompteEpargne::findOrFail($compte_epargne_id);
    
    $mouvements = MouvementEpargne::where('compte_epargne_id', $compte_epargne_id)
        ->orderBy('created_at', 'desc')
        ->paginate(50);

    return view('comptes-epargne.mouvements', compact('compte', 'mouvements'));
}

    public function exportReleve($compte_epargne_id)
    {
        $compte = CompteEpargne::with(['client', 'groupeSolidaire'])->findOrFail($compte_epargne_id);
        
        // Récupérer toutes les transactions
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            $epargnes = Epargne::where('client_id', $compte->client_id)
                ->where('devise', $compte->devise)  // ← AJOUTER
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
            $epargnes = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
                 ->where('devise', $compte->devise)  // ← AJOUTER
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $epargnes = collect();
        }

        $retraits = Mouvement::where('compte_epargne_id', $compte_epargne_id)
            ->where('type', 'retrait')
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = collect();
        
        foreach ($epargnes as $epargne) {
            $transactions->push([
                'date' => $epargne->date_apport?->format('d/m/Y') ?? $epargne->created_at->format('d/m/Y'),
                'type' => 'Épargne',
                'description' => $this->getEpargneDescription($epargne),
                'montant' => $epargne->montant,
                'devise' => $epargne->devise,
                'agent' => $epargne->agent_nom ?? 'Système'
            ]);
        }

        foreach ($retraits as $retrait) {
            $transactions->push([
                'date' => $retrait->date_mouvement?->format('d/m/Y') ?? $retrait->created_at->format('d/m/Y'),
                'type' => 'Retrait',
                'description' => $retrait->description,
                'montant' => -$retrait->montant, // Négatif pour les retraits
                'devise' => $retrait->devise,
                'agent' => $retrait->operateur->name ?? 'Système'
            ]);
        }

        $transactions = $transactions->sortByDesc(function($transaction) {
            return $transaction['date'];
        })->values();

        $pdf = Pdf::loadView('pdf.releve-compte-epargne', compact('compte', 'transactions'))
            ->setPaper('A4', 'portrait');

        return $pdf->download("releve-epargne-{$compte->numero_compte}-" . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Génère la description pour une épargne
     */
    private function getEpargneDescription(Epargne $epargne): string
    {
        $description = 'Épargne ';
        
        if ($epargne->type_epargne === 'groupe_solidaire') {
            $description .= 'groupe';
            if ($epargne->groupeSolidaire) {
                $description .= ' - ' . $epargne->groupeSolidaire->nom_groupe;
            }
        } else {
            $description .= 'individuelle';
            if ($epargne->client) {
                $description .= ' - ' . $epargne->client->nom_complet;
            }
        }

        if ($epargne->cycle) {
            $description .= ' - Cycle ' . $epargne->cycle->numero_cycle;
        }

        return $description;
    }
}