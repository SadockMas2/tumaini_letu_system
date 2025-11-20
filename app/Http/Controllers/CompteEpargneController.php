<?php

namespace App\Http\Controllers;

use App\Models\CompteEpargne;
use App\Models\Epargne;
use App\Models\Mouvement;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CompteEpargneController extends Controller
{
    public function details($compte_epargne_id)
    {
        $compte = CompteEpargne::with(['client', 'groupeSolidaire'])
            ->findOrFail($compte_epargne_id);

        // Récupérer les épargnes selon le type de compte
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            // Pour les comptes individuels : chercher par client_id
            $epargnes = Epargne::where('client_id', $compte->client_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
            // Pour les comptes groupe : chercher par groupe_solidaire_id
            $epargnes = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $epargnes = collect();
        }

        // Récupérer les retraits depuis les mouvements
        $retraits = Mouvement::where('compte_epargne_id', $compte_epargne_id)
            ->where('type', 'retrait')
            ->orderBy('created_at', 'desc')
            ->get();

        // Combiner et trier toutes les transactions
        $transactions = collect();
        
        // Ajouter les épargnes comme dépôts
        foreach ($epargnes as $epargne) {
            $transactions->push([
                'id' => $epargne->id,
                'type' => 'depot',
                'type_mouvement' => 'epargne',
                'montant' => $epargne->montant,
                'description' => $this->getEpargneDescription($epargne),
                'nom_deposant' => $epargne->agent_nom ?? 'Système',
                'devise' => $epargne->devise,
                'created_at' => $epargne->created_at,
                'date_operation' => $epargne->date_apport,
                'solde_avant' => 0,
                'solde_apres' => 0,
                'operateur' => $epargne->user,
                'reference' => 'EPARGNE-' . $epargne->id
            ]);
        }

        // Ajouter les retraits
        foreach ($retraits as $retrait) {
            $transactions->push([
                'id' => $retrait->id,
                'type' => 'retrait',
                'type_mouvement' => $retrait->type_mouvement,
                'montant' => $retrait->montant,
                'description' => $retrait->description,
                'nom_deposant' => $retrait->nom_deposant,
                'devise' => $retrait->devise,
                'created_at' => $retrait->created_at,
                'date_operation' => $retrait->date_mouvement,
                'solde_avant' => $retrait->solde_avant,
                'solde_apres' => $retrait->solde_apres,
                'operateur' => $retrait->operateur,
                'reference' => $retrait->reference
            ]);
        }

        // Trier par date décroissante
        $transactions = $transactions->sortByDesc(function($transaction) {
            return $transaction['date_operation'] ?? $transaction['created_at'];
        })->values();

        // Statistiques
        $statsMouvements = [
            'total_depots' => $epargnes->sum('montant'),
            'total_retraits' => $retraits->sum('montant'),
            'nombre_depots' => $epargnes->count(),
            'nombre_retraits' => $retraits->count(),
        ];

        // Pagination manuelle
        $page = request()->get('page', 1);
        $perPage = 20;
        $paginatedTransactions = $transactions->slice(($page - 1) * $perPage, $perPage)->all();
        $mouvements = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedTransactions,
            $transactions->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('comptes-epargne.details', compact('compte', 'statsMouvements', 'mouvements'));
    }

    public function mouvements($compte_epargne_id)
    {
        $compte = CompteEpargne::findOrFail($compte_epargne_id);
        
        // Même logique que dans details() mais avec plus d'éléments par page
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            $epargnes = Epargne::where('client_id', $compte->client_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
            $epargnes = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
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
                'id' => $epargne->id,
                'type' => 'depot',
                'type_mouvement' => 'epargne',
                'montant' => $epargne->montant,
                'description' => $this->getEpargneDescription($epargne),
                'nom_deposant' => $epargne->agent_nom ?? 'Système',
                'devise' => $epargne->devise,
                'created_at' => $epargne->created_at,
                'date_operation' => $epargne->date_apport,
                'solde_avant' => 0,
                'solde_apres' => 0,
                'operateur' => $epargne->user,
                'reference' => 'EPARGNE-' . $epargne->id
            ]);
        }

        foreach ($retraits as $retrait) {
            $transactions->push([
                'id' => $retrait->id,
                'type' => 'retrait',
                'type_mouvement' => $retrait->type_mouvement,
                'montant' => $retrait->montant,
                'description' => $retrait->description,
                'nom_deposant' => $retrait->nom_deposant,
                'devise' => $retrait->devise,
                'created_at' => $retrait->created_at,
                'date_operation' => $retrait->date_mouvement,
                'solde_avant' => $retrait->solde_avant,
                'solde_apres' => $retrait->solde_apres,
                'operateur' => $retrait->operateur,
                'reference' => $retrait->reference
            ]);
        }

        $transactions = $transactions->sortByDesc(function($transaction) {
            return $transaction['date_operation'] ?? $transaction['created_at'];
        })->values();

        $page = request()->get('page', 1);
        $perPage = 50;
        $paginatedTransactions = $transactions->slice(($page - 1) * $perPage, $perPage)->all();
        $mouvements = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedTransactions,
            $transactions->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('comptes-epargne.mouvements', compact('compte', 'mouvements'));
    }

    public function exportReleve($compte_epargne_id)
    {
        $compte = CompteEpargne::with(['client', 'groupeSolidaire'])->findOrFail($compte_epargne_id);
        
        // Récupérer toutes les transactions
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            $epargnes = Epargne::where('client_id', $compte->client_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($compte->type_compte === 'groupe_solidaire' && $compte->groupe_solidaire_id) {
            $epargnes = Epargne::where('groupe_solidaire_id', $compte->groupe_solidaire_id)
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