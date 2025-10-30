<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Compte;
use App\Models\CreditGroupe;
use App\Models\Mouvement;
use App\Models\PaiementCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    // Afficher le formulaire de demande de crédit
    public function create($compte_id)
    {
        $compte = Compte::findOrFail($compte_id);
        return view('credits.create', compact('compte'));
    }

    // Traiter la demande de crédit
    public function store(Request $request)
    {
        Log::info('=== DÉBUT DEMANDE CRÉDIT ===');
        Log::info('Données reçues:', $request->all());
        
        $request->validate([
            'compte_id' => 'required|exists:comptes,id',
            'type_credit' => 'required|in:individuel,groupe',
            'montant_demande' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $compte = Compte::find($request->compte_id);
            Log::info('Compte trouvé:', $compte->toArray());
            
            $isCompteGroupe = str_starts_with($compte->numero_compte, 'GS');
            Log::info('Est compte groupe:', ['is_groupe' => $isCompteGroupe]);

            // Validation du type de crédit vs type de compte
            if ($request->type_credit === 'groupe' && !$isCompteGroupe) {
                Log::warning('Tentative de crédit groupe sur compte individuel');
                return back()->with('error', 'Les crédits groupe ne peuvent être demandés que par des comptes groupe.');
            }

            if ($request->type_credit === 'individuel' && $isCompteGroupe) {
                Log::warning('Tentative de crédit individuel sur compte groupe');
                return back()->with('error', 'Les crédits individuels ne peuvent être demandés que par des comptes individuels.');
            }

            if ($request->type_credit === 'groupe') {
                // Créer le crédit groupe
                $creditGroupe = CreditGroupe::create([
                    'compte_id' => $request->compte_id,
                    'montant_demande' => $request->montant_demande,
                    'date_demande' => now(),
                    'statut_demande' => 'en_attente',
                ]);

                DB::commit();
                Log::info('Crédit groupe créé:', $creditGroupe->toArray());

                return redirect()->route('credits.approval-groupe', $creditGroupe->id)
                    ->with('success', 'Demande de crédit groupe soumise avec succès!');

            } else {
                // Crédit individuel
                $credit = Credit::create([
                    'compte_id' => $request->compte_id,
                    'type_credit' => 'individuel',
                    'montant_demande' => $request->montant_demande,
                    'date_demande' => now(),
                    'statut_demande' => 'en_attente',
                ]);

                DB::commit();
                Log::info('Crédit individuel créé:', $credit->toArray());

                return redirect()->route('credits.approval', $credit->id)
                    ->with('success', 'Demande de crédit soumise avec succès!');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création crédit:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la soumission: ' . $e->getMessage());
        }
    }

    // Afficher le formulaire d'approbation pour crédit individuel
    public function showApproval($credit_id)
    {
        $credit = Credit::with('compte')->findOrFail($credit_id);
        
        // Calculer les détails du crédit
        $frais = Credit::calculerFraisIndividuel($credit->montant_demande);
        $montantTotal = Credit::calculerMontantTotalIndividuel($credit->montant_demande);
        $remboursementHebdo = Credit::calculerRemboursementHebdo($montantTotal, 'individuel');

        return view('credits.approval', compact('credit', 'frais', 'montantTotal', 'remboursementHebdo'));
    }

    // Traiter l'approbation du crédit individuel
// Traiter l'approbation du crédit individuel
// Traiter l'approbation du crédit individuel
public function processApproval(Request $request, $credit_id)
{
    Log::info('=== DÉBUT PROCESS APPROVAL INDIVIDUEL ===');
    
    $request->validate([
        'action' => 'required|in:approuver,rejeter',
        'montant_accorde' => 'required_if:action,approuver|numeric|min:0.01',
        'motif_rejet' => 'required_if:action,rejeter',
    ]);

    try {
        DB::beginTransaction();

        $credit = Credit::with('compte')->findOrFail($credit_id);
        
        if ($request->action === 'approuver') {
            Log::info('Traitement approbation individuel');
            
            // Calculer tous les frais et montants
            $frais = Credit::calculerFraisIndividuel($request->montant_accorde);
            $montantTotal = Credit::calculerMontantTotalIndividuel($request->montant_accorde);
            $remboursementHebdo = Credit::calculerRemboursementHebdo($montantTotal, 'individuel');

            // Mettre à jour le crédit
            $credit->update([
                'montant_accorde' => $request->montant_accorde,
                'type_mouvement' => 'credit_octroye',
                'montant_total' => $montantTotal,
                'frais_dossier' => $frais['dossier'],
                'frais_alerte' => $frais['alerte'],
                'frais_adhesion' => $frais['adhesion'],
                'caution' => $frais['caution'],
                'remboursement_hebdo' => $remboursementHebdo,
                'duree_mois' => 4,
                'statut_demande' => 'approuve',
                'date_octroi' => now(),
                'date_echeance' => now()->addMonths(4),
            ]);

            // ✅ CORRECTION : Mettre à jour le solde UNE SEULE FOIS
            $compte = $credit->compte;
            $ancienSolde = $compte->solde;
            
            // Augmenter le solde du montant accordé
            $compte->increment('solde', $request->montant_accorde);
            
            // Recharger le compte pour avoir le nouveau solde
            $compte->refresh();

            // ✅ CORRECTION : Créer le mouvement avec le NOUVEAU solde
            Mouvement::create([
                'compte_id' => $compte->id,
                'type_mouvement' => 'credit_octroye',
                'montant' => $request->montant_accorde,
                'solde_avant' => $ancienSolde,
                'solde_apres' => $compte->solde, // ✅ Utiliser le solde ACTUALISÉ
                'description' => "Octroi de crédit individuel - Montant: {$request->montant_accorde} USD",
                'reference' => 'CREDIT-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compte->nom . ' ' . $compte->prenom ?? 'Système',
            ]);

            Log::info('Crédit individuel approuvé avec succès');
            DB::commit();

            return redirect()->route('comptes.details', $credit->compte_id)
                ->with('success', 'Crédit approuvé avec succès!');

        } else {
            // ... code pour le rejet ...
        }

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur processApproval:', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(), 
            'line' => $e->getLine()
        ]);
        return back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
    }
}

    // Afficher le formulaire de paiement
    public function showPayment($compte_id)
    {
        $compte = Compte::with(['credits' => function($query) {
            $query->where('statut_demande', 'approuve')
                  ->where('montant_total', '>', 0);
        }])->findOrFail($compte_id);

        $credit = $compte->credits->first();

        if (!$credit) {
            return redirect()->route('comptes.details', $compte_id)
                ->with('error', 'Aucun crédit actif trouvé pour ce compte.');
        }

        return view('credits.payment', compact('compte', 'credit'));
    }

    // Traiter le paiement
    public function processPayment(Request $request, $credit_id)
    {
        $request->validate([
            'montant_paye' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $credit = Credit::with('compte')->findOrFail($credit_id);
            $compte = $credit->compte;
            
            if ($request->montant_paye > $credit->montant_total) {
                return back()->with('error', 'Le montant payé ne peut pas dépasser le montant total dû.');
            }

            // Vérifier si le solde est suffisant
            if ($compte->solde < $request->montant_paye) {
                return back()->with('error', 'Solde insuffisant pour effectuer ce paiement.');
            }

            $ancienSoldeCompte = $compte->solde;
            $ancienMontantCredit = $credit->montant_total;

            // Mettre à jour le montant total du crédit
            $credit->montant_total -= $request->montant_paye;
            $credit->save();

            // Débiter le compte
            $compte->solde -= $request->montant_paye;
            $compte->save();

            // Créer le paiement
            $paiement = PaiementCredit::create([
                'credit_id' => $credit->id,
                'compte_id' => $compte->id,
                'montant_paye' => $request->montant_paye,
                'date_paiement' => now(),
                'type_paiement' => 'especes',
                'reference' => 'PAY-' . time(),
            ]);

            // Créer le mouvement comptable
            Mouvement::create([
                'compte_id' => $compte->id,
                'type_mouvement' => 'paiement_credit',
                'montant' => -$request->montant_paye,
                'solde_avant' => $ancienSoldeCompte,
                'solde_apres' => $compte->solde,
                'description' => "Paiement crédit - Montant: {$request->montant_paye} USD - Restant: {$credit->montant_total} USD",
                'reference' => $paiement->reference,
                'date_mouvement' => now(),
                'nom_deposant' => $compte->client_nom ?? 'Système'
            ]);

            // Si le crédit est entièrement remboursé
            if ($credit->montant_total <= 0) {
                $credit->update(['statut_demande' => 'rembourse']);
                
                // Débloquer la caution si elle existe
                if ($credit->caution > 0) {
                    $compte->solde += $credit->caution;
                    $compte->save();
                    
                    Mouvement::create([
                        'compte_id' => $compte->id,
                        'type_mouvement' => 'deblocage_caution',
                        'montant' => $credit->caution,
                        'solde_avant' => $compte->solde - $credit->caution,
                        'solde_apres' => $compte->solde,
                        'description' => "Déblocage caution crédit - Montant: {$credit->caution} USD",
                        'reference' => 'CAUTION-' . $credit->id,
                        'date_mouvement' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('paiement.bordereau', $paiement->id)
                ->with('success', 'Paiement effectué avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur processPayment:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    // Afficher l'approbation pour crédit groupe
    public function showApprovalGroupe($credit_groupe_id)
    {
        try {
            Log::info('Chargement approbation groupe:', ['id' => $credit_groupe_id]);
            
            $credit = CreditGroupe::with('compte')->findOrFail($credit_groupe_id);
            $compte = $credit->compte;
            $membres = $credit->membres;
            
            return view('credits.approval-groupe-final', compact('credit', 'compte', 'membres'));
            
        } catch (\Exception $e) {
            Log::error('Erreur showApprovalGroupe:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    // Traiter l'approbation du crédit groupe - NOUVELLE LOGIQUE
  // Traiter l'approbation du crédit groupe - VERSION CORRIGÉE
public function processApprovalGroupe(Request $request, $credit_groupe_id)
{
    Log::info('🎯 === DÉBUT PROCESS APPROVAL GROUPE ===');
    Log::info('📥 Données reçues:', $request->all());

    $request->validate([
        'action' => 'required|in:approuver,rejeter',
        'montant_total_groupe' => 'required_if:action,approuver|numeric|min:0.01',
        'montants_membres' => 'required_if:action,approuver|array',
        'montants_membres.*' => 'numeric|min:0',
        'motif_rejet' => 'required_if:action,rejeter',
    ]);

    try {
        DB::beginTransaction();

        $credit = CreditGroupe::with('compte')->findOrFail($credit_groupe_id);
        
        if ($request->action === 'approuver') {
            Log::info('🟢 APPROBATION GROUPE');
            
            // VALIDATION
            $totalMontantsMembres = array_sum($request->montants_membres);
            $montantTotalGroupe = floatval($request->montant_total_groupe);
            
            if (abs($totalMontantsMembres - $montantTotalGroupe) > 0.01) {
                throw new \Exception("La répartition n'est pas équilibrée. Total membres: {$totalMontantsMembres}, Total groupe: {$montantTotalGroupe}");
            }

            // CALCUL DES FRAIS
            $repartitionDetaillee = [];
            $totalCautions = 0;

            foreach ($request->montants_membres as $membreId => $montantMembre) {
                $montantMembre = floatval($montantMembre);
                if ($montantMembre > 0) {
                    $cautionMembre = $montantMembre * 0.20;
                    $fraisMembre = Credit::calculerFraisGroupe($montantMembre);
                    $montantTotalMembre = Credit::calculerMontantTotalGroupe($montantMembre);
                    $remboursementHebdoMembre = Credit::calculerRemboursementHebdo($montantTotalMembre, 'groupe');
                    
                    $repartitionDetaillee[$membreId] = [
                        'montant_accorde' => $montantMembre,
                        'frais_dossier' => $fraisMembre['dossier'],
                        'frais_alerte' => $fraisMembre['alerte'],
                        'frais_carnet' => $fraisMembre['carnet'],
                        'frais_adhesion' => $fraisMembre['adhesion'],
                        'caution' => $cautionMembre,
                        'montant_total' => $montantTotalMembre,
                        'remboursement_hebdo' => $remboursementHebdoMembre,
                    ];
                    
                    $totalCautions += $cautionMembre;
                }
            }

            // MISE À JOUR CRÉDIT GROUPE
            $montantTotalAvecInteret = $montantTotalGroupe * 1.225;
            $remboursementHebdoTotal = $montantTotalAvecInteret / 16;

            $credit->update([
                'montant_accorde' => $montantTotalGroupe,
                'montant_total' => $montantTotalAvecInteret,
                'frais_dossier' => 0,
                'frais_alerte' => 0,
                'frais_carnet' => 0,
                'frais_adhesion' => 0,
                'caution_totale' => $totalCautions,
                'remboursement_hebdo_total' => $remboursementHebdoTotal,
                'repartition_membres' => $repartitionDetaillee,
                'montants_membres' => $request->montants_membres,
                'statut_demande' => 'approuve',
                'date_octroi' => now(),
                'date_echeance' => now()->addMonths(4),
            ]);

            Log::info('✅ Crédit groupe mis à jour');

            // CRÉDITER LES COMPTES MEMBRES
            foreach ($repartitionDetaillee as $membreId => $details) {
                $montantMembre = $details['montant_accorde'];
                
                $compteMembre = DB::table('comptes')->where('client_id', $membreId)->first();
                if (!$compteMembre) {
                    Log::error("❌ Compte non trouvé pour membre ID: {$membreId}");
                    continue;
                }

                // Créditer le compte
                $ancienSoldeMembre = $compteMembre->solde;
                DB::table('comptes')
                    ->where('id', $compteMembre->id)
                    ->increment('solde', $montantMembre);
                
                $nouveauSoldeMembre = DB::table('comptes')->where('id', $compteMembre->id)->value('solde');

                // Mouvement pour le membre
                Mouvement::create([
                    'compte_id' => $compteMembre->id,
                    'type_mouvement' => 'credit_octroye_groupe',
                    'montant' => $montantMembre,
                    'solde_avant' => $ancienSoldeMembre,
                    'solde_apres' => $nouveauSoldeMembre,
                    'description' => "Octroi crédit groupe - Montant: {$montantMembre} USD",
                    'reference' => 'CREDIT-GROUPE-' . $credit->id,
                    'date_mouvement' => now(),
                    'nom_deposant' => $compteMembre->nom . ' ' . $compteMembre->prenom ?? 'Membre',
                ]);

                Log::info("💰 Compte membre {$compteMembre->numero_compte} crédité: +{$montantMembre} USD");
            }

            // MOUVEMENT POUR LE GROUPE (IMPORTANT: avec nom_deposant)
            $compteGroupe = $credit->compte;
            Mouvement::create([
                'compte_id' => $compteGroupe->id,
                'type_mouvement' => 'credit_groupe_octroye',
                'montant' => 0,
                'solde_avant' => $compteGroupe->solde,
                'solde_apres' => $compteGroupe->solde,
                'description' => "Crédit groupe octroyé - Montant total: {$montantTotalGroupe} USD",
                'reference' => 'CREDIT-GROUPE-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compteGroupe->nom ?? 'Système', // LIGNE CRITIQUE
            ]);

            // CRÉER CRÉDITS INDIVIDUELS ET ÉCHÉANCIERS
            $credit->creerCreditsIndividuelsAvecCaution();
            $credit->creerEcheanciersMembres();

            DB::commit();
            Log::info('🎉 APPROBATION GROUPE TERMINÉE AVEC SUCCÈS');

           return redirect()->route('comptes.details', $credit->compte_id)
                ->with('success', 'Crédit groupe accordé avec succès!');

        } else {
            // REJET
            $credit->update([
                'statut_demande' => 'rejete',
                'motif_rejet' => $request->motif_rejet,
            ]);

            DB::commit();
            return redirect()->route('comptes.details', $credit->compte_id)
                ->with('info', 'Demande de crédit groupe rejetée.');
        }

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('💥 ERREUR APPROBATION GROUPE:', ['error' => $e->getMessage()]);
        return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
    }
}

    // Afficher les détails du crédit groupe après approbation
   public function showDetailsGroupe($id)
{
    try {
        $credit = CreditGroupe::with('compte')->findOrFail($id);
        
        if ($credit->statut_demande !== 'approuve') {
            return redirect()->back()->with('error', 'Ce crédit groupe n\'a pas encore été approuvé.');
        }

        $etat = $credit->genererEtatRepartition();
        $compte = $credit->compte; // Récupérer le compte depuis le crédit
        
        return view('credits.details-groupe', compact('credit', 'etat', 'compte'));
        
    } catch (\Exception $e) {
        Log::error('Erreur showDetailsGroupe: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Erreur lors du chargement des détails: ' . $e->getMessage());
    }
}

    // Afficher les échéanciers du groupe
   public function showEcheanciersGroupe($id)
{
    try {
        $credit = CreditGroupe::with('compte')->findOrFail($id);
        
        // Récupérer les échéanciers avec les informations des membres
        $echeanciers = DB::table('echeanciers')
            ->where('credit_groupe_id', $id)
            ->join('comptes', 'echeanciers.compte_id', '=', 'comptes.id')
            ->join('clients', 'comptes.client_id', '=', 'clients.id')
            ->select('echeanciers.*', 'clients.nom', 'clients.prenom', 'comptes.numero_compte')
            ->orderBy('comptes.id')
            ->orderBy('echeanciers.semaine')
            ->get()
            ->groupBy('compte_id');

        return view('credits.echeanciers-groupe', compact('credit', 'echeanciers'));
        
    } catch (\Exception $e) {
        Log::error('Erreur showEcheanciersGroupe: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Erreur lors du chargement des échéanciers.');
    }
}

    // Afficher l'échéancier d'un membre spécifique
    public function showEcheancierMembre($id, $membre_id)
    {
        try {
            $credit = CreditGroupe::findOrFail($id);
            $compteMembre = Compte::where('client_id', $membre_id)->firstOrFail();
            
            $echeanciers = DB::table('echeanciers')
                ->where('credit_groupe_id', $id)
                ->where('compte_id', $compteMembre->id)
                ->orderBy('semaine')
                ->get();
            
            $creditIndividuel = Credit::where('credit_groupe_id', $id)
                ->where('compte_id', $compteMembre->id)
                ->first();
            
            return view('credits.echeancier-membre', compact('credit', 'compteMembre', 'echeanciers', 'creditIndividuel'));
            
        } catch (\Exception $e) {
            Log::error('Erreur showEcheancierMembre: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors du chargement de l\'échéancier: ' . $e->getMessage());
        }
    }

    // Générer bordereau de paiement
    public function generateBordereauPDF($paiement_id)
    {
        try {
            $paiement = PaiementCredit::with(['credit', 'compte'])->findOrFail($paiement_id);
            
            // Vous pouvez utiliser DomPDF ou une autre librairie PDF ici
            // Pour l'instant, on retourne une vue
            return view('paiements.bordereau-pdf', compact('paiement'));
            
        } catch (\Exception $e) {
            Log::error('Erreur generateBordereauPDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la génération du bordereau.');
        }
    }

    // Ajoutez cette méthode temporaire dans CreditController
public function testApprovalGroupe($credit_groupe_id)
{
    try {
        Log::info('=== TEST APPROBATION GROUPE ===');
        
        $credit = CreditGroupe::with('compte')->findOrFail($credit_groupe_id);
        $compte = $credit->compte;
        $membres = $credit->membres;
        
        Log::info('Données crédit:', $credit->toArray());
        Log::info('Données compte:', $compte->toArray());
        Log::info('Membres trouvés:', $membres->toArray());
        
        return response()->json([
            'success' => true,
            'credit' => $credit,
            'compte' => $compte,
            'membres' => $membres,
            'count_membres' => $membres->count()
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erreur test:', ['error' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function showEcheancier($credit_id)
{
    $credit = Credit::with('compte')->findOrFail($credit_id);
    
    if ($credit->statut_demande !== 'approuve') {
        return redirect()->back()->with('error', 'Seuls les crédits approuvés peuvent avoir un échéancier.');
    }

    $compte = $credit->compte;
    
    return view('credits.echeancier', compact('credit', 'compte'));
}
}