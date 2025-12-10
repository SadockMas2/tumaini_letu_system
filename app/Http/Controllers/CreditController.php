<?php

namespace App\Http\Controllers;

use App\Models\CompteSpecial;
use App\Models\Credit;
use App\Models\Compte;
use App\Models\CreditGroupe;
use App\Models\EcritureComptable;
use App\Models\HistoriqueCompteSpecial;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Models\PaiementCredit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    
    // Récupérer les agents (rôle ConseillerMembres)
    $agents = User::whereHas('roles', function ($query) {
        $query->where('name', 'ConseillerMembres');
    })->get();
    
    // Récupérer les superviseurs (rôle ChefBureau)
    $superviseurs = User::whereHas('roles', function ($query) {
        $query->where('name', 'ChefBureau');
    })->get();
    
    // Calculer les détails du crédit
    $frais = Credit::calculerFraisIndividuel($credit->montant_demande);
    $montantTotal = Credit::calculerMontantTotalIndividuel($credit->montant_demande);
    $remboursementHebdo = Credit::calculerRemboursementHebdo($montantTotal, 'individuel');

    return view('credits.approval', [
        'credit' => $credit,
        'frais' => $frais,
        'montantTotal' => $montantTotal,
        'remboursementHebdo' => $remboursementHebdo,
        'agents' => $agents,
        'superviseurs' => $superviseurs,
    ]);
}



