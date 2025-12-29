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


    // Dans App\Http\Controllers\CompteEpargneController

// Dans App\Http\Controllers\CompteEpargneController
public function rapportEpargne()
{
    // Récupérer tous les comptes épargne avec leurs relations
    $comptes = CompteEpargne::with([
        'client',
        'groupeSolidaire'
    ])
    ->orderBy('numero_compte')
    ->get();

    // Calculer les totaux par devise depuis la création
    $totaux = [
        'usd' => [
            'solde_total' => 0,
            'nombre_comptes' => 0,
            'depots_total' => 0,
            'retraits_total' => 0,
            'comptes_actifs' => 0
        ],
        'cdf' => [
            'solde_total' => 0,
            'nombre_comptes' => 0,
            'depots_total' => 0,
            'retraits_total' => 0,
            'comptes_actifs' => 0
        ]
    ];

    // Calculer les statistiques pour chaque compte
    foreach ($comptes as $compte) {
        $devise = strtolower($compte->devise);
        
        // Totaux par devise
        $totaux[$devise]['solde_total'] += floatval($compte->solde);
        $totaux[$devise]['nombre_comptes']++;
        
        if ($compte->statut === 'actif') {
            $totaux[$devise]['comptes_actifs']++;
        }
        
        // Dépôts totaux depuis la création
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            $depotsTotal = Epargne::where('client_id', $compte->client_id)
                ->where('statut', 'valide')
                ->where('devise', $compte->devise)
                ->sum('montant');
        } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
            $depotsTotal = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
                ->where('statut', 'valide')
                ->where('devise', $compte->devise)
                ->sum('montant');
        }
        
        $totaux[$devise]['depots_total'] += $depotsTotal;
        
        // Retraits totaux depuis la création
        $retraitsTotal = Mouvement::where('compte_epargne_id', $compte->id)
            ->where('type', 'retrait')
            ->sum('montant');
            
        $totaux[$devise]['retraits_total'] += $retraitsTotal;
        
        // Ajouter les dépôts et retraits totaux au modèle pour la vue
        $compte->depots_total = $depotsTotal ?? 0;
        $compte->retraits_total = $retraitsTotal ?? 0;
    }

    // Préparer les données du rapport
    $rapport = [
        'date_rapport' => now()->format('d/m/Y'),
        'heure_generation' => now()->format('H:i:s'),
        'nombre_total_comptes' => $comptes->count(),
        'comptes' => $comptes,
        'totaux' => $totaux,
        'logo_base64' => $this->getLogoBase64()
    ];

    return view('rapports.epargne', compact('rapport'));
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
                TUMAINI LETU ÉPARGNE
            </text>
        </svg>
    ');
}

// Optionnel : Export PDF
public function rapportEpargnePDF()
{
    $rapport = $this->prepareRapportData(); // Vous pouvez réutiliser la logique ci-dessus
    
    $pdf = Pdf::loadView('rapports.epargne-pdf', compact('rapport'));
    
    return $pdf->download('rapport-epargne-' . now()->format('Y-m-d') . '.pdf');
}
}