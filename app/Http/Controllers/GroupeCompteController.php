<?php

namespace App\Http\Controllers;

use App\Models\GroupeSolidaireCompte;
use Illuminate\Http\Request;

class GroupeCompteController extends Controller
{
    public function showDetails($groupe_compte_id)
    {
        $groupeCompte = GroupeSolidaireCompte::with(['membres.compte', 'creditsGroupes.creditsIndividuels'])->findOrFail($groupe_compte_id);
        
        return view('groupe-comptes.details', compact('groupeCompte'));
    }
}