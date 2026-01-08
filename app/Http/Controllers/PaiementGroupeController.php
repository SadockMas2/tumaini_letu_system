<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\Compte;
use App\Models\EcritureComptable;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Models\PaiementCredit;
use App\Enums\TypePaiement;
use Filament\Notifications\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaiementGroupeController extends Controller
{
    public function index()
    {
        $groupesActifs = $this->getGroupesAvecCreditsActifs();
        
        return view('paiement-credits-groupe', [
            'groupesActifs' => $groupesActifs,
            'selectedGroupeId' => request('selected_groupe_id'),
            'paiementsMembres' => []
        ]);
    }

public function processerPaiements(Request $request)
{
    $request->validate([
        'selected_groupe_id' => 'required|exists:credit_groupes,id',
        'paiements_membres' => 'required|array',
        'mode_paiement' => 'sometimes|in:normal,complement',
        'membres_complement' => 'sometimes|array',
    ]);

    $mode = $request->input('mode_paiement', 'normal');
    $membresComplement = $request->input('membres_complement', []);
    
    try {
        DB::transaction(function () use ($request, $mode, $membresComplement) {
            $creditGroupe = CreditGroupe::with(['compte'])->findOrFail($request->selected_groupe_id);
            $datePaiement = now();
            $results = [];
            $totalPaiementGroupe = 0;

            foreach ($request->paiements_membres as $membreId => $montantApporte) {
                // CORRECTION : Remplacer les virgules par des points
                $montantApporte = str_replace(',', '.', $montantApporte);
                $montantApporte = floatval($montantApporte);
                
                // En mode complément, ne traiter que les membres sélectionnés
                if ($mode === 'complement') {
                    if (!in_array($membreId, $membresComplement)) {
                        continue; // Ignorer ce membre
                    }
                }
                
                // Accepter 0 pour les compléments
                if ($montantApporte >= 0) {
                    $result = $this->traiterPaiementMembreGroupeExact($membreId, $montantApporte, $creditGroupe, $datePaiement);
                    $results[] = $result;
                    $totalPaiementGroupe += $result['montant_preleve_groupe'] ?? 0;
                }
            }

            $this->traiterPaiementsParMembre($creditGroupe, $results, $datePaiement);

            session()->flash('paiement_success', true);
            session()->flash('results', $results);
            session()->flash('total_paiement_groupe', $totalPaiementGroupe);
            session()->flash('credit_groupe_nom', $creditGroupe->compte->nom ?? 'Groupe');
            session()->flash('mode_paiement', $mode);
            
            $totalExcedent = array_sum(array_column($results, 'montant_excedent'));
            session()->flash('total_excedent', $totalExcedent);
        });

        return redirect()->route('paiement.credits.groupe', [
            'selected_groupe_id' => $request->selected_groupe_id
        ])->with('success', 'Paiements traités avec succès!');

    } catch (\Exception $e) {
        Log::error('Erreur lors du traitement des paiements groupe: ' . $e->getMessage());
        return redirect()->route('paiement.credits.groupe')
            ->with('error', 'Une erreur est survenue lors du traitement des paiements: ' . $e->getMessage());
    }
}
    /**
     * CORRECTION : Traite les paiements multiples (si montant > remboursement hebdo)
     */


private function traiterPaiementsParMembre($creditGroupe, $results, $datePaiement)
{
    // Toujours enregistrer le paiement groupe si des membres ont remboursé
    // Même si tout a été prélevé des comptes membres
    $this->enregistrerPaiementGroupeComplet($creditGroupe, $results, $datePaiement);
}
    /**
     * NOUVELLE MÉTHODE : Distribue les excédents exacts aux membres
     */
// private function distribuerExcedentsMembresExact($creditGroupe, $excedentsParMembre, $datePaiement)
// {
//     foreach ($excedentsParMembre as $membreId => $excedent) {
//         if ($excedent > 0) {
//             $compteMembre = Compte::where('client_id', $membreId)->first();
//             if ($compteMembre) {
//                 // Créditer le compte membre avec l'excédent exact
//                 $ancienSolde = $compteMembre->solde;
//                 $compteMembre->solde += $excedent;
//                 $compteMembre->save();
                
//                 // Créer un mouvement
//                 $reference = 'EXCEDENT-EXACT-GRP-' . $creditGroupe->id . '-MEMBRE-' . $membreId . '-' . now()->format('YmdHis');
                
//                 Mouvement::create([
//                     'compte_id' => $compteMembre->id,
//                     'type_mouvement' => 'excedent_groupe_exact',
//                     'montant' => $excedent,
//                     'solde_avant' => $ancienSolde,
//                     'solde_apres' => $compteMembre->solde,
//                     'description' => "Excédent exact remboursement crédit groupe - Montant: " . number_format($excedent, 2) . " USD",
//                     'reference' => $reference,
//                     'date_mouvement' => $datePaiement,
//                     'nom_deposant' => 'Système Automatique'
//                 ]);
                
//                 Log::info("💰 Excédent exact distribué au membre", [
//                     'membre_id' => $membreId,
//                     'montant_excedent' => $excedent,
//                     'nouveau_solde' => $compteMembre->solde
//                 ]);
//             }
//         }
//     }
// }



    /**
     * CORRECTION : Distribue l'excédent aux membres proportionnellement
     */
    private function distribuerExcedentMembres($creditGroupe, $montantExcedent, $datePaiement)
    {
        $repartition = $creditGroupe->repartition_membres ?? [];
        $totalRemboursementHebdo = $creditGroupe->remboursement_hebdo_total;
        
        foreach ($repartition as $membreId => $details) {
            $montantDuMembre = $details['remboursement_hebdo'] ?? 0;
            $pourcentageMembre = $montantDuMembre / $totalRemboursementHebdo;
            $excedentMembre = $montantExcedent * $pourcentageMembre;
            
            if ($excedentMembre > 0) {
                $compteMembre = Compte::where('client_id', $membreId)->first();
                if ($compteMembre) {
                    // Créditer le compte membre
                    $ancienSolde = $compteMembre->solde;
                    $compteMembre->solde += $excedentMembre;
                    $compteMembre->save();
                    
                    // Créer un mouvement
                    $reference = 'EXCEDENT-GRP-' . $creditGroupe->id . '-' . now()->format('YmdHis');
                    
                    Mouvement::create([
                        'compte_id' => $compteMembre->id,
                        'type_mouvement' => 'excedent_groupe',
                        'montant' => $excedentMembre,
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $compteMembre->solde,
                        'description' => "Excédent remboursement crédit groupe - Montant: " . number_format($excedentMembre, 2) . " USD",
                        'reference' => $reference,
                        'date_mouvement' => $datePaiement,
                        'nom_deposant' => 'Système Automatique'
                    ]);
                    
                    Log::info("💰 Excédent distribué au membre", [
                        'membre_id' => $membreId,
                        'montant' => $excedentMembre,
                        'nouveau_solde' => $compteMembre->solde
                    ]);
                }
            }
        }
    }



    /**
 * Enregistre le paiement groupe même avec 0 prélevé du groupe
 */
/**
 * Enregistre le paiement groupe avec le MONTANT RÉELLEMENT COLLECTÉ
 */
private function enregistrerPaiementGroupeComplet($creditGroupe, $results, $datePaiement)
{
    $repartition = $creditGroupe->repartition_membres ?? [];
    $montantEffectivementCollecte = 0;  // Montant réel collecté (prélevé groupe + prélevé membre)
    $montantTotalRembourse = 0;         // Montant dû total (inclut les déficits)
    $montantTotalCapital = 0;
    $montantTotalInterets = 0;
    
    foreach ($results as $result) {
        $membreId = $result['membre_id'] ?? null;
        if ($membreId && isset($repartition[$membreId])) {
            $montantDuMembre = $result['montant_du'] ?? 0;
            $montantPreleveGroupe = $result['montant_preleve_groupe'] ?? 0;
            $montantPreleveMembre = $result['montant_preleve_membre'] ?? 0;
            
            // Montant réellement payé par ce membre
            $montantEffectifPaye = min($montantPreleveGroupe + $montantPreleveMembre, $montantDuMembre);
            $montantEffectivementCollecte += $montantEffectifPaye;
            
            // Pour le calcul capital/intérêts, on prend le montant dû (même si pas entièrement payé)
            $montantTotalRembourse += $montantDuMembre;
            
            // Calculer la répartition capital/intérêts pour ce membre
            $detailsMembre = $repartition[$membreId];
            $montantAccordeMembre = $detailsMembre['montant_accorde'] ?? 0;
            $montantTotalMembre = $detailsMembre['montant_total'] ?? 0;
            
            $capitalHebdoMembre = $montantAccordeMembre / 16;
            $interetsHebdoMembre = ($montantTotalMembre - $montantAccordeMembre) / 16;
            
            // Ajouter proportionnellement au montant effectivement payé
            // Montant réellement payé par ce membre
            $montantReelPaye = min($montantPreleveGroupe + $montantPreleveMembre, $montantDuMembre);

            // Répartition proportionnelle
            $pourcentageDuMontantTotal = $montantDuMembre / $creditGroupe->remboursement_hebdo_total;
            $partCapitalMembre = ($capitalHebdoMembre / $montantDuMembre) * $montantReelPaye;
            $partInteretsMembre = ($interetsHebdoMembre / $montantDuMembre) * $montantReelPaye;

            $montantTotalCapital += $partCapitalMembre;
            $montantTotalInterets += $partInteretsMembre;
        }
    }
    
    Log::info("=== ENREGISTREMENT PAIEMENT GROUPE ===");
    Log::info("Montant réellement collecté: {$montantEffectivementCollecte} USD");
    Log::info("Montant dû total: {$montantTotalRembourse} USD");
    Log::info("Capital: {$montantTotalCapital} USD");
    Log::info("Intérêts: {$montantTotalInterets} USD");
    
    if ($montantEffectivementCollecte > 0) {
        // Enregistrer le paiement avec le MONTANT RÉEL COLLECTÉ
        $paiement = PaiementCredit::create([
            'credit_id' => null,
            'credit_groupe_id' => $creditGroupe->id,
            'compte_id' => $creditGroupe->compte_id,
            'montant_paye' => $montantEffectivementCollecte, // CORRECTION ICI
            'date_paiement' => $datePaiement,
            'type_paiement' => TypePaiement::GROUPE->value,
            'reference' => 'PAY-GROUPE-' . $creditGroupe->id . '-' . now()->format('YmdHis'),
            'statut' => 'complet',
            'capital_rembourse' => $montantTotalCapital,
            'interets_payes' => $montantTotalInterets
        ]);

        Log::info("✅ Paiement groupe enregistré: {$montantEffectivementCollecte} USD");

        // Générer les écritures comptables
        $repartition = [
            'capital' => $montantTotalCapital,
            'interets' => $montantTotalInterets,
            'excédent' => 0
        ];
        
        $this->genererEcritureComptablePaiementGroupe(
            $creditGroupe->compte, 
            $creditGroupe, 
            $montantEffectivementCollecte, // CORRECTION ICI
            $repartition, 
            $paiement->reference
        );
        
        // Mettre à jour l'échéancier avec le MONTANT RÉEL
        $this->mettreAJourEcheancierAvecMontantReel($creditGroupe, $paiement, $montantEffectivementCollecte);
        
        return $paiement;
    }
    
    Log::info("⚠️ Aucun montant collecté - Paiement non enregistré");
    return null;
}


/**
 * CORRECTION : Met à jour l'échéancier après paiement avec montant réel
 */
private function mettreAJourEcheancierAvecMontantReel($creditGroupe, $paiement, $montantReel)
{
    // Trouver la prochaine échéance non payée
    $echeance = DB::table('echeanciers')
        ->where('credit_groupe_id', $creditGroupe->id)
        ->where('statut', 'a_venir')
        ->orderBy('semaine', 'asc')
        ->first();
        
    if ($echeance) {
        // Si paiement partiel (inférieur au dû hebdo)
        $montantHebdomadaire = $creditGroupe->remboursement_hebdo_total;
        
        if ($montantReel < $montantHebdomadaire) {
            // Marquer comme partiellement payé
            DB::table('echeanciers')
                ->where('id', $echeance->id)
                ->update([
                    'statut' => 'partiel',
                    'date_paiement' => $paiement->date_paiement,
                    'montant_paye' => $montantReel,
                    'updated_at' => now()
                ]);
            
            Log::info("⚠️ Échéance marquée comme partiellement payée: {$montantReel}/{$montantHebdomadaire} USD");
        } else {
            // Paiement complet
            DB::table('echeanciers')
                ->where('id', $echeance->id)
                ->update([
                    'statut' => 'paye',
                    'date_paiement' => $paiement->date_paiement,
                    'montant_paye' => $montantHebdomadaire,
                    'updated_at' => now()
                ]);
            
            Log::info("✅ Échéance marquée comme payée: {$montantHebdomadaire} USD");
        }
    }
}

/**
 * Vérifie le solde réel vs solde affiché
 */
private function verifierSoldeGroupe($creditGroupeId)
{
    $creditGroupe = CreditGroupe::with('compte')->find($creditGroupeId);
    
    if (!$creditGroupe || !$creditGroupe->compte) {
        return null;
    }
    
    // Vérifier le solde depuis la table
    $soldeDirect = DB::table('comptes')
        ->where('id', $creditGroupe->compte->id)
        ->value('solde');
    
    // Vérifier les mouvements
    $totalDepots = Mouvement::where('compte_id', $creditGroupe->compte->id)
        ->whereIn('type_mouvement', ['depot', 'recouvrement_credit_groupe'])
        ->sum('montant');
    
    $totalRetraits = Mouvement::where('compte_id', $creditGroupe->compte->id)
        ->whereIn('type_mouvement', ['paiement_credit_groupe', 'retrait'])
        ->sum('montant');
    
    $soldeCalcule = $totalDepots - $totalRetraits;
    
    Log::info('🔍 VÉRIFICATION SOLDE GROUPE', [
        'groupe_id' => $creditGroupe->id,
        'solde_direct' => $soldeDirect,
        'solde_modele' => $creditGroupe->compte->solde,
        'total_depots' => $totalDepots,
        'total_retraits' => $totalRetraits,
        'solde_calcule' => $soldeCalcule,
        'difference' => $soldeDirect - $creditGroupe->compte->solde
    ]);
    
    return [
        'direct' => (float) $soldeDirect,
        'modele' => (float) $creditGroupe->compte->solde,
        'calcule' => (float) $soldeCalcule
    ];
}

private function traiterPaiementMembreGroupeExact($membreId, $montantApporte, $creditGroupe, $datePaiement)
{
    Log::info("=== DÉBUT TRAITEMENT EXACT PAIEMENT MEMBRE GROUPE ===");
    Log::info("Membre ID: {$membreId}");
    Log::info("Montant apporté: {$montantApporte}");
    Log::info("Crédit Groupe ID: {$creditGroupe->id}");
    Log::info("Date: {$datePaiement}");
    
    $compteMembre = Compte::where('client_id', $membreId)->first();
    $compteGroupe = $creditGroupe->compte;
    
    // LOGS DÉTAILLÉS DU GROUPE
    Log::info("=== INFOS COMPTE GROUPE ===");
    Log::info("ID: {$compteGroupe->id}");
    Log::info("Numéro: {$compteGroupe->numero_compte}");
    Log::info("Solde AVANT traitement: {$compteGroupe->solde} USD");
    Log::info("Type: {$compteGroupe->type_compte}");
    
    if (!$compteMembre) {
        Log::error("❌ Compte membre non trouvé pour client_id: {$membreId}");
        return [
            'compte' => 'Membre ' . $membreId,
            'montant_apporte' => $montantApporte,
            'montant_preleve_groupe' => 0,
            'montant_preleve_membre' => 0,
            'montant_du' => 0,
            'montant_excedent' => 0,
            'statut' => 'echec',
            'raison' => 'Compte membre non trouvé',
            'membre_id' => $membreId
        ];
    }

    // Récupérer le montant dû hebdomadaire
    $repartition = $creditGroupe->repartition_membres ?? [];
    $detailsMembre = $repartition[$membreId] ?? [];
    $montantDuMembre = $detailsMembre['remboursement_hebdo'] ?? 0;
    
    Log::info("=== INFOS MEMBRE ===");
    Log::info("Compte membre: {$compteMembre->numero_compte}");
    Log::info("Nom: {$compteMembre->nom} {$compteMembre->prenom}");
    Log::info("Solde membre: {$compteMembre->solde} USD");
    Log::info("Montant dû hebdo: {$montantDuMembre} USD");
    Log::info("Montant apporté: {$montantApporte} USD");
    
    // Calculer solde disponible groupe
    $cautionGroupe = DB::table('cautions')
        ->where('compte_id', $compteGroupe->id)
        ->where('statut', 'bloquee')
        ->sum('montant');
    
    $soldeDisponibleGroupe = max(0, $compteGroupe->solde - $cautionGroupe);
    
    Log::info("=== INFOS CAUTION ===");
    Log::info("Caution bloquée: {$cautionGroupe} USD");
    Log::info("Solde disponible groupe: {$soldeDisponibleGroupe} USD");
    
    // === LOGIQUE DE PRÉLÈVEMENT ===
    // RÈGLE 1: Prélèvement groupe = montant apporté
    $montantPreleveGroupe = $montantApporte;
    $montantPreleveMembre = 0;
    $montantExcedent = 0;
    
    // RÈGLE 2: Déterminer excédent ou déficit
    if ($montantApporte >= $montantDuMembre) {
        // Le membre paie assez ou plus
        $montantExcedent = $montantApporte - $montantDuMembre;
        Log::info("✅ Membre paie assez - Excédent: {$montantExcedent} USD");
    } else {
        // Le membre ne paie pas assez
        $deficit = $montantDuMembre - $montantApporte;
        Log::info("⚠️ Membre ne paie pas assez - Déficit: {$deficit} USD");
        
        // Vérifier solde membre
        $soldeMembre = $compteMembre->solde;
        Log::info("Solde disponible membre: {$soldeMembre} USD");
        
        if ($soldeMembre >= $deficit) {
            $montantPreleveMembre = $deficit;
            Log::info("✅ Membre a assez - Complément: {$montantPreleveMembre} USD");
        } else {
            $montantPreleveMembre = $soldeMembre;
            Log::info("⚠️ Membre n'a pas assez - Complément partiel: {$montantPreleveMembre} USD");
        }
    }
    
    Log::info("=== RÉSUMÉ PRÉLÈVEMENTS ===");
    Log::info("Prélèvement groupe: {$montantPreleveGroupe} USD");
    Log::info("Prélèvement membre: {$montantPreleveMembre} USD");
    Log::info("Excédent: {$montantExcedent} USD");
    Log::info("Total dû: {$montantDuMembre} USD");
    
    // VÉRIFICATION SOLDE GROUPE
    Log::info("=== VÉRIFICATION SOLDE GROUPE ===");
    Log::info("Solde groupe avant: {$compteGroupe->solde} USD");
    Log::info("Caution bloquée: {$cautionGroupe} USD");
    Log::info("Solde disponible: {$soldeDisponibleGroupe} USD");
    Log::info("Prélèvement demandé: {$montantPreleveGroupe} USD");
    
    // IMPORTANT: Validation du solde disponible
    if ($montantPreleveGroupe > $soldeDisponibleGroupe) {
        $message = "❌ Prélèvement refusé - Solde disponible insuffisant";
        Log::error($message);
        return [
            'compte' => $compteMembre->numero_compte,
            'montant_apporte' => $montantApporte,
            'montant_preleve_groupe' => 0,
            'montant_preleve_membre' => 0,
            'montant_du' => $montantDuMembre,
            'montant_excedent' => 0,
            'statut' => 'echec',
            'raison' => "Solde disponible groupe insuffisant. Disponible: " . number_format($soldeDisponibleGroupe, 2) . " USD",
            'membre_id' => $membreId
        ];
    }
    
    // === PRÉLÈVEMENT DU GROUPE ===
    if ($montantPreleveGroupe > 0) {
        $ancienSoldeGroupe = $compteGroupe->solde;
        $nouveauSoldeGroupe = $ancienSoldeGroupe - $montantPreleveGroupe;
        
        Log::info("=== PRÉLÈVEMENT GROUPE ===");
        Log::info("Ancien solde: {$ancienSoldeGroupe} USD");
        Log::info("Montant à prélever: {$montantPreleveGroupe} USD");
        Log::info("Nouveau solde (calculé): {$nouveauSoldeGroupe} USD");
        
        // Mettre à jour le solde
        $compteGroupe->solde = $nouveauSoldeGroupe;
        $compteGroupe->save();
        
        Log::info("✅ Solde groupe mis à jour: {$compteGroupe->solde} USD");
        
        // Créer mouvement
        $referenceGroupe = 'PRELEV-GRP-' . $creditGroupe->id . '-MEMBRE-' . $membreId . '-' . now()->format('YmdHis');
        
        $mouvementGroupe = Mouvement::create([
            'compte_id' => $compteGroupe->id,
            'type' => 'retrait',
            'type_mouvement' => 'paiement_credit_groupe',
            'montant' => $montantPreleveGroupe,
            'solde_avant' => $ancienSoldeGroupe,
            'solde_apres' => $compteGroupe->solde,
            'description' => "Prélèvement crédit groupe - Membre: " . $compteMembre->numero_compte . 
                           " - Montant apporté: " . number_format($montantApporte, 2) . " USD" .
                           " - Dû: " . number_format($montantDuMembre, 2) . " USD",
            'reference' => $referenceGroupe,
            'date_mouvement' => $datePaiement,
            'nom_deposant' => $compteMembre->nom . ' ' . $compteMembre->prenom
        ]);
        
        Log::info("✅ Mouvement groupe créé - ID: {$mouvementGroupe->id}");
        // Après avoir créé le mouvement groupe, vérifier la cohérence
if (isset($mouvementGroupe)) {
    $soldeApresAttendu = $ancienSoldeGroupe - $montantPreleveGroupe;
    $soldeApresEnregistre = (float)$mouvementGroupe->solde_apres;
    
    if (abs($soldeApresAttendu - $soldeApresEnregistre) > 0.01) {
        Log::error("❌ INCOHÉRENCE DÉTECTÉE DANS MOUVEMENT GROUPE");
        Log::error("Solde après attendu: {$soldeApresAttendu} USD");
        Log::error("Solde après enregistré: {$soldeApresEnregistre} USD");
        Log::error("Différence: " . ($soldeApresAttendu - $soldeApresEnregistre) . " USD");
        
        // Corriger immédiatement
        $mouvementGroupe->solde_apres = $soldeApresAttendu;
        $mouvementGroupe->save();
        
        Log::info("✅ Mouvement corrigé: ID {$mouvementGroupe->id}");
    }
}     
    }
    
 // === PRÉLÈVEMENT DU MEMBRE (si complément) ===
if ($montantPreleveMembre > 0) {
    Log::info("=== PRÉLÈVEMENT MEMBRE ===");
    Log::info("Ancien solde membre: {$compteMembre->solde} USD");
    Log::info("Montant à prélever: {$montantPreleveMembre} USD");
    
    $ancienSoldeMembre = $compteMembre->solde;
    $nouveauSoldeMembre = $ancienSoldeMembre - $montantPreleveMembre;
    
    // Mettre à jour le solde directement avec DB::table pour éviter la validation
    DB::table('comptes')
        ->where('id', $compteMembre->id)
        ->update(['solde' => $nouveauSoldeMembre]);
    
    // Recharger le modèle
    $compteMembre->refresh();
    
    Log::info("✅ Nouveau solde membre: {$compteMembre->solde} USD");
    
    $referenceMembre = 'COMPL-MEMBRE-' . $membreId . '-GRP-' . $creditGroupe->id . '-' . now()->format('YmdHis');
    
    // Créer le mouvement avec DB::table pour éviter la validation
    DB::table('mouvements')->insert([
        'compte_id' => $compteMembre->id,
        'type' => 'retrait',
        'type_mouvement' => 'complement_paiement_groupe',
        'montant' => $montantPreleveMembre,
        'solde_avant' => $ancienSoldeMembre,
        'solde_apres' => $nouveauSoldeMembre,
        'description' => "Complément paiement crédit groupe - Montant: " . number_format($montantPreleveMembre, 2) . " USD" .
                       " - Dû total: " . number_format($montantDuMembre, 2) . " USD" .
                       " - Apporté: " . number_format($montantApporte, 2) . " USD",
        'reference' => $referenceMembre,
        'date_mouvement' => $datePaiement,
        'nom_deposant' => 'Système Automatique',
        'operateur_id' => Auth::id(),
        'numero_compte' => $compteMembre->numero_compte,
        'client_nom' => trim($compteMembre->nom . ' ' . $compteMembre->prenom),
        'created_at' => now(),
        'updated_at' => now(),
        'devise' => 'USD'
    ]);
}
    
    // === EXCÉDENT AU MEMBRE ===
    if ($montantExcedent > 0) {
        Log::info("=== EXCÉDENT AU MEMBRE ===");
        Log::info("Ancien solde membre: {$compteMembre->solde} USD");
        Log::info("Excédent à créditer: {$montantExcedent} USD");
        
        $ancienSoldeMembre = $compteMembre->solde;
        $compteMembre->solde += $montantExcedent;
        $compteMembre->save();
        
        Log::info("✅ Nouveau solde membre avec excédent: {$compteMembre->solde} USD");
        
        $referenceExcedent = 'EXCEDENT-GRP-' . $creditGroupe->id . '-MEMBRE-' . $membreId . '-' . now()->format('YmdHis');
        
        Mouvement::create([
            'compte_id' => $compteMembre->id,
            'type' => 'depot',
            'type_mouvement' => 'excedent_groupe',
            'montant' => $montantExcedent,
            'solde_avant' => $ancienSoldeMembre,
            'solde_apres' => $compteMembre->solde,
            'description' => "Excédent paiement crédit groupe - Montant: " . number_format($montantExcedent, 2) . " USD",
            'reference' => $referenceExcedent,
            'date_mouvement' => $datePaiement,
            'nom_deposant' => 'Système Automatique'
        ]);
    }

    // Avant "=== FIN TRAITEMENT ===", ajoutez :
Log::info("Résultat final:");
Log::info("Montant apporté: {$montantApporte} USD");
Log::info("Montant dû: {$montantDuMembre} USD");
Log::info("Prélèvement groupe: {$montantPreleveGroupe} USD");
Log::info("Prélèvement membre: {$montantPreleveMembre} USD");
Log::info("Excédent: {$montantExcedent} USD");
Log::info("Total payé: " . ($montantPreleveGroupe + $montantPreleveMembre) . " USD");
    
    // === RÉSUMÉ FINAL ===
    Log::info("=== RÉSUMÉ FINAL ===");
    Log::info("Solde groupe après: {$compteGroupe->solde} USD");
    Log::info("Solde membre après: {$compteMembre->solde} USD");
    Log::info("Montant apporté: {$montantApporte} USD");
    Log::info("Prélèvement groupe: {$montantPreleveGroupe} USD");
    Log::info("Prélèvement membre: {$montantPreleveMembre} USD");
    Log::info("Excédent: {$montantExcedent} USD");
    Log::info("=== FIN TRAITEMENT ===");
    
    // Préparer réponse
    $statut = 'succes';
    $raison = 'Paiement enregistré';
    
    if ($montantExcedent > 0) {
        $raison = 'Paiement avec excédent de ' . number_format($montantExcedent, 2) . ' USD';
    }
    
    if ($montantPreleveMembre > 0) {
        $raison = 'Paiement complété depuis compte membre: ' . number_format($montantPreleveMembre, 2) . ' USD';
    }
    
    return [
        'compte' => $compteMembre->numero_compte,
        'montant_apporte' => $montantApporte,
        'montant_preleve_groupe' => $montantPreleveGroupe,
        'montant_preleve_membre' => $montantPreleveMembre,
        'montant_du' => $montantDuMembre,
        'montant_excedent' => $montantExcedent,
        'statut' => $statut,
        'raison' => $raison,
        'nouveau_solde_membre' => $compteMembre->solde,
        'nouveau_solde_groupe' => $compteGroupe->solde,
        'membre_id' => $membreId
    ];
}    private function getCombinedCredits(): Collection
{
    $creditsIndividuels = Credit::where('statut_demande', 'approuve')
        ->with(['compte', 'agent', 'superviseur', 'paiements'])
        ->get()
        ->map(function ($credit) {
            $credit->total_paiements = $credit->paiements()
                ->where('type_paiement', '!=', TypePaiement::GROUPE->value)
                ->sum('montant_paye');
            return $credit;
        });

    $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
        ->with(['compte', 'agent', 'superviseur'])
        ->get()
        ->map(function ($creditGroupe) {
            // Calculer les paiements du groupe
            $totalPaiementsGroupe = PaiementCredit::where('credit_groupe_id', $creditGroupe->id)
                ->where('type_paiement', TypePaiement::GROUPE->value)
                ->sum('montant_paye');
            
            // Créer un modèle Credit factice pour les crédits groupe
            $credit = new Credit();
            $credit->id = $creditGroupe->id + 100000;
            $credit->compte_id = $creditGroupe->compte_id;
            $credit->agent_id = $creditGroupe->agent_id;
            $credit->superviseur_id = $creditGroupe->superviseur_id;
            $credit->type_credit = 'groupe';
            $credit->montant_demande = $creditGroupe->montant_demande;
            $credit->montant_accorde = $creditGroupe->montant_accorde;
            $credit->montant_total = $creditGroupe->montant_total;
            $credit->date_octroi = $creditGroupe->date_octroi;
            $credit->date_echeance = $creditGroupe->date_echeance;
            $credit->created_at = $creditGroupe->created_at;
            $credit->updated_at = $creditGroupe->updated_at;
            $credit->total_paiements = $totalPaiementsGroupe; // ← AJOUTER ICI
            
            // Ajouter les relations avec vérification
            if ($creditGroupe->relationLoaded('compte') && $creditGroupe->compte) {
                $credit->setRelation('compte', $creditGroupe->compte);
            } else {
                $compte = new Compte();
                $compte->numero_compte = 'GS' . str_pad($creditGroupe->id, 5, '0', STR_PAD_LEFT);
                $compte->nom = 'Groupe ' . $creditGroupe->id;
                $credit->setRelation('compte', $compte);
            }
            
            if ($creditGroupe->relationLoaded('agent') && $creditGroupe->agent) {
                $credit->setRelation('agent', $creditGroupe->agent);
            }
            
            if ($creditGroupe->relationLoaded('superviseur') && $creditGroupe->superviseur) {
                $credit->setRelation('superviseur', $creditGroupe->superviseur);
            }
            
            $credit->setRelation('paiements', collect());
            
            return $credit;
        });

    return $creditsIndividuels->merge($creditsGroupe)->sortByDesc('id');
}
    /**
     * Crée les mouvements comptables
     */
  /**
 * Crée les mouvements comptables
 */
private function creerMouvements($compteGroupe, $compteMembre, $montantPreleveGroupe, $montantDu, $montantExcedent, $creditGroupe, $membreId, $datePaiement)
{
    $reference = 'PAY-GRP-' . $creditGroupe->id . '-MEMBRE-' . $membreId . '-' . now()->format('YmdHis');

    // Mouvement 1: Débit du compte groupe
    Mouvement::create([
        'compte_id' => $compteGroupe->id,
        'type' => 'retrait',
        'type_mouvement' => 'paiement_credit_groupe',
        'montant' => $montantPreleveGroupe,
        'solde_avant' => $compteGroupe->solde + $montantPreleveGroupe,
        'solde_apres' => $compteGroupe->solde,
        'description' => "Collecte paiement crédit groupe - Membre: " . $compteMembre->numero_compte . 
                       " - Montant: " . number_format($montantPreleveGroupe, 2) . " USD",
        'reference' => $reference,
        'date_mouvement' => $datePaiement,
        'nom_deposant' => $compteMembre->nom . ' ' . $compteMembre->prenom
    ]);
}

    /**
     * Effectue le paiement sur le compte groupe
     */
    private function effectuerPaiementGroupe($creditGroupe, $montantPaiement, $datePaiement)
    {
        // CORRECTION : Répartir le montant entre capital et intérêts
        $repartition = $this->repartirCapitalInteretsGroupe($creditGroupe, $montantPaiement);
        
        // Enregistrer le paiement
        $paiement = PaiementCredit::create([
            'credit_id' => null,
            'credit_groupe_id' => $creditGroupe->id,
            'compte_id' => $creditGroupe->compte_id,
            'montant_paye' => $montantPaiement,
            'date_paiement' => $datePaiement,
            'type_paiement' => TypePaiement::GROUPE->value,
            'reference' => 'PAY-GROUPE-' . $creditGroupe->id . '-' . now()->format('YmdHis'),
            'statut' => 'complet',
            'capital_rembourse' => $repartition['capital'],
            'interets_payes' => $repartition['interets']
        ]);

        // Générer les écritures comptables
        $this->genererEcritureComptablePaiementGroupe(
            $creditGroupe->compte, 
            $creditGroupe, 
            $montantPaiement, 
            $repartition, 
            $paiement->reference
        );
        
        // Mettre à jour l'échéancier
        $this->mettreAJourEcheancier($creditGroupe, $paiement);
        
        return $paiement;
    }

    /**
     * CORRECTION : Met à jour l'échéancier après paiement
     */
    private function mettreAJourEcheancier($creditGroupe, $paiement)
    {
        // Trouver la prochaine échéance non payée
        $echeance = DB::table('echeanciers')
            ->where('credit_groupe_id', $creditGroupe->id)
            ->where('statut', 'a_venir')
            ->orderBy('semaine', 'asc')
            ->first();
            
        if ($echeance) {
            DB::table('echeanciers')
                ->where('id', $echeance->id)
                ->update([
                    'statut' => 'paye',
                    'date_paiement' => $paiement->date_paiement,
                    'montant_paye' => $paiement->montant_paye,
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Récupère les groupes avec des crédits actifs
     */
 private function getGroupesAvecCreditsActifs()
    {
        return CreditGroupe::where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->where('date_echeance', '>=', now())
            ->with(['compte']) // ← ENLEVER 'paiements' ici
            ->get()
            ->map(function ($creditGroupe) {
                // Calculer les valeurs sans utiliser les relations
                $creditGroupe->montant_restant = $this->calculerMontantRestantGroupe($creditGroupe);
                $creditGroupe->total_deja_paye = $this->calculerTotalDejaPaye($creditGroupe);
                $creditGroupe->capital_rembourse_total = $this->calculerCapitalRembourseTotal($creditGroupe);
                $creditGroupe->remboursement_hebdo_total = $this->calculerRemboursementHebdoTotal($creditGroupe);
                $creditGroupe->semaine_actuelle = $this->getSemaineActuelle($creditGroupe);
                $creditGroupe->montant_du_jusqu_present = $this->calculerMontantDuJusquPresent($creditGroupe);
                $creditGroupe->membres_avec_soldes = $this->getMembresAvecSoldes($creditGroupe);
                return $creditGroupe;
            });
    }

      private function calculerMontantRestantGroupe($creditGroupe): float
    {
        $totalPaye = $this->calculerTotalDejaPaye($creditGroupe);
        return max(0, floatval($creditGroupe->montant_total) - $totalPaye);
    }

      private function calculerTotalDejaPaye($creditGroupe): float
    {
        return PaiementCredit::where('credit_groupe_id', $creditGroupe->id)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->sum('montant_paye');
    }


      private function calculerCapitalRembourseTotal($creditGroupe): float
    {
        return PaiementCredit::where('credit_groupe_id', $creditGroupe->id)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->sum('capital_rembourse');
    }

    private function calculerRemboursementHebdoTotal($creditGroupe): float
    {
        return floatval($creditGroupe->montant_total) / 16;
    }

    private function getSemaineActuelle($creditGroupe): int
    {
        if (!$creditGroupe->date_octroi) {
            return 1;
        }

        $dateDebut = $creditGroupe->date_octroi->copy()->addWeeks(2);
        
        if (now()->lt($dateDebut)) {
            return 0;
        }
        
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        
        return min($semainesEcoulees + 1, 16);
    }

    private function calculerMontantDuJusquPresent($creditGroupe): float
    {
        $semaineActuelle = $this->getSemaineActuelle($creditGroupe);
        
        if ($semaineActuelle <= 0) {
            return 0;
        }
        
        return $this->calculerRemboursementHebdoTotal($creditGroupe) * min($semaineActuelle, 16);
    }

    /**
     * Répartit le montant payé entre capital et intérêts pour un groupe
     */
    private function repartirCapitalInteretsGroupe($creditGroupe, $montantPaiement)
    {
        // Calculer les parts hebdomadaires
        $capitalHebdomadaire = $creditGroupe->montant_accorde / 16;
        $interetHebdomadaire = ($creditGroupe->montant_total - $creditGroupe->montant_accorde) / 16;
        $montantHebdomadaireTotal = $capitalHebdomadaire + $interetHebdomadaire;
        
        // Si paiement complet ou supérieur au dû hebdomadaire
        if ($montantPaiement >= $montantHebdomadaireTotal) {
            return [
                'capital' => $capitalHebdomadaire,
                'interets' => $interetHebdomadaire,
                'excédent' => $montantPaiement - $montantHebdomadaireTotal
            ];
        }
        
        // Si paiement partiel : priorité aux intérêts
        $interetsAPayer = min($montantPaiement, $interetHebdomadaire);
        $capitalAPayer = max(0, $montantPaiement - $interetsAPayer);
        
        return [
            'capital' => $capitalAPayer,
            'interets' => $interetsAPayer,
            'excédent' => 0
        ];
    }

    /**
     * Récupère les membres avec leurs soldes
     */
    private function getMembresAvecSoldes($creditGroupe): array
    {
        $membres = [];
        $repartition = $creditGroupe->repartition_membres ?? [];

        foreach ($repartition as $membreId => $details) {
            $compteMembre = Compte::where('client_id', $membreId)->first();
            
            if ($compteMembre) {
                $soldeDisponible = $this->calculerSoldeDisponible($compteMembre->id);
                $montantDu = $details['remboursement_hebdo'] ?? 0;

                $membres[] = [
                    'membre_id' => $membreId,
                    'nom_complet' => $compteMembre->nom . ' ' . $compteMembre->prenom,
                    'numero_compte' => $compteMembre->numero_compte,
                    'solde_disponible' => $soldeDisponible,
                    'montant_du' => $montantDu,
                    'montant_accorde' => $details['montant_accorde'] ?? 0,
                    'montant_total' => $details['montant_total'] ?? 0,
                ];
            }
        }

        return $membres;
    }

    /**
     * Calcule le solde disponible (hors caution)
     */
    private function calculerSoldeDisponible($compteId): float
    {
        $compte = Compte::find($compteId);
        $caution = DB::table('cautions')
            ->where('compte_id', $compteId)
            ->where('statut', 'bloquee')
            ->sum('montant');
        
        return max(0, $compte->solde - $caution);
    }

    /**
     * Génère l'écriture comptable pour le paiement groupe
     */
    private function genererEcritureComptablePaiementGroupe($compteGroupe, $creditGroupe, $montantApplique, $repartition, $reference)
    {
        $journal = JournalComptable::where('type_journal', 'banque')->first();
        
        if (!$journal) {
            Log::warning('Journal banque non trouvé pour écriture comptable groupe');
            return;
        }

        // 1. DÉBIT - Remboursement capital (compte 411100)
        if ($repartition['capital'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'remboursement_capital_groupe',
                'compte_number' => '411100',
                'libelle' => "Remboursement capital crédit groupe - " . ($compteGroupe->nom ?? 'Groupe'),
                'montant_debit' => $repartition['capital'],
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }

        // 2. DÉBIT - Paiement intérêts (compte 411100)
        if ($repartition['interets'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'paiement_interets_groupe',
                'compte_number' => '411100',
                'libelle' => "Paiement intérêts crédit groupe - " . ($compteGroupe->nom ?? 'Groupe'),
                'montant_debit' => $repartition['interets'],
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }

        // 3. CRÉDIT - Recouvrement capital (compte 751100)
        if ($repartition['capital'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'recouvrement_capital_groupe',
                'compte_number' => '751100',
                'libelle' => "Recouvrement capital crédit groupe - " . ($compteGroupe->nom ?? 'Groupe'),
                'montant_debit' => 0,
                'montant_credit' => $repartition['capital'],
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }

        // 4. CRÉDIT - Revenus intérêts (compte 758100)
        if ($repartition['interets'] > 0) {
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'revenus_interets_groupe',
                'compte_number' => '758100',
                'libelle' => "Revenus intérêts crédit groupe - " . ($compteGroupe->nom ?? 'Groupe'),
                'montant_debit' => 0,
                'montant_credit' => $repartition['interets'],
                'date_ecriture' => now(),
                'devise' => 'USD',
                'statut' => 'comptabilise',
            ]);
        }
    }
}