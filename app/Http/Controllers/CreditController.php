<?php

namespace App\Http\Controllers;

use App\Models\CompteSpecial;
use App\Models\Credit;
use App\Models\Compte;
use App\Models\CreditGroupe;
use App\Models\HistoriqueCompteSpecial;
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

            // Calculer le total des frais à payer (sans la caution)
            $totalFrais = $frais['dossier'] + $frais['alerte'] + $frais['adhesion'];
            
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
                'description' => "Paiement frais pour octroi crédit - Dossier: {$frais['dossier']}, Alerte: {$frais['alerte']}, Adhésion: {$frais['adhesion']}",
                'reference' => 'FRAIS-CREDIT-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compte->nom . ' ' . $compte->prenom ?? 'Système',
            ]);

            Log::info("💰 FRAIS DÉDUITS - Solde après frais: {$soldeApresFrais}");

            // 3. TRANSFÉRER LES FRAIS VERS LE COMPTE SPÉCIAL
            $this->transfererFraisVersCompteSpecial($totalFrais, $compte->devise, $credit);

            // 4. CRÉER L'HISTORIQUE DANS LE COMPTE SPÉCIAL
            $this->creerHistoriqueCompteSpecial($totalFrais, $compte->devise, $credit, $compte);

            // 5. METTRE À JOUR LE CRÉDIT
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

            // 6. CRÉDITER LE MONTANT ACCORDÉ AU COMPTE
            $soldeApresCredit = $soldeApresFrais + $request->montant_accorde;
            $compte->solde = $soldeApresCredit;
            $compte->save();

            Log::info("💳 CRÉDIT AJOUTÉ - Solde après crédit: {$soldeApresCredit}");

            // 7. CRÉER LE MOUVEMENT "CRÉDIT OCTROYÉ"
            Mouvement::create([
                'compte_id' => $compte->id,
                'type_mouvement' => 'credit_octroye',
                'montant' => $request->montant_accorde,
                'solde_avant' => $soldeApresFrais,
                'solde_apres' => $soldeApresCredit,
                'description' => "Octroi de crédit individuel - Montant: {$request->montant_accorde} {$compte->devise}",
                'reference' => 'CREDIT-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compte->nom . ' ' . $compte->prenom ?? 'Système',
            ]);

            // 8. BLOQUER LA CAUTION DANS LE COMPTE (CORRIGÉ POUR CRÉDIT INDIVIDUEL)
            $caution = $frais['caution'];
            if ($caution > 0) {
                DB::table('cautions')->insert([
                    'compte_id' => $compte->id,
                    'credit_id' => $credit->id, // ✅ CORRECTION: credit_id pour crédit individuel
                    'montant' => $caution,
                    'statut' => 'bloquee',
                    'date_blocage' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("🔒 CAUTION BLOQUÉE - Montant: {$caution} USD pour crédit individuel #{$credit->id}");
            }

            Log::info('✅ Crédit individuel approuvé avec succès - Frais transférés - Caution bloquée');
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
 * Transfère les frais vers le compte spécial selon la devise
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

    // Créditer le compte spécial
    $ancienSoldeSpecial = $compteSpecial->solde;
    $compteSpecial->increment('solde', $montantFrais);
    
    Log::info("Frais transférés vers compte spécial: {$montantFrais} {$devise}");
}

/**
 * Crée l'historique dans le compte spécial
 */
private function creerHistoriqueCompteSpecial($montantFrais, $devise, $credit, $compteClient)
{
    $nomClient = trim($compteClient->nom . ' ' . ($compteClient->postnom ?? '') . ' ' . ($compteClient->prenom ?? ''));
    
    HistoriqueCompteSpecial::create([
        'client_nom' => $nomClient,
        'montant' => $montantFrais,
        'devise' => $devise,
        'description' => "Frais crédit payés - Crédit #{$credit->id} - Client: {$nomClient}",
    ]);
    
    Log::info("Historique créé pour compte spécial: {$montantFrais} {$devise}");
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
// Dans App\Http\Controllers\CreditController - processApprovalGroupe method
// Remplacer toute la logique d'approbation groupe

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

            // CALCUL DES FRAIS POUR LE GROUPE (comme crédit individuel)
            $fraisGroupe = Credit::calculerFraisIndividuel($montantTotalGroupe);
            $totalFraisGroupe = $fraisGroupe['dossier'] + $fraisGroupe['alerte'] + $fraisGroupe['adhesion'];
            $cautionGroupe = $fraisGroupe['caution'];

            // VÉRIFIER LE SOLDE DU GROUPE POUR LES FRAIS
            $compteGroupe = $credit->compte;
            $soldeDebutGroupe = $compteGroupe->solde;
            
            if ($soldeDebutGroupe < $totalFraisGroupe) {
                throw new \Exception("Solde insuffisant pour payer les frais. Solde groupe: {$soldeDebutGroupe} USD, Frais à payer: {$totalFraisGroupe} USD");
            }

            Log::info("📊 CALCULS GROUPE - Solde début: {$soldeDebutGroupe}, Frais: {$totalFraisGroupe}, Crédit: {$montantTotalGroupe}, Caution: {$cautionGroupe}");

            // 1. RETRANCHER LES FRAIS DU SOLDE DU GROUPE
            $soldeApresFraisGroupe = $soldeDebutGroupe - $totalFraisGroupe;
            $compteGroupe->solde = $soldeApresFraisGroupe;
            $compteGroupe->save();

            // 2. CRÉER LE MOUVEMENT "FRAIS PAYÉS" POUR LE GROUPE
            Mouvement::create([
                'compte_id' => $compteGroupe->id,
                'type_mouvement' => 'frais_payes_credit_groupe',
                'montant' => -$totalFraisGroupe,
                'solde_avant' => $soldeDebutGroupe,
                'solde_apres' => $soldeApresFraisGroupe,
                'description' => "Paiement frais crédit groupe - Dossier: {$fraisGroupe['dossier']}, Alerte: {$fraisGroupe['alerte']}, Adhésion: {$fraisGroupe['adhesion']}",
                'reference' => 'FRAIS-CREDIT-GROUPE-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compteGroupe->nom ?? 'Groupe',
            ]);

            Log::info("💰 FRAIS DÉDUITS GROUPE - Solde après frais: {$soldeApresFraisGroupe}");

            // 3. TRANSFÉRER LES FRAIS VERS LE COMPTE SPÉCIAL
            $this->transfererFraisVersCompteSpecial($totalFraisGroupe, $compteGroupe->devise, $credit);

            // 4. CRÉER L'HISTORIQUE DANS LE COMPTE SPÉCIAL
            $this->creerHistoriqueCompteSpecial($totalFraisGroupe, $compteGroupe->devise, $credit, $compteGroupe);

            // 5. CRÉDITER LE MONTANT ACCORDÉ AU COMPTE DU GROUPE
            $soldeApresCreditGroupe = $soldeApresFraisGroupe + $montantTotalGroupe;
            $compteGroupe->solde = $soldeApresCreditGroupe;
            $compteGroupe->save();

            Log::info("💳 CRÉDIT AJOUTÉ AU GROUPE - Solde après crédit: {$soldeApresCreditGroupe}");

            // 6. CRÉER LE MOUVEMENT "CRÉDIT OCTROYÉ" POUR LE GROUPE
            Mouvement::create([
                'compte_id' => $compteGroupe->id,
                'type_mouvement' => 'credit_octroye_groupe',
                'montant' => $montantTotalGroupe,
                'solde_avant' => $soldeApresFraisGroupe,
                'solde_apres' => $soldeApresCreditGroupe,
                'description' => "Octroi de crédit groupe - Montant: {$montantTotalGroupe} USD",
                'reference' => 'CREDIT-GROUPE-' . $credit->id,
                'date_mouvement' => now(),
                'nom_deposant' => $compteGroupe->nom ?? 'Groupe',
            ]);

            // 7. BLOQUER LA CAUTION DU GROUPE
            if ($cautionGroupe > 0) {
                DB::table('cautions')->insert([
                    'compte_id' => $compteGroupe->id,
                    'credit_groupe_id' => $credit->id,
                    'montant' => $cautionGroupe,
                    'statut' => 'bloquee',
                    'date_blocage' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("🔒 CAUTION GROUPE BLOQUÉE - Montant: {$cautionGroupe} USD");
            }

            // 8. METTRE À JOUR LE CRÉDIT GROUPE
            $montantTotalAvecInteret = $montantTotalGroupe * 1.225;
            $remboursementHebdoTotal = $montantTotalAvecInteret / 16;

            $credit->update([
                'montant_accorde' => $montantTotalGroupe,
                'montant_total' => $montantTotalAvecInteret,
                'frais_dossier' => $fraisGroupe['dossier'],
                'frais_alerte' => $fraisGroupe['alerte'],
                'frais_adhesion' => $fraisGroupe['adhesion'],
                'caution_totale' => $cautionGroupe,
                'remboursement_hebdo_total' => $remboursementHebdoTotal,
                'repartition_membres' => $this->calculerRepartitionMembres($request->montants_membres),
                'montants_membres' => $request->montants_membres,
                'statut_demande' => 'approuve',
                'date_octroi' => now(),
                'date_echeance' => now()->addMonths(4),
            ]);

            Log::info('✅ Crédit groupe approuvé avec succès');

            DB::commit();

            return redirect()->route('comptes.details', $credit->compte_id)
                ->with('success', 'Crédit groupe accordé avec succès! Les frais ont été prélevés et la caution est bloquée sur le compte du groupe.');

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

/**
 * Calcule la répartition détaillée des membres
 */
private function calculerRepartitionMembres($montantsMembres)
{
    $repartition = [];
    
    foreach ($montantsMembres as $membreId => $montantMembre) {
        $montantMembre = floatval($montantMembre);
        if ($montantMembre > 0) {
            $fraisMembre = Credit::calculerFraisGroupe($montantMembre);
            $montantTotalMembre = Credit::calculerMontantTotalGroupe($montantMembre);
            $remboursementHebdoMembre = Credit::calculerRemboursementHebdo($montantTotalMembre, 'groupe');
            
            $repartition[$membreId] = [
                'montant_accorde' => $montantMembre,
                'frais_dossier' => $fraisMembre['dossier'],
                'frais_alerte' => $fraisMembre['alerte'],
                'frais_carnet' => $fraisMembre['carnet'],
                'frais_adhesion' => $fraisMembre['adhesion'],
                'caution' => $montantMembre * 0.20,
                'montant_total' => $montantTotalMembre,
                'remboursement_hebdo' => $remboursementHebdoMembre,
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
}