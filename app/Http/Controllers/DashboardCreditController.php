<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\Compte;
use App\Models\PaiementCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardCreditController extends Controller
{
    public function tableauDeBordComplet()
    {
        // PORTEFEUILLE TOTAL
        $portefeuilleTotal = [
            'credits_individuels_actifs' => Credit::where('statut_demande', 'approuve')->sum('montant_total'),
            'credits_groupes_actifs' => CreditGroupe::where('statut_demande', 'approuve')->sum('montant_total'),
            'montant_total_encours' => Credit::where('statut_demande', 'approuve')->sum('montant_total') + 
                                     CreditGroupe::where('statut_demande', 'approuve')->sum('montant_total'),
            
            'total_rembourse_ce_mois' => PaiementCredit::whereMonth('date_paiement', now()->month)
                ->whereYear('date_paiement', now()->year)
                ->sum('montant_paye'),
            
            'credits_en_attente' => Credit::where('statut_demande', 'en_attente')->count() + 
                                  CreditGroupe::where('statut_demande', 'en_attente')->count(),
            
            'credits_approuves_ce_mois' => Credit::where('statut_demande', 'approuve')
                ->whereMonth('date_octroi', now()->month)
                ->count() + 
                CreditGroupe::where('statut_demande', 'approuve')
                ->whereMonth('date_octroi', now()->month)
                ->count(),
        ];

        // TOUS LES CRÉDITS AVEC PAGINATION
        $creditsIndividuels = Credit::with(['compte.client', 'agent', 'superviseur'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'credits_individuels');

        $creditsGroupes = CreditGroupe::with(['compte', 'agent', 'superviseur', 'creditsIndividuels'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'credits_groupes');

        // SITUATION PAR COMPTE
        $situationComptes = Compte::with(['client', 'credits' => function($query) {
            $query->where('statut_demande', 'approuve');
        }, 'creditsGroupe' => function($query) {
            $query->where('statut_demande', 'approuve');
        }])
        ->whereHas('credits', function($query) {
            $query->where('statut_demande', 'approuve');
        })
        ->orWhereHas('creditsGroupe', function($query) {
            $query->where('statut_demande', 'approuve');
        })
        ->get()
        ->map(function($compte) {
            $totalCredits = $compte->credits->sum('montant_total') + $compte->creditsGroupe->sum('montant_total');
            $totalRembourse = $compte->credits->sum(function($credit) {
                return $credit->paiements->sum('montant_paye');
            }) + $compte->creditsGroupe->sum(function($creditGroupe) {
                return $creditGroupe->creditsIndividuels->sum(function($credit) {
                    return $credit->paiements->sum('montant_paye');
                });
            });
            
            return [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'type_compte' => $compte->type_compte,
                'nom' => $compte->nom . ' ' . $compte->prenom,
                'total_credits' => $totalCredits,
                'total_rembourse' => $totalRembourse,
                'reste_a_rembourser' => $totalCredits - $totalRembourse,
                'nombre_credits' => $compte->credits->count() + $compte->creditsGroupe->count(),
            ];
        });

        // PERFORMANCE DES AGENTS
        $performanceAgents = DB::table('users')
            ->whereIn('role', ['agent_credit', 'superviseur_credit'])
            ->leftJoin('credits', 'users.id', '=', 'credits.agent_id')
            ->leftJoin('credit_groupes', 'users.id', '=', 'credit_groupes.agent_id')
            ->select(
                'users.id',
                'users.name',
                'users.role',
                DB::raw('COUNT(DISTINCT credits.id) + COUNT(DISTINCT credit_groupes.id) as total_credits_geres'),
                DB::raw('COALESCE(SUM(credits.montant_total), 0) + COALESCE(SUM(credit_groupes.montant_total), 0) as montant_total_geres')
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->get();

        return view('dashboard.credits', compact(
            'portefeuilleTotal',
            'creditsIndividuels',
            'creditsGroupes',
            'situationComptes',
            'performanceAgents'
        ));
    }

    public function detailsCredit($id, $type = 'individuel')
    {
        if ($type === 'groupe') {
            $credit = CreditGroupe::with([
                'compte', 
                'agent', 
                'superviseur', 
                'creditsIndividuels.compte.client',
                'creditsIndividuels.paiements'
            ])->findOrFail($id);
        } else {
            $credit = Credit::with([
                'compte.client', 
                'agent', 
                'superviseur', 
                'paiements',
                'creditGroupe'
            ])->findOrFail($id);
        }

        return view('dashboard.details-credit', compact('credit', 'type'));
    }
}