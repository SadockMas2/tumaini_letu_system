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
        ]);

        try {
            DB::transaction(function () use ($request) {
                $creditGroupe = CreditGroupe::with(['compte'])->findOrFail($request->selected_groupe_id);
                $datePaiement = now();
                $results = [];
                $totalPaiementGroupe = 0;

                foreach ($request->paiements_membres as $membreId => $montantApporte) {
                    $montantApporte = floatval($montantApporte);
                    if ($montantApporte > 0) {
                        $result = $this->traiterPaiementMembreGroupe($membreId, $montantApporte, $creditGroupe, $datePaiement);
                        $results[] = $result;
                        $totalPaiementGroupe += $result['montant_preleve_groupe'] ?? 0;
                    }
                }

                // CORRECTION : Utiliser la nouvelle méthode pour traiter par membre
                $this->traiterPaiementsParMembre($creditGroupe, $results, $datePaiement);

                // Stocker les résultats dans la session pour affichage
                session()->flash('paiement_success', true);
                session()->flash('results', $results);
                session()->flash('total_paiement_groupe', $totalPaiementGroupe);
                session()->flash('credit_groupe_nom', $creditGroupe->compte->nom ?? 'Groupe');
                
                // Ajouter les totaux pour affichage
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
  private function traiterPaiementsMultiples($creditGroupe, $montantTotalApporte, $datePaiement)
    {
        $montantHebdomadaireTotal = $creditGroupe->remboursement_hebdo_total; // 76.56 USD pour le groupe
        
        // CORRECTION : Pour chaque membre, traiter individuellement son paiement
        // On ne peut pas simplement diviser le total par le montant hebdo
        
        // D'abord, traiter le paiement hebdomadaire normal
        if ($montantTotalApporte >= $montantHebdomadaireTotal) {
            $this->effectuerPaiementGroupe($creditGroupe, $montantHebdomadaireTotal, $datePaiement);
            
            // Calculer l'excédent après le paiement hebdomadaire
            $resteApresRemboursement = $montantTotalApporte - $montantHebdomadaireTotal;
            
            // Si reste > 0, c'est un excédent qui ira dans les comptes membres
            if ($resteApresRemboursement > 0) {
                $this->distribuerExcedentMembres($creditGroupe, $resteApresRemboursement, $datePaiement);
            }
        } else {
            // Si le montant total est inférieur au remboursement hebdo
            // C'est un paiement partiel, pas d'excédent
            $this->effectuerPaiementGroupe($creditGroupe, $montantTotalApporte, $datePaiement);
        }
    }


     private function traiterPaiementsParMembre($creditGroupe, $results, $datePaiement)
    {
        $repartition = $creditGroupe->repartition_membres ?? [];
        $montantTotalApporte = 0;
        $excedentsParMembre = [];
        
        // 1. Calculer les excédents par membre
        foreach ($results as $result) {
            $membreId = $result['membre_id'] ?? null;
            $montantApporte = $result['montant_apporte'] ?? 0;
            $montantDuMembre = $result['montant_du'] ?? 0;
            
            if ($membreId && isset($repartition[$membreId])) {
                // Calculer l'excédent pour ce membre
                $excedentMembre = max(0, $montantApporte - $montantDuMembre);
                
                if ($excedentMembre > 0) {
                    $excedentsParMembre[$membreId] = $excedentMembre;
                }
                
                // Le montant appliqué au remboursement est le minimum
                $montantAppliqueAuRemboursement = min($montantApporte, $montantDuMembre);
                $montantTotalApporte += $montantAppliqueAuRemboursement;
            }
        }
        
        // 2. Effectuer le paiement du groupe avec le total des remboursements
        if ($montantTotalApporte > 0) {
            $this->effectuerPaiementGroupe($creditGroupe, $montantTotalApporte, $datePaiement);
        }
        
        // 3. Distribuer les excédents aux membres
        if (!empty($excedentsParMembre)) {
            $this->distribuerExcedentsMembresExact($creditGroupe, $excedentsParMembre, $datePaiement);
        }
    }

    /**
     * NOUVELLE MÉTHODE : Distribue les excédents exacts aux membres
     */
    private function distribuerExcedentsMembresExact($creditGroupe, $excedentsParMembre, $datePaiement)
    {
        foreach ($excedentsParMembre as $membreId => $excedent) {
            if ($excedent > 0) {
                $compteMembre = Compte::where('client_id', $membreId)->first();
                if ($compteMembre) {
                    // Créditer le compte membre avec l'excédent exact
                    $ancienSolde = $compteMembre->solde;
                    $compteMembre->solde += $excedent;
                    $compteMembre->save();
                    
                    // Créer un mouvement
                    $reference = 'EXCEDENT-EXACT-GRP-' . $creditGroupe->id . '-MEMBRE-' . $membreId . '-' . now()->format('YmdHis');
                    
                    Mouvement::create([
                        'compte_id' => $compteMembre->id,
                        'type_mouvement' => 'excedent_groupe_exact',
                        'montant' => $excedent,
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $compteMembre->solde,
                        'description' => "Excédent exact remboursement crédit groupe - Montant: " . number_format($excedent, 2) . " USD",
                        'reference' => $reference,
                        'date_mouvement' => $datePaiement,
                        'nom_deposant' => 'Système Automatique'
                    ]);
                    
                    Log::info("💰 Excédent exact distribué au membre", [
                        'membre_id' => $membreId,
                        'montant_excedent' => $excedent,
                        'nouveau_solde' => $compteMembre->solde
                    ]);
                }
            }
        }
    }



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
     * Traite le paiement d'un membre avec prélèvement du compte groupe
     */private function traiterPaiementMembreGroupe($membreId, $montantApporte, $creditGroupe, $datePaiement)
    {
        $compteMembre = Compte::where('client_id', $membreId)->first();
        $compteGroupe = $creditGroupe->compte;
        
        if (!$compteMembre) {
            return [
                'compte' => 'Membre ' . $membreId,
                'montant_apporte' => $montantApporte,
                'montant_preleve_groupe' => 0,
                'montant_du' => 0,
                'montant_excedent' => 0,
                'statut' => 'echec',
                'raison' => 'Compte membre non trouvé',
                'membre_id' => $membreId // ← AJOUTER pour identifier le membre
            ];
        }

        // Récupérer les détails du membre depuis la répartition
        $repartition = $creditGroupe->repartition_membres ?? [];
        $detailsMembre = $repartition[$membreId] ?? [];
        $montantDuMembre = $detailsMembre['remboursement_hebdo'] ?? 0;
        
        // Vérifier que le compte groupe a suffisamment de solde
        $soldeGroupe = $compteGroupe->solde;
        
        if ($soldeGroupe < $montantApporte) {
            return [
                'compte' => $compteMembre->numero_compte,
                'montant_apporte' => $montantApporte,
                'montant_preleve_groupe' => 0,
                'montant_du' => $montantDuMembre,
                'montant_excedent' => 0,
                'statut' => 'echec',
                'raison' => 'Solde insuffisant dans le compte groupe',
                'membre_id' => $membreId
            ];
        }

        // Calculer l'excédent exact pour ce membre
        $montantExcedent = max(0, $montantApporte - $montantDuMembre);
        
        // Débiter le compte groupe
        $ancienSoldeGroupe = $compteGroupe->solde;
        $compteGroupe->solde -= $montantApporte;
        $compteGroupe->save();

        // Créer les mouvements comptables
        $this->creerMouvements($compteGroupe, $compteMembre, $montantApporte, $montantDuMembre, $montantExcedent, $creditGroupe, $membreId, $datePaiement);

        $statut = 'succes';
        $raison = 'Paiement enregistré';
        
        if ($montantExcedent > 0) {
            $raison = 'Paiement avec excédent - ' . number_format($montantExcedent, 2) . ' USD';
        }

        return [
            'compte' => $compteMembre->numero_compte,
            'montant_apporte' => $montantApporte,
            'montant_preleve_groupe' => $montantApporte,
            'montant_du' => $montantDuMembre,
            'montant_excedent' => $montantExcedent,
            'statut' => $statut,
            'raison' => $raison,
            'nouveau_solde_membre' => $compteMembre->solde,
            'nouveau_solde_groupe' => $compteGroupe->solde,
            'membre_id' => $membreId // ← TRÈS IMPORTANT pour identifier le membre
        ];
    }


    private function getCombinedCredits(): Collection
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
    private function creerMouvements($compteGroupe, $compteMembre, $montantApporte, $montantDu, $montantExcedent, $creditGroupe, $membreId, $datePaiement)
    {
        $reference = 'PAY-GRP-' . $creditGroupe->id . '-MEMBRE-' . $membreId . '-' . now()->format('YmdHis');

        // Mouvement 1: Débit du compte groupe
        Mouvement::create([
            'compte_id' => $compteGroupe->id,
            'type_mouvement' => 'paiement_credit_groupe',
            'montant' => -$montantApporte,
            'solde_avant' => $compteGroupe->solde + $montantApporte,
            'solde_apres' => $compteGroupe->solde,
            'description' => "Collecte paiement crédit groupe - Membre: " . $compteMembre->numero_compte . 
                           " - Montant: " . number_format($montantApporte, 2) . " USD",
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