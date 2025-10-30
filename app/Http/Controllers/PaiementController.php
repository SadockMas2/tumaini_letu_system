<?php

namespace App\Http\Controllers;

use App\Models\PaiementCredit;
use App\Models\Credit;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class PaiementController extends Controller
{
    /**
     * Afficher l'historique des paiements d'un crédit
     */
   // Afficher le bordereau de paiement
public function bordereau($paiement_id)
{
    $paiement = PaiementCredit::with(['credit.compte'])->findOrFail($paiement_id);
    
    return view('paiements.bordereau', compact('paiement'));
}

// Afficher l'historique des paiements
public function historiqueCredit($credit_id)
{
    $credit = Credit::with(['paiements', 'compte'])->findOrFail($credit_id);
    $paiements = $credit->paiements()->orderBy('date_paiement', 'desc')->get();
    
    return view('paiements.historique', compact('credit', 'paiements'));
}

// Générer le PDF du bordereau
public function generateBordereauPDF($paiement_id)
{
    $paiement = PaiementCredit::with(['credit.compte'])->findOrFail($paiement_id);
    
    $pdf = PDF::loadView('paiements.bordereau-pdf', compact('paiement'));
    
    return $pdf->download('bordereau-paiement-' . $paiement->reference . '.pdf');
}

    
}