public function processApproval(Request $request, $credit_id)
{
    Log::info('=== DÉBUT PROCESS APPROVAL INDIVIDUEL ===');
    
    $request->validate([
        'action' => 'required|in:approuver,rejeter',
         'agent_id' => 'required|exists:users,id',          // ✅ Nouveau
        'superviseur_id' => 'required|exists:users,id',  
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

            // Calculer le total des frais à payer (sans la caution)
            $totalFrais = $frais['dossier'] + $frais['alerte'];
            
            // Vérifier si le solde est suffisant pour couvrir les frais
            $compte = $credit->compte;
            $soldeDebut = $compte->solde;
            
            if ($soldeDebut < $totalFrais) {
                throw new \Exception("Solde insuffisant pour payer les frais. Solde actuel: {$soldeDebut} {$compte->devise}, Frais à payer: {$totalFrais} {$compte->devise}");
            }

            Log::info("📊 CALCULS - Solde début: {$soldeDebut}, Frais: {$totalFrais}, Crédit: {$request->montant_accorde}, Caution: {$frais['caution']}");

            // 1. RETRANCHER LES FRAIS DU SOLDE DU CLIENT
            $soldeApresFrais = $soldeDebut - $totalFrais;
            $compte->solde = $soldeApresFrais;
            $compte->save();

            // 2. CRÉER LE MOUVEMENT "FRAIS PAYÉS" POUR LE CLIENT
            Mouvement::create([
                'compte_id' => $compte->id,
                'type_mouvement' => 'frais_payes_credit',
                'montant' => -$totalFrais,
                'solde_avant' => $soldeDebut,
                'solde_apres' => $soldeApresFrais,
                'description' => "Paiement frais pour octroi crédit - Dossier: {$frais['dossier']}, Alerte: {$frais['alerte']}",
                'reference' => 'FRAIS-CREDIT-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compte->nom . ' ' . $compte->prenom ?? 'Système',
            ]);

            Log::info("💰 FRAIS DÉDUITS - Solde après frais: {$soldeApresFrais}");

            // 3. TRANSFÉRER LES FRAIS VERS LE COMPTE SPÉCIAL (CORRECTION)
            $compteSpecial = $this->transfererFraisVersCompteSpecial($totalFrais, $compte->devise, $credit);

            // ✅ CORRECTION : CRÉDITER EFFECTIVEMENT LE COMPTE SPÉCIAL (comme pour le groupe)
            $ancienSoldeSpecial = $compteSpecial->solde;
            $compteSpecial->solde += $totalFrais;
            $compteSpecial->save();

            Log::info("💰 COMPTE SPÉCIAL CRÉDITÉ - Ancien solde: {$ancienSoldeSpecial} USD, Nouveau solde: {$compteSpecial->solde} USD");

            // 4. CRÉER L'HISTORIQUE DANS LE COMPTE SPÉCIAL
            $this->creerHistoriqueCompteSpecial($totalFrais, $compte->devise, $credit, $compte);

            // 5. METTRE À JOUR LE CRÉDIT
            $credit->update([
                'montant_accorde' => $request->montant_accorde,
                'type_mouvement' => 'credit_octroye',
                'montant_total' => $montantTotal,
                'frais_dossier' => $frais['dossier'],
                'frais_alerte' => $frais['alerte'],
                'caution' => $frais['caution'],
                'remboursement_hebdo' => $remboursementHebdo,
                'agent_id' => $request->agent_id,          // ✅ Nouveau
                'superviseur_id' => $request->superviseur_id, // ✅ Nouveau
                'duree_mois' => 4,
                'statut_demande' => 'approuve',
                'date_octroi' => now(),
                'date_echeance' => now()->addMonths(4),
            ]);

            // 6. CRÉDITER LE MONTANT ACCORDÉ AU COMPTE
            $soldeApresCredit = $soldeApresFrais + $request->montant_accorde;
            $compte->solde = $soldeApresCredit;
            $compte->save();

            Log::info("💳 CRÉDIT AJOUTÉ - Solde après crédit: {$soldeApresCredit}");

            // 7. BLOQUER LA CAUTION DANS LE COMPTE
            $caution = $frais['caution'];
            if ($caution > 0) {
                DB::table('cautions')->insert([
                    'compte_id' => $compte->id,
                    'credit_id' => $credit->id,
                    'montant' => $caution,
                    'statut' => 'bloquee',
                    'date_blocage' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("🔒 CAUTION BLOQUÉE - Montant: {$caution} USD pour crédit individuel #{$credit->id}");
            }

            // ✅ CORRECTION : GÉNÉRER LES ÉCRITURES COMPTABLES POUR LE CRÉDIT INDIVIDUEL
            $this->genererEcrituresComptablesCreditIndividuel(
                $credit, 
                $compte, 
                $frais, 
                $montantTotal
            );

            Log::info('✅ Crédit individuel approuvé avec succès - Frais transférés - Caution bloquée - Écritures comptables créées');
            Log::info("📈 RÉCAPITULATIF - Début: {$soldeDebut}, Après frais: {$soldeApresFrais}, Final: {$soldeApresCredit}, Caution bloquée: {$caution}");

            DB::commit();

            return redirect()->route('comptes.details', $credit->compte_id)
                ->with('success', 'Crédit approuvé avec succès! Les frais ont été prélevés et la caution est bloquée.');

        } else {
            // REJET
            $credit->update([
                'statut_demande' => 'rejete',
                'motif_rejet' => $request->motif_rejet,
            ]);

            DB::commit();
            return redirect()->route('comptes.details', $credit->compte_id)
                ->with('info', 'Demande de crédit rejetée.');
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


/**
 * Transfère les frais vers le compte spécial selon la devise - VERSION CORRIGÉE
 */
/**
 * Transfère les frais vers le compte spécial selon la devise - VERSION SIMPLIFIÉE
 */
private function transfererFraisVersCompteSpecial($montantFrais, $devise, $credit)
{
    // Trouver ou créer le compte spécial pour cette devise
    $compteSpecial = CompteSpecial::where('devise', $devise)->first();
    
    if (!$compteSpecial) {
        // Créer un nouveau compte spécial pour cette devise
        $compteSpecial = CompteSpecial::create([
            'nom' => "Compte Frais Crédit - {$devise}",
            'solde' => 0,
            'devise' => $devise
        ]);
    }
    
    Log::info("📊 Compte spécial trouvé/créé: {$compteSpecial->nom}, Solde: {$compteSpecial->solde} {$devise}");
    
    return $compteSpecial;
}

    
    
/**
 * Crée l'historique dans le compte spécial - VERSION CORRIGÉE
 */
/**
 * Crée l'historique dans le compte spécial - VERSION SIMPLIFIÉE
 */
private function creerHistoriqueCompteSpecial($montantFrais, $devise, $credit, $compteClient)
{
    $nomClient = trim($compteClient->nom . ' ' . ($compteClient->postnom ?? '') . ' ' . ($compteClient->prenom ?? ''));
    
    // CRÉER L'HISTORIQUE POUR TRACABILITÉ
    HistoriqueCompteSpecial::create([
        'client_nom' => $nomClient,
        'montant' => $montantFrais,
        'devise' => $devise,
        'description' => "Frais crédit  payés - Crédit #{$credit->id} - membre/Groupe: {$nomClient}",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    Log::info("📝 Historique créé pour compte spécial: {$montantFrais} {$devise}");
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
                // Pour les crédits individuels
                DB::table('cautions')
                    ->where('compte_id', $compte->id)
                    ->where('credit_id', $credit->id)
                    ->where('statut', 'bloquee')
                    ->update([
                        'statut' => 'debloquee',
                        'date_deblocage' => now(),
                        'updated_at' => now()
                    ]);

                // La caution reste dans le compte (elle était déjà déduite au départ)
                // On ne fait pas de mouvement supplémentaire car l'argent était déjà dans le compte
                // mais simplement "bloqué" pour les retraits

                Log::info("🔓 CAUTION DÉBLOQUÉE - Crédit individuel #{$credit->id}, Montant: {$credit->caution} USD");
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
   // Afficher l'approbation pour crédit groupe - VERSION CORRIGÉE
// Afficher le formulaire d'approbation pour crédit groupe - VERSION FINALE
public function showApprovalGroupe($credit_groupe_id)
{
    try {
        Log::info('Chargement approbation groupe:', ['id' => $credit_groupe_id]);
        
        // Récupérer le crédit groupe avec son compte
        $credit = CreditGroupe::with('compte')->findOrFail($credit_groupe_id);
        $compte = $credit->compte;
        
        // Récupérer les membres du groupe
        $membres = DB::table('groupes_membres')
            ->join('clients', 'groupes_membres.client_id', '=', 'clients.id')
            ->join('comptes', 'clients.id', '=', 'comptes.client_id')
            ->where('groupes_membres.groupe_solidaire_id', $compte->groupe_solidaire_id)
            ->select('clients.id', 'clients.nom', 'clients.prenom', 'comptes.numero_compte', 'comptes.solde')
            ->get();
        
        // ✅ Récupérer les agents (rôle ConseillerMembres)
        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'ConseillerMembres');
        })->get();
        
        // ✅ Récupérer les superviseurs (rôle ChefBureau)
        $superviseurs = User::whereHas('roles', function ($query) {
            $query->where('name', 'ChefBureau');
        })->get();
        
        Log::info('Données chargées:', [
            'credit_id' => $credit->id,
            'compte_id' => $compte->id,
            'membres_count' => $membres->count(),
            'agents_count' => $agents->count(),
            'superviseurs_count' => $superviseurs->count()
        ]);
        
        return view('credits.approval-groupe-final', [
            'credit' => $credit,
            'compte' => $compte,
            'membres' => $membres,
            'agents' => $agents,              // ✅ Passer aux vues
            'superviseurs' => $superviseurs,  // ✅ Passer aux vues
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erreur showApprovalGroupe:', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return redirect()->back()
            ->with('error', 'Erreur lors du chargement du formulaire d\'approbation: ' . $e->getMessage());
    }
}



public function processApprovalGroupe(Request $request, $credit_groupe_id)
{
    Log::info('🎯 === DÉBUT PROCESS APPROVAL GROUPE - VERSION SANS DOUBLE DÉDUCTION ===');
    Log::info('📥 Données reçues:', $request->all());

    $request->validate([
        'action' => 'required|in:approuver,rejeter',
        'montant_total_groupe' => 'required_if:action,approuver|numeric|min:0.01',
         'agent_id' => 'required|exists:users,id',          // ✅ Nouveau
        'superviseur_id' => 'required|exists:users,id',   
        'montants_membres' => 'required_if:action,approuver|array',
        'montants_membres.*' => 'numeric|min:0',
        'motif_rejet' => 'required_if:action,rejeter',
    ]);

    try {
        DB::beginTransaction();

        $credit = CreditGroupe::with('compte')->findOrFail($credit_groupe_id);
        
        if ($request->action === 'approuver') {
            Log::info('🟢 APPROBATION GROUPE - VERSION AVEC UNE SEULE DÉDUCTION');
            
            // VALIDATION DE LA RÉPARTITION
            $totalMontantsMembres = array_sum($request->montants_membres);
            $montantTotalGroupe = floatval($request->montant_total_groupe);
            
            if (abs($totalMontantsMembres - $montantTotalGroupe) > 0.01) {
                throw new \Exception("La répartition n'est pas équilibrée. Total membres: {$totalMontantsMembres}, Total groupe: {$montantTotalGroupe}");
            }

            // CALCUL DES FRAIS TOTAUX
            $fraisEtCautionsMembres = $this->calculerFraisEtCautionsMembres($request->montants_membres);
            $totalFraisGroupe = $fraisEtCautionsMembres['total_frais'];
            $totalCautionGroupe = $fraisEtCautionsMembres['total_caution'];

            // VÉRIFIER LE SOLDE DU GROUPE
            $compteGroupe = $credit->compte;
            $soldeDebutGroupe = $compteGroupe->solde;
            
            Log::info("💰 SOLDE DÉBUT GROUPE: {$soldeDebutGroupe} USD, FRAIS À PRÉLEVER: {$totalFraisGroupe} USD");

            if ($soldeDebutGroupe < $totalFraisGroupe) {
                throw new \Exception("Solde groupe insuffisant pour payer les frais. Solde: {$soldeDebutGroupe} USD, Frais: {$totalFraisGroupe} USD");
            }

            // === DÉBUT DE LA SECTION CORRIGÉE ===
            
            // 1. DÉDUIRE LES FRAIS DU SOLDE DU COMPTE GROUPE
            $soldeApresFraisGroupe = $soldeDebutGroupe - $totalFraisGroupe;
            $compteGroupe->solde = $soldeApresFraisGroupe;
            $compteGroupe->save();

            Log::info("💰 FRAIS DÉDUITS - Solde début: {$soldeDebutGroupe} USD, Frais: {$totalFraisGroupe} USD, Solde après: {$soldeApresFraisGroupe} USD");

            // 2. CRÉER LE MOUVEMENT "RETRAIT FRAIS" POUR LE GROUPE
            Mouvement::create([
                'compte_id' => $compteGroupe->id,
                'type_mouvement' => 'frais_payes_credit_groupe',
                'montant' => -$totalFraisGroupe,
                'solde_avant' => $soldeDebutGroupe,
                'solde_apres' => $soldeApresFraisGroupe,
                'description' => "Paiement frais crédit groupe - Total: {$totalFraisGroupe} USD",
                'reference' => 'FRAIS-CREDIT-GROUPE-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compteGroupe->nom ?? 'Groupe',
            ]);

            // 3. TRANSFÉRER LES FRAIS VERS LE COMPTE SPÉCIAL
            $compteSpecial = $this->transfererFraisVersCompteSpecial($totalFraisGroupe, $compteGroupe->devise, $credit);

            // CRÉDITER LE COMPTE SPÉCIAL
            $ancienSoldeSpecial = $compteSpecial->solde;
            $compteSpecial->solde += $totalFraisGroupe;
            $compteSpecial->save();

            Log::info("💰 COMPTE SPÉCIAL CRÉDITÉ - Ancien solde: {$ancienSoldeSpecial} USD, Nouveau solde: {$compteSpecial->solde} USD");

            // 4. CRÉER L'HISTORIQUE DU COMPTE SPÉCIAL
            $this->creerHistoriqueCompteSpecial($totalFraisGroupe, $compteGroupe->devise, $credit, $compteGroupe);

            // ✅ VÉRIFICATION FINALE
            $compteGroupe->refresh();
            Log::info("✅ VÉRIFICATION FINALE - Solde groupe après frais: {$compteGroupe->solde} USD");

            // === FIN DE LA SECTION CORRIGÉE ===

            // 5. CRÉDITER DIRECTEMENT LES COMPTES DES MEMBRES (AVEC VÉRIFICATION)
            Log::info("💳 CRÉDIT DIRECT AUX MEMBRES - Total: {$montantTotalGroupe} USD");
            $this->crediterComptesMembresSansDouble($request->montants_membres, $credit);

            // 6. BLOQUER LA CAUTION DANS LE COMPTE GROUPE (SANS DÉDUCTION DU SOLDE)
            $soldeActuelGroupe = $compteGroupe->fresh()->solde;
            $cautionBloquee = false;

            if ($totalCautionGroupe > 0) {
                // ✅ CORRECTION : NE PAS DÉDUIRE LA CAUTION DU SOLDE
                // La caution reste dans le compte mais est marquée comme bloquée
                
                // Enregistrer la caution dans la table cautions (statut "bloquee")
                DB::table('cautions')->insert([
                    'compte_id' => $compteGroupe->id,
                    'credit_groupe_id' => $credit->id,
                    'montant' => $totalCautionGroupe,
                    'statut' => 'bloquee',
                    'date_blocage' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // ✅ CORRECTION : CRÉER UN MOUVEMENT DE "BLOQUAGE" SANS DÉDUCTION
                Mouvement::create([
                    'compte_id' => $compteGroupe->id,
                    'type_mouvement' => 'caution_bloquee_groupe',
                    'montant' => 0, // ❌ IMPORTANT : Montant 0 car pas de déduction
                    'solde_avant' => $soldeActuelGroupe,
                    'solde_apres' => $soldeActuelGroupe, // Même solde
                    'description' => "Caution bloquée pour crédit groupe - Montant: {$totalCautionGroupe} USD (non déduit)",
                    'reference' => 'CAUTION-GROUPE-' . $credit->id,
                    'date_mouvement' => now(),
                    'nom_deposant' => 'TUMAINI LETU Finances',
                ]);

                $cautionBloquee = true;
                Log::info("🔒 CAUTION BLOQUÉE (NON DÉDUITE) - Montant: {$totalCautionGroupe} USD, Solde groupe inchangé: {$soldeActuelGroupe} USD");
            }

            // 7. METTRE À JOUR LE CRÉDIT GROUPE
            $montantTotalAvecInteret = $montantTotalGroupe * 1.225;
            $remboursementHebdoTotal = $montantTotalAvecInteret / 16;

            $credit->update([
                'montant_accorde' => $montantTotalGroupe,
                'montant_total' => $montantTotalAvecInteret,
                'frais_dossier' => $fraisEtCautionsMembres['frais_dossier_total'],
                'frais_alerte' => $fraisEtCautionsMembres['frais_alerte_total'],
                'frais_carnet' => $fraisEtCautionsMembres['frais_carnet_total'],
                'frais_adhesion' => 0,
                'caution_totale' => $totalCautionGroupe,
                'remboursement_hebdo_total' => $remboursementHebdoTotal,
                'repartition_membres' => $this->calculerRepartitionMembres($request->montants_membres),
                'montants_membres' => $request->montants_membres,
                'agent_id' => $request->agent_id,          // ✅ Nouveau
                'superviseur_id' => $request->superviseur_id, // ✅ Nouveau
                'statut_demande' => 'approuve',
                'date_octroi' => now(),
                'date_echeance' => now()->addMonths(4),
                'frais_preleves' => true,
                'caution_bloquee' => $cautionBloquee,
            ]);

            // 8. GÉNÉRER LES ÉCRITURES COMPTABLES
            $this->genererEcrituresComptablesCreditGroupeCorrect(
                $credit,
                $compteGroupe,
                $totalFraisGroupe,
                $totalCautionGroupe,
                $montantTotalGroupe
            );

            // VÉRIFICATION FINALE
            $compteGroupe->refresh();
            Log::info("🔍 VÉRIFICATION FINALE - Solde groupe: {$compteGroupe->solde} USD");

            DB::commit();

            return redirect()->route('comptes.details', $credit->compte_id)
                ->with('success', "Crédit groupe accordé avec succès! Frais prélevés: {$totalFraisGroupe} USD. Membres crédités directement.");

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
        Log::error('💥 ERREUR APPROBATION GROUPE:', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return back()->withInput()->with('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
    }
}


/**
 * CRÉDITER LES COMPTES DES MEMBRES AVEC MISE À JOUR DES SOLDES
 */
private function crediterComptesMembresSansDouble($montantsMembres, $creditGroupe)
{
    Log::info('💳 === CRÉDIT DIRECT AUX MEMBRES - AVEC MISE À JOUR SOLDE ===');
    
    $totalCredite = 0;
    $membresCredites = 0;

    foreach ($montantsMembres as $membreId => $montantMembre) {
        $montant = floatval($montantMembre);
        
        if ($montant > 0) {
            try {
                // Trouver le compte du membre
                $compteMembre = Compte::where('client_id', $membreId)->first();
                
                if (!$compteMembre) {
                    Log::error("❌ Compte non trouvé pour client_id: {$membreId}");
                    continue;
                }

                $soldeDebutMembre = $compteMembre->solde;
                
                // ✅ CORRECTION : METTRE À JOUR LE SOLDE DU COMPTE
                $nouveauSolde = $soldeDebutMembre + $montant;
                $compteMembre->solde = $nouveauSolde;
                $compteMembre->save();
                
                Log::info("👤 Membre {$membreId}: Solde début = {$soldeDebutMembre} USD, Crédit = {$montant} USD, Nouveau solde = {$nouveauSolde} USD");

                // CRÉER LE MOUVEMENT "DÉPÔT" POUR LE MEMBRE
                Mouvement::create([
                    'compte_id' => $compteMembre->id,
                    'type_mouvement' => 'credit_groupe_recu',
                    'montant' => $montant,
                    'solde_avant' => $soldeDebutMembre,
                    'solde_apres' => $nouveauSolde,
                    'description' => "Crédit groupe reçu - Montant: {$montant} USD - Groupe: {$creditGroupe->compte->nom}",
                    'reference' => 'CREDIT-GRP-' . $creditGroupe->id,
                    'date_mouvement' => now(),
                    'nom_deposant' => 'TUMAINI LETU Finances',
                ]);

                // ✅ VÉRIFICATION : RECHARGER POUR CONFIRMER
                $compteMembre->refresh();
                Log::info("✅ VÉRIFICATION: Solde après mouvement = {$compteMembre->solde} USD");

                $totalCredite += $montant;
                $membresCredites++;

                Log::info("✅ Membre crédité - ID: {$membreId}, Montant: {$montant} USD");

            } catch (\Exception $e) {
                Log::error("❌ Erreur crédit membre {$membreId}: " . $e->getMessage());
                throw new \Exception("Erreur lors du crédit du membre {$membreId}: " . $e->getMessage());
            }
        }
    }

    Log::info("💰 TOTAL CRÉDITÉ AUX MEMBRES: {$totalCredite} USD pour {$membresCredites} membres");
    Log::info('💳 === FIN CRÉDIT DIRECT AVEC MISE À JOUR SOLDE ===');
    
    return [
        'total_credite' => $totalCredite,
        'membres_credites' => $membresCredites
    ];
}



private function crediterComptesMembresDirect($montantsMembres, $creditGroupe)
{
    Log::info('💳 === CRÉDIT DIRECT AUX MEMBRES ===');
    
    $totalCredite = 0;
    $membresCredites = 0;

    foreach ($montantsMembres as $membreId => $montantMembre) {
        $montant = floatval($montantMembre);
        
        if ($montant > 0) {
            try {
                // Trouver le compte du membre
                $compteMembre = Compte::where('client_id', $membreId)->first();
                
                if (!$compteMembre) {
                    Log::error("❌ Compte non trouvé pour client_id: {$membreId}");
                    continue;
                }

                $soldeDebutMembre = $compteMembre->solde;
                $nouveauSolde = $soldeDebutMembre + $montant;
                
                Log::info("👤 Membre {$membreId}: Solde début = {$soldeDebutMembre} USD, Crédit = {$montant} USD");

                // CRÉDITER LE COMPTE DU MEMBRE
                DB::table('comptes')
                    ->where('id', $compteMembre->id)
                    ->update(['solde' => $nouveauSolde]);
                
                $compteMembre->refresh();

                Log::info("✅ APRÈS CRÉDIT: Solde après = {$compteMembre->solde} USD");

                // CRÉER LE MOUVEMENT "DÉPÔT" POUR LE MEMBRE
                Mouvement::create([
                    'compte_id' => $compteMembre->id,
                    'type_mouvement' => 'credit_groupe_recu',
                    'montant' => $montant,
                    'solde_avant' => $soldeDebutMembre,
                    'solde_apres' => $compteMembre->solde,
                    'description' => "Crédit groupe reçu - Montant: {$montant} USD - Groupe: {$creditGroupe->compte->nom}",
                    'reference' => 'CREDIT-GRP-' . $creditGroupe->id,
                    'date_mouvement' => now(),
                    'nom_deposant' => 'TUMAINI LETU Finances',
                ]);

                $totalCredite += $montant;
                $membresCredites++;

                Log::info("✅ Membre crédité - ID: {$membreId}, Montant: {$montant} USD");

            } catch (\Exception $e) {
                Log::error("❌ Erreur crédit membre {$membreId}: " . $e->getMessage());
                throw new \Exception("Erreur lors du crédit du membre {$membreId}: " . $e->getMessage());
            }
        }
    }

    Log::info("💰 TOTAL CRÉDITÉ AUX MEMBRES: {$totalCredite} USD pour {$membresCredites} membres");
    Log::info('💳 === FIN CRÉDIT DIRECT ===');
}


/**
 * CRÉDITER DIRECTEMENT LES COMPTES DES MEMBRES - VERSION AVEC COMPENSATION DU DOUBLE
 */





/**
 * CALCUL DES FRAIS ET CAUTIONS POUR CHAQUE MEMBRE
 */
        private function calculerFraisEtCautionsMembres($montantsMembres)
        {
            $fraisParTranche = [
                50 => ['dossier' => 2, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 9],
                100 => ['dossier' => 4, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 11],
                150 => ['dossier' => 6, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 13],
                200 => ['dossier' => 8, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 15],
                250 => ['dossier' => 10, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 17],
                300 => ['dossier' => 12, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 19],
                350 => ['dossier' => 14, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 21],
                400 => ['dossier' => 16, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 23],
                450 => ['dossier' => 18, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 25],
                500 => ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 27],
            ];

            $totalFrais = 0;
            $totalCaution = 0;
            $fraisDossierTotal = 0;
            $fraisAlerteTotal = 0;
            $fraisCarnetTotal = 0;

            foreach ($montantsMembres as $membreId => $montantMembre) {
                $montant = floatval($montantMembre);
                
                if ($montant > 0) {
                    // Calcul de la caution (20% du montant accordé)
                    $cautionMembre = $montant * 0.20;
                    $totalCaution += $cautionMembre;

                    // Calcul des frais selon la tranche
                    $montantArrondi = floor($montant / 50) * 50;
                    if ($montantArrondi < 50) $montantArrondi = 50;
                    if ($montantArrondi > 500) $montantArrondi = 500;

                    $fraisMembre = $fraisParTranche[$montantArrondi] ?? $fraisParTranche[500];
                    
                    $totalFrais += $fraisMembre['total_frais'];
                    $fraisDossierTotal += $fraisMembre['dossier'];
                    $fraisAlerteTotal += $fraisMembre['alerte'];
                    $fraisCarnetTotal += $fraisMembre['carnet'];

                    Log::info("👤 Membre {$membreId} - Montant: {$montant}, Frais: {$fraisMembre['total_frais']}, Caution: {$cautionMembre}");
                }
            }

            return [
                'total_frais' => $totalFrais,
                'total_caution' => $totalCaution,
                'frais_dossier_total' => $fraisDossierTotal,
                'frais_alerte_total' => $fraisAlerteTotal,
                'frais_carnet_total' => $fraisCarnetTotal,
            ];
        }

/**
 * CALCUL DES FRAIS SELON LE TABLEAU FOURNI
 */
    private function calculerFraisSelonMontant($montant)
    {
        $frais = [
            50 => ['dossier' => 2, 'alerte' => 4.5, 'carnet' => 2.5],
            100 => ['dossier' => 4, 'alerte' => 4.5, 'carnet' => 2.5],
            150 => ['dossier' => 6, 'alerte' => 4.5, 'carnet' => 2.5],
            200 => ['dossier' => 8, 'alerte' => 4.5, 'carnet' => 2.5],
            250 => ['dossier' => 10, 'alerte' => 4.5, 'carnet' => 2.5],
            300 => ['dossier' => 12, 'alerte' => 4.5, 'carnet' => 2.5],
            350 => ['dossier' => 14, 'alerte' => 4.5, 'carnet' => 2.5],
            400 => ['dossier' => 16, 'alerte' => 4.5, 'carnet' => 2.5],
            450 => ['dossier' => 18, 'alerte' => 4.5, 'carnet' => 2.5],
            500 => ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5],
        ];
        
        // Arrondir au multiple de 50 inférieur
        $montantArrondi = floor($montant / 50) * 50;
        
        // Si le montant est supérieur à 500, utiliser les frais de 500
        if ($montantArrondi > 500) {
            $montantArrondi = 500;
        }
        
        // Si le montant est inférieur à 50, utiliser les frais de 50
        if ($montantArrondi < 50) {
            $montantArrondi = 50;
        }
        
        $fraisCalcules = $frais[$montantArrondi];
        $fraisCalcules['total_frais'] = $fraisCalcules['dossier'] + $fraisCalcules['alerte'] + $fraisCalcules['carnet'];
        
        return $fraisCalcules;
    }



  private function calculerRepartitionMembres($montantsMembres)
{
    $fraisParTranche = [
        50 => ['dossier' => 2, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 9],
        100 => ['dossier' => 4, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 11],
        150 => ['dossier' => 6, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 13],
        200 => ['dossier' => 8, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 15],
        250 => ['dossier' => 10, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 17],
        300 => ['dossier' => 12, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 19],
        350 => ['dossier' => 14, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 21],
        400 => ['dossier' => 16, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 23],
        450 => ['dossier' => 18, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 25],
        500 => ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5, 'total_frais' => 27],
    ];

    $repartition = [];
    
    foreach ($montantsMembres as $membreId => $montantMembre) {
        $montantMembre = floatval($montantMembre);
        if ($montantMembre > 0) {
            // Calcul des frais selon la tranche
            $montantArrondi = floor($montantMembre / 50) * 50;
            if ($montantArrondi < 50) $montantArrondi = 50;
            if ($montantArrondi > 500) $montantArrondi = 500;

            $fraisMembre = $fraisParTranche[$montantArrondi] ?? $fraisParTranche[500];
            
            // Calculs pour le membre
            $montantTotalMembre = $montantMembre * 1.225; // Coefficient 1.225
            $remboursementHebdoMembre = $montantTotalMembre / 16;
            $cautionMembre = $montantMembre * 0.20;
            
            // Récupérer les infos du membre pour l'affichage
            $compteMembre = Compte::where('client_id', $membreId)->first();
            $nomMembre = $compteMembre ? $compteMembre->nom . ' ' . $compteMembre->prenom : 'Membre ' . $membreId;
            $numeroCompte = $compteMembre ? $compteMembre->numero_compte : 'N/A';
            
            $repartition[$membreId] = [
                'membre_id' => $membreId,
                'nom_complet' => $nomMembre,
                'numero_compte' => $numeroCompte,
                'montant_accorde' => $montantMembre,
                'frais_dossier' => $fraisMembre['dossier'],
                'frais_alerte' => $fraisMembre['alerte'],
                'frais_carnet' => $fraisMembre['carnet'],
                'frais_adhesion' => 0, // Frais d'adhésion supprimés
                'caution' => $cautionMembre,
                'montant_total' => $montantTotalMembre,
                'remboursement_hebdo' => $remboursementHebdoMembre,
                'credite' => true, // Indique que le membre a été crédité
            ];
        }
    }
    
    return $repartition;
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

/**
 * MÉTHODE TEMPORAIRE POUR IDENTIFIER LA SOURCE DU DOUBLE CRÉDIT
 */
private function verifierDoubleCredit($creditGroupeId)
{
    Log::info("🔍 === VÉRIFICATION DOUBLE CRÉDIT POUR CRÉDIT GROUPE {$creditGroupeId} ===");
    
    $membresIds = [10, 32, 33, 35, 38];
    
    foreach ($membresIds as $membreId) {
        $compteMembre = Compte::where('client_id', $membreId)->first();
        if ($compteMembre) {
            $mouvements = Mouvement::where('compte_id', $compteMembre->id)
                ->where('created_at', '>=', now()->subHour())
                ->get();
                
            Log::info("📊 Membre {$membreId} - Mouvements récents: " . $mouvements->count());
            
            foreach ($mouvements as $mouvement) {
                Log::info("   - {$mouvement->type_mouvement}: {$mouvement->montant} USD - Ref: {$mouvement->reference}");
            }
        }
    }
    
    Log::info("🔍 === FIN VÉRIFICATION DOUBLE CRÉDIT ===");
}
   

/**
 * Générer les écritures comptables pour un crédit individuel
 */
/**
 * Générer les écritures comptables pour un crédit individuel (VERSION CORRIGÉE)
 */
private function genererEcrituresComptablesCreditIndividuel($credit, $compte, $frais, $montantTotal)
{
    try {
        $journal = JournalComptable::where('type_journal', 'banque')->first();
        
        if (!$journal) {
            Log::warning('Journal banque non trouvé pour écriture comptable crédit individuel');
            return;
        }

        $reference = 'CREDIT-IND-' . $credit->id . '-' . now()->format('YmdHis');

        // ✅ SEULEMENT LES FRAIS ET LA CAUTION - PAS LE CAPITAL
        // (le capital est déjà géré par votre système existant)

        // 1. Écriture pour les frais perçus
        $totalFrais = $frais['dossier'] + $frais['alerte'];

        if ($totalFrais > 0) {
            // Débit: Compte caisse (frais perçus)
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-FRAIS',
                'type_operation' => 'frais_credit_individuel',
                'compte_number' => '571100', // Compte caisse
                'libelle' => "Frais crédit individuel - Client: {$compte->nom} - Crédit #{$credit->id}",
                'montant_debit' => $totalFrais,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compte->devise,
                'statut' => 'comptabilise',
                'created_by' => auth::id(),
            ]);

            // Crédit: Compte produits divers (revenus frais)
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-FRAIS',
                'type_operation' => 'frais_credit_individuel',
                'compte_number' => '758100', // Produits divers
                'libelle' => "Frais crédit individuel - Client: {$compte->nom} - Crédit #{$credit->id}",
                'montant_debit' => 0,
                'montant_credit' => $totalFrais,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compte->devise,
                'statut' => 'comptabilise',
                'created_by' => auth::id(),
            ]);
        }

        // 2. Écriture pour la caution bloquée
        if ($frais['caution'] > 0) {
            // Débit: Compte caution clients
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-CAUTION',
                'type_operation' => 'caution_credit_individuel',
                'compte_number' => '455000', // Compte caution clients
                'libelle' => "Caution crédit individuel - Client: {$compte->nom} - Crédit #{$credit->id}",
                'montant_debit' => $frais['caution'],
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compte->devise,
                'statut' => 'comptabilise',
                'created_by' => auth::id(),
            ]);

            // Crédit: Compte caisse (caution reçue)
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-CAUTION',
                'type_operation' => 'caution_credit_individuel',
                'compte_number' => '571100', // Compte caisse
                'libelle' => "Caution crédit individuel - Client: {$compte->nom} - Crédit #{$credit->id}",
                'montant_debit' => 0,
                'montant_credit' => $frais['caution'],
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compte->devise,
                'statut' => 'comptabilise',
                'created_by' => auth::id(),
            ]);
        }

        // ❌ SUPPRIMÉ : Les écritures pour le capital (2,000 USD)
        // Votre système les génère déjà automatiquement

        Log::info("✅ Écritures comptables créées pour crédit individuel #{$credit->id} (frais et caution seulement)");

    } catch (\Exception $e) {
        Log::error("❌ Erreur création écritures comptables crédit individuel: " . $e->getMessage());
    }
}

/**
 * Générer les écritures comptables pour un crédit groupe (VERSION COMPLÈTE)
 */
private function genererEcrituresComptablesCreditGroupeCorrect($creditGroupe, $compteGroupe, $totalFrais, $totalCaution, $montantTotalGroupe)
{
    try {
        Log::info('📘 === DÉBUT ÉCRITURES COMPTABLES GROUPE ===');
        
        $journal = JournalComptable::where('type_journal', 'banque')->first();
        
        if (!$journal) {
            // Créer un journal par défaut
            $journal = JournalComptable::create([
                'nom' => 'Journal Banque Principal',
                'type_journal' => 'banque',
                'code' => 'BQ',
                'devise' => 'USD',
                'statut' => 'actif'
            ]);
        }

        $reference = 'CREDIT-GRP-' . $creditGroupe->id;
        $userId = auth::id() ?? 1;

        // 1. ÉCRITURES POUR LES FRAIS (PRODUITS)
        if ($totalFrais > 0) {
            // Débit: Compte caisse (frais reçus)
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-FRAIS',
                'type_operation' => 'frais_credit_groupe',
                'compte_number' => '571100', // Caisse
                'libelle' => "Frais crédit groupe perçus - Groupe: {$compteGroupe->nom}",
                'montant_debit' => $totalFrais,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compteGroupe->devise,
                'statut' => 'comptabilise',
                'created_by' => $userId,
            ]);

            // Crédit: Compte produits frais
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-FRAIS',
                'type_operation' => 'frais_credit_groupe',
                'compte_number' => '758100', // Produits divers
                'libelle' => "Produits frais crédit groupe - Groupe: {$compteGroupe->nom}",
                'montant_debit' => 0,
                'montant_credit' => $totalFrais,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compteGroupe->devise,
                'statut' => 'comptabilise',
                'created_by' => $userId,
            ]);

            Log::info("✅ Écritures frais: {$totalFrais} USD");
        }

        // 2. ÉCRITURES POUR LA CAUTION
        if ($totalCaution > 0 && $creditGroupe->caution_bloquee) {
            // Débit: Compte caution groupes
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-CAUTION',
                'type_operation' => 'caution_credit_groupe',
                'compte_number' => '455100', // Caution groupes
                'libelle' => "Caution crédit groupe bloquée - Groupe: {$compteGroupe->nom}",
                'montant_debit' => $totalCaution,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compteGroupe->devise,
                'statut' => 'comptabilise',
                'created_by' => $userId,
            ]);

            // Crédit: Compte caisse (caution reçue)
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference . '-CAUTION',
                'type_operation' => 'caution_credit_groupe',
                'compte_number' => '571100', // Caisse
                'libelle' => "Caution reçue crédit groupe - Groupe: {$compteGroupe->nom}",
                'montant_debit' => 0,
                'montant_credit' => $totalCaution,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $compteGroupe->devise,
                'statut' => 'comptabilise',
                'created_by' => $userId,
            ]);

            Log::info("✅ Écritures caution: {$totalCaution} USD");
        }

        Log::info('📘 === FIN ÉCRITURES COMPTABLES ===');

    } catch (\Exception $e) {
        Log::error("❌ Erreur écritures comptables: " . $e->getMessage());
        throw new \Exception("Erreur création écritures comptables: " . $e->getMessage());
    }
}
/**
 * Créer les mouvements pour un crédit groupe (comme pour l'individuel)
 */
