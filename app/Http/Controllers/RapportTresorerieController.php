<?php
// app/Http/Controllers/RapportTresorerieController.php

namespace App\Http\Controllers;

use App\Services\TresorerieService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RapportTresorerieController extends Controller
{
    protected $tresorerieService;

    public function __construct(TresorerieService $tresorerieService)
    {
        $this->tresorerieService = $tresorerieService;
    }

    /**
     * Générer le rapport détaillé en PDF
     */
    public function genererRapportPDF(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $rapport = $this->tresorerieService->genererRapportDetaillePDF($date);

        $pdf = PDF::loadView('pdf.rapport-tresorerie', compact('rapport'))
                  ->setPaper('A4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true
                  ]);

        return $pdf->download('rapport-tresorerie-' . $date . '.pdf');
    }

    /**
     * Aperçu du rapport dans le navigateur
     */
    public function apercuRapportPDF(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $rapport = $this->tresorerieService->genererRapportDetaillePDF($date);

        $pdf = PDF::loadView('pdf.rapport-tresorerie', compact('rapport'))
                  ->setPaper('A4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true
                  ]);

        return $pdf->stream('rapport-tresorerie-' . $date . '.pdf');
    }

    /**
     * Rapport synthétique JSON pour affichage
     */
    public function rapportSynthese(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $synthese = $this->tresorerieService->rapportSynthese($date);

        return response()->json($synthese);
    }

    /**
     * Données brutes pour génération manuelle de PDF
     */
    public function donneesRapport(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $rapport = $this->tresorerieService->genererRapportDetaillePDF($date);

        return response()->json($rapport);
    }
}