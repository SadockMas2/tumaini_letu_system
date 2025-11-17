<?php
// app/Http/Controllers/EtatTresorerieController.php

namespace App\Http\Controllers;

use App\Services\TresorerieService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EtatTresorerieController extends Controller
{
    protected $tresorerieService;

    public function __construct(TresorerieService $tresorerieService)
    {
        $this->tresorerieService = $tresorerieService;
    }

    /**
     * État de sortie en temps réel
     */
    public function etatSortieTempsReel(Request $request)
    {
        $devise = $request->get('devise');
        $date = $request->get('date');

        $etat = $this->tresorerieService->genererEtatSortie($devise, $date);

        return response()->json($etat);
    }

    /**
     * État des grandes caisses vs comptabilité
     */
    public function etatGrandesCaissesComptabilite(Request $request)
    {
        $date = $request->get('date');

        $etat = $this->tresorerieService->etatGrandesCaissesComptabilite($date);

        return response()->json($etat);
    }

    /**
     * État de trésorerie temps réel complet
     */
    public function etatTresorerieTempsReel()
    {
        $etat = $this->tresorerieService->etatTresorerieTempsReel();

        return response()->json($etat);
    }

    /**
     * Export PDF de l'état de sortie
     */
    public function exportPdfEtatSortie(Request $request)
    {
        $devise = $request->get('devise');
        $date = $request->get('date');

        $etat = $this->tresorerieService->genererEtatSortie($devise, $date);

        // Ici vous pouvez utiliser DomPDF ou autre librairie PDF
        // return PDF::loadView('pdf.etat-sortie', compact('etat'))->download('etat-sortie.pdf');
        
        return response()->json([
            'message' => 'Fonction PDF à implémenter',
            'data' => $etat
        ]);
    }
}