/**
 * Créer les mouvements pour un crédit groupe (comme pour l'individuel) - VERSION CORRIGÉE
 */
private function creerMouvementsCreditGroupe($creditGroupe, $compteGroupe, $totalFrais, $totalCaution, $fraisPreleves)
{
    try {
        $soldeDebut = $compteGroupe->solde;
        
        // 1. Mouvement pour les frais payés (si prélevés)
        if ($fraisPreleves && $totalFrais > 0) {
            $soldeApresFrais = $soldeDebut - $totalFrais;
            
            Mouvement::create([
                'compte_id' => $compteGroupe->id,
                'type_mouvement' => 'frais_payes_credit_groupe',
                'montant' => -$totalFrais,
                'solde_avant' => $soldeDebut,
                'solde_apres' => $soldeApresFrais,
                'description' => "Paiement frais crédit groupe - Dossier: {$creditGroupe->frais_dossier}, Alerte: {$creditGroupe->frais_alerte}, Carnet: {$creditGroupe->frais_carnet}",
                'reference' => 'FRAIS-CREDIT-GROUPE-' . $creditGroupe->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compteGroupe->nom ?? 'Groupe',
            ]);

            Log::info("💰 MOUVEMENT FRAIS - Crédit groupe #{$creditGroupe->id}: {$totalFrais} {$compteGroupe->devise} déduits");
        }

        // 2. Mouvement pour la caution bloquée
        if ($creditGroupe->caution_bloquee && $totalCaution > 0) {
            $soldeActuel = $compteGroupe->fresh()->solde; // Recharger le solde actuel
            
            Mouvement::create([
                'compte_id' => $compteGroupe->id,
                'type_mouvement' => 'caution_bloquee_groupe',
                'montant' => -$totalCaution, // ❌ CORRECTION: Montant négatif pour la déduction
                'solde_avant' => $soldeActuel,
                'solde_apres' => $soldeActuel - $totalCaution, // ❌ CORRECTION: Calcul correct
                'description' => "Caution bloquée pour crédit groupe - Montant: {$totalCaution} {$compteGroupe->devise}",
                'reference' => 'CAUTION-GROUPE-' . $creditGroupe->id,
                'date_mouvement' => now(),
                'nom_deposant' => 'TUMAINI LETU Finances',
            ]);

            // ❌ CORRECTION: Mettre à jour le solde du compte groupe
            $compteGroupe->solde -= $totalCaution;
            $compteGroupe->save();

            Log::info("🔒 MOUVEMENT CAUTION - Crédit groupe #{$creditGroupe->id}: {$totalCaution} {$compteGroupe->devise} bloqués");
        }

        Log::info("✅ Mouvements créés pour crédit groupe #{$creditGroupe->id}");

    } catch (\Exception $e) {
        Log::error("❌ Erreur création mouvements crédit groupe: " . $e->getMessage());
        throw $e;
    }
}
}