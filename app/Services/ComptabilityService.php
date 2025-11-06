<?php
// app/Services/ComptabilityService.php

namespace App\Services;

use App\Models\Cycle;
use App\Models\EcritureComptable;
use App\Models\Epargne;
use App\Models\JournalComptable;
use App\Models\MouvementCoffre;
use App\Models\Caisse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComptabilityService
{
      const COMPTE_CAISSE = '571100';
    const COMPTE_BANQUE = '521100';
    const COMPTE_COFFRE_FORT = '571200';
    const COMPTE_PETITE_CAISSE = '571300';
    const COMPTE_CHARGES_DIVERSES = '658100';
    const COMPTE_PRODUITS_DIVERS = '758100';
    const COMPTE_TRANSIT_TRESORERIE = '511100'; // Compte de transit


     private function initialiserJournaux()
    {
        $journaux = [
            [
                'code_journal' => 'BNQ',
                'libelle_journal' => 'Journal de Banque',
                'type_journal' => 'banque',
                'agence_id' => 1, // ID de l'agence par défaut
                'responsable_id' => Auth::id(), // Utilisateur connecté
                'date_ouverture' => now(),
                'solde_initial' => 0,
                'solde_final' => 0,
                'statut' => 'ouvert'
            ],
            [
                'code_journal' => 'CAI',
                'libelle_journal' => 'Journal de Caisse',
                'type_journal' => 'caisse',
                'agence_id' => 1,
                'responsable_id' => Auth::id(),
                'date_ouverture' => now(),
                'solde_initial' => 0,
                'solde_final' => 0,
                'statut' => 'ouvert'
            ],
            [
                'code_journal' => 'ACH',
                'libelle_journal' => 'Journal des Achats',
                'type_journal' => 'achats',
                'agence_id' => 1,
                'responsable_id' => Auth::id(),
                'date_ouverture' => now(),
                'solde_initial' => 0,
                'solde_final' => 0,
                'statut' => 'ouvert'
            ],
            [
                'code_journal' => 'VTE',
                'libelle_journal' => 'Journal des Ventes',
                'type_journal' => 'ventes',
                'agence_id' => 1,
                'responsable_id' => Auth::id(),
                'date_ouverture' => now(),
                'solde_initial' => 0,
                'solde_final' => 0,
                'statut' => 'ouvert'
            ]
        ];

        foreach ($journaux as $journal) {
            JournalComptable::firstOrCreate(
                ['code_journal' => $journal['code_journal']],
                $journal
            );
        }
    }
    private function getJournal(string $typeJournal): JournalComptable
    {
        Log::info("Recherche journal", ['type_journal' => $typeJournal]);

        $journal = JournalComptable::where('type_journal', $typeJournal)->first();

        if (!$journal) {
            Log::warning("Journal non trouvé, tentative d'initialisation", ['type_journal' => $typeJournal]);
            $this->initialiserJournaux();
            
            $journal = JournalComptable::where('type_journal', $typeJournal)->first();
            
            if (!$journal) {
                $message = "Journal comptable de type '{$typeJournal}' introuvable même après initialisation. Journaux disponibles: " . 
                          JournalComptable::pluck('type_journal')->implode(', ');
                Log::error($message);
                throw new \Exception($message);
            }
        }

        Log::info("Journal trouvé", [
            'journal_id' => $journal->id,
            'code_journal' => $journal->code_journal,
            'type_journal' => $journal->type_journal
        ]);

        return $journal;
    }


    /**
     * Enregistre l'alimentation du coffre depuis banque/partenaire
     */
   public function enregistrerAlimentationCoffre($mouvementCoffreId, $reference)
    {
        return DB::transaction(function () use ($mouvementCoffreId, $reference) {
            $mouvement = MouvementCoffre::findOrFail($mouvementCoffreId);
            $journal = $this->getJournal('banque');

            // Débit: Coffre fort
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'banque_vers_coffre',
                'compte_number' => self::COMPTE_COFFRE_FORT,
                'libelle' => "Alimentation coffre - Ref: {$reference}",
                'montant_debit' => $mouvement->montant,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'created_by' => Auth::id(),
            ]);

            // Crédit: Banque
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'banque_vers_coffre',
                'compte_number' => self::COMPTE_BANQUE,
                'libelle' => "Alimentation coffre - Ref: {$reference}",
                'montant_debit' => 0,
                'montant_credit' => $mouvement->montant,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'created_by' => Auth::id(),
            ]);

            return $mouvement;
        });
    }


    /**
     * Enregistre le transfert du coffre vers la comptabilité
     */
    public function enregistrerTransfertCoffreVersComptable($mouvementCoffreId)
    {
        return DB::transaction(function () use ($mouvementCoffreId) {
            $mouvement = MouvementCoffre::findOrFail($mouvementCoffreId);
            $journal = $this->getJournal('caisse');

            // Débit: Compte de transit trésorerie
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $mouvement->reference,
                'type_operation' => 'coffre_vers_tresorerie',
                'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
                'libelle' => $mouvement->description,
                'montant_debit' => $mouvement->montant,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'created_by' => Auth::id(),
            ]);

            // Crédit: Coffre fort
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $mouvement->reference,
                'type_operation' => 'coffre_vers_tresorerie',
                'compte_number' => self::COMPTE_COFFRE_FORT,
                'libelle' => $mouvement->description,
                'montant_debit' => 0,
                'montant_credit' => $mouvement->montant,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'created_by' => Auth::id(),
            ]);

            return $mouvement;
        });
    }

    /**
     * Distribution des fonds aux caisses depuis le compte de transit
     */
 public function distribuerAuxCaisses(array $distributions, string $reference, string $devise)
{
    return DB::transaction(function () use ($distributions, $reference, $devise) {
        
        Log::info("Début distribution aux caisses", [
            'distributions' => $distributions,
            'reference' => $reference,
            'devise' => $devise
        ]);

        try {
            // Récupérer le journal de caisse
            $journal = $this->getJournal('caisse');
            Log::info("Journal récupéré", ['journal_id' => $journal->id, 'journal_code' => $journal->code_journal]);

            // CORRECTION : Calculer le total correctement
            $totalDistribue = array_sum($distributions);
            Log::info("Total à distribuer", ['total' => $totalDistribue]);

            // Vérifier que le compte transit a suffisamment de fonds
            $soldeTransit = $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, $devise);
            Log::info("Solde compte transit", ['solde' => $soldeTransit, 'devise' => $devise]);

            if ($soldeTransit < $totalDistribue) {
                throw new \Exception("Fonds insuffisants dans le compte de transit. Solde disponible: {$soldeTransit} {$devise}");
            }

            // CORRECTION : Parcourir les distributions par ID de caisse
            foreach ($distributions as $caisseId => $montant) {
                if ($montant > 0) {
                    Log::info("Traitement caisse", ['caisse_id' => $caisseId, 'montant' => $montant]);

                    // Récupérer la caisse spécifique par ID
                    $caisse = Caisse::find($caisseId);
                    if (!$caisse) {
                        throw new \Exception("Caisse avec ID {$caisseId} non trouvée");
                    }

                    Log::info("Caisse récupérée", [
                        'caisse_id' => $caisse->id,
                        'nom' => $caisse->nom,
                        'type' => $caisse->type_caisse,
                        'solde_avant' => $caisse->solde,
                        'plafond' => $caisse->plafond
                    ]);

                    // Vérifier que la caisse peut recevoir le montant
                    $nouveauSolde = $caisse->solde + $montant;
                    if ($nouveauSolde > $caisse->plafond) {
                        throw new \Exception("Le plafond de la caisse {$caisse->nom} serait dépassé. Plafond: {$caisse->plafond}, Nouveau solde: {$nouveauSolde}");
                    }

                    // Vérifier que la devise correspond
                    if ($caisse->devise !== $devise) {
                        throw new \Exception("La devise de la caisse {$caisse->nom} ({$caisse->devise}) ne correspond pas à la devise de distribution ({$devise})");
                    }

                    // Alimenter la caisse
                    $ancienSolde = $caisse->solde;
                    $caisse->solde += $montant;
                    $caisse->save();
                    Log::info("Caisse alimentée", [
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $caisse->solde
                    ]);

                    // CORRECTION : Déterminer le compte en fonction du type de caisse
                    $compteCaisse = $caisse->type_caisse === 'petite_caisse' 
                        ? self::COMPTE_PETITE_CAISSE 
                        : self::COMPTE_CAISSE;

                    Log::info("Création écriture comptable", ['compte' => $compteCaisse]);

                    // Débit: Compte de caisse spécifique
                    EcritureComptable::create([
                        'journal_comptable_id' => $journal->id,
                        'reference_operation' => $reference,
                        'type_operation' => 'distribution_caisses',
                        'compte_number' => $compteCaisse,
                        'libelle' => "Distribution {$caisse->type_caisse} - {$caisse->nom}",
                        'montant_debit' => $montant,
                        'montant_credit' => 0,
                        'date_ecriture' => now(),
                        'date_valeur' => now(),
                        'devise' => $devise,
                        'statut' => 'comptabilise',
                        'created_by' => Auth::id(),
                    ]);

                    Log::info("Écriture créée pour caisse");

                    // Enregistrer le mouvement dans la caisse
                    \App\Models\Mouvement::create([
                        'caisse_id' => $caisse->id,
                        'type' => 'depot',
                        'montant' => $montant,
                        'solde_avant' => $ancienSolde,
                        'solde_apres' => $caisse->solde,
                        'description' => "Distribution depuis comptabilité - Ref: {$reference}",
                        'nom_deposant' => 'Comptabilité',
                        'operateur_id' => Auth::id(),
                        'compte_number' => $compteCaisse,
                        'devise' => $devise
                    ]);

                    Log::info("Mouvement créé pour caisse");
                }
            }

            Log::info("Création écriture de crédit pour compte transit");

            // Crédit: Compte de transit (total distribué)
            EcritureComptable::create([
                'journal_comptable_id' => $journal->id,
                'reference_operation' => $reference,
                'type_operation' => 'distribution_caisses',
                'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
                'libelle' => "Distribution aux caisses - Ref: {$reference}",
                'montant_debit' => 0,
                'montant_credit' => $totalDistribue,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $devise,
                'statut' => 'comptabilise',
                'created_by' => Auth::id(),
            ]);

            Log::info("Distribution terminée avec succès", ['total_distribue' => $totalDistribue]);

            return $totalDistribue;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la distribution aux caisses", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    });
}
    private function creerOuRecupererCaisse(string $typeCaisse, string $devise): Caisse
    {
        Log::info("Recherche caisse", ['type_caisse' => $typeCaisse, 'devise' => $devise]);

        $caisse = Caisse::where('type_caisse', $typeCaisse)->first();

        if (!$caisse) {
            Log::info("Caisse non trouvée, création", ['type_caisse' => $typeCaisse]);
            
            $config = $this->getConfigCaisse($typeCaisse);
            
            $caisse = Caisse::create([
                'type_caisse' => $typeCaisse,
                'nom' => $config['nom'],
                'devise' => $devise,
                'solde' => 0,
                'plafond' => $config['plafond'],
                'statut' => 'actif'
            ]);

            Log::info("Caisse créée", ['caisse_id' => $caisse->id, 'nom' => $caisse->nom]);
        } else {
            Log::info("Caisse existante trouvée", ['caisse_id' => $caisse->id, 'nom' => $caisse->nom]);
        }

        return $caisse;
    }

    // Dans ComptabilityService.php - Ajouter cette méthode

public function enregistrerRetourVersCoffre($mouvementCoffreId, $reference)
{
    return DB::transaction(function () use ($mouvementCoffreId, $reference) {
        $mouvement = MouvementCoffre::findOrFail($mouvementCoffreId);
        $journal = $this->getJournal('banque');

        // Débit: Coffre fort
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'retour_vers_coffre',
            'compte_number' => self::COMPTE_COFFRE_FORT,
            'libelle' => "Retour vers coffre - Ref: {$reference}",
            'montant_debit' => $mouvement->montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $mouvement->devise,
            'statut' => 'comptabilise',
            'created_by' => auth::id(),
        ]);

        // Crédit: Compte de transit trésorerie
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'retour_vers_coffre',
            'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
            'libelle' => "Retour vers coffre - Ref: {$reference}",
            'montant_debit' => 0,
            'montant_credit' => $mouvement->montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $mouvement->devise,
            'statut' => 'comptabilise',
            'created_by' => auth::id(),
        ]);

        return $mouvement;
    });
}


     private function getConfigCaisse(string $typeCaisse): array
    {
        return match($typeCaisse) {
            'petite_caisse' => [
                'nom' => 'Petite Caisse Opérations',
                'plafond' => 5000 // Plafond de 5000 USD
            ],
            'grande_caisse' => [
                'nom' => 'Grande Caisse Principale', 
                'plafond' => 50000 // Plafond de 50,000 USD
            ],
            default => [
                'nom' => 'Caisse ' . ucfirst($typeCaisse),
                'plafond' => 10000
            ]
        };
    }


    /**
     * Récupère le solde d'un compte comptable
     */
       public function getSoldeCompte(string $compteNumber, string $devise): float
    {
        $debits = EcritureComptable::where('compte_number', $compteNumber)
            ->where('devise', $devise)
            ->where('statut', 'comptabilise')
            ->sum('montant_debit');

        $credits = EcritureComptable::where('compte_number', $compteNumber)
            ->where('devise', $devise)
            ->where('statut', 'comptabilise')
            ->sum('montant_credit');

        return $debits - $credits;
    }


    /**
     * Vérifie les fonds disponibles pour distribution
     */
    public function getFondsDisponiblesTresorerie(string $devise = 'USD'): float
    {
        return $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, $devise);
    }

      public function getEtatComptes(): array
    {
        return [
            'transit_usd' => $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'USD'),
            'transit_cdf' => $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'CDF'),
            'coffre_usd' => $this->getSoldeCompte(self::COMPTE_COFFRE_FORT, 'USD'),
            'coffre_cdf' => $this->getSoldeCompte(self::COMPTE_COFFRE_FORT, 'CDF'),
            'banque_usd' => $this->getSoldeCompte(self::COMPTE_BANQUE, 'USD'),
            'banque_cdf' => $this->getSoldeCompte(self::COMPTE_BANQUE, 'CDF'),
            'caisse_usd' => $this->getSoldeCompte(self::COMPTE_CAISSE, 'USD'),
            'caisse_cdf' => $this->getSoldeCompte(self::COMPTE_CAISSE, 'CDF'),
            'petite_caisse_usd' => $this->getSoldeCompte(self::COMPTE_PETITE_CAISSE, 'USD'),
            'petite_caisse_cdf' => $this->getSoldeCompte(self::COMPTE_PETITE_CAISSE, 'CDF'),
        ];
    }

    public function enregistrerDepenseComptable($caisseId, $montant, $devise, $compteCharge, $libelle, $beneficiaire)
    {
        $caisse = Caisse::findOrFail($caisseId);
        $journal = JournalComptable::where('type_journal', 'achats')->first();

        // Débit: Compte de charge
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => 'DEP-' . now()->format('YmdHis'),
            'type_operation' => 'depense',
            'compte_number' => $compteCharge,
            'libelle' => "{$libelle} - {$beneficiaire}",
            'montant_debit' => $montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Caisse
        $compteCaisse = $caisse->type_caisse === 'petite_caisse' 
            ? self::COMPTE_PETITE_CAISSE 
            : self::COMPTE_CAISSE;

        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => 'DEP-' . now()->format('YmdHis'),
            'type_operation' => 'depense',
            'compte_number' => $compteCaisse,
            'libelle' => "{$libelle} - {$beneficiaire}",
            'montant_debit' => 0,
            'montant_credit' => $montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    public function enregistrerProduit($montant, $devise, $compteProduit, $libelle, $reference)
    {
        $journal = JournalComptable::where('type_journal', 'ventes')->first();

        // Débit: Caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'produit',
            'compte_number' => self::COMPTE_CAISSE,
            'libelle' => $libelle,
            'montant_debit' => $montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte de produit
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'produit',
            'compte_number' => $compteProduit,
            'libelle' => $libelle,
            'montant_debit' => 0,
            'montant_credit' => $montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);
    }

    // Dans ComptabilityService.php - Ajouter ces méthodes

/**
 * Enregistre l'écriture comptable pour l'ouverture d'un cycle
 */
public function enregistrerOuvertureCycle(Cycle $cycle): void
{
    DB::transaction(function () use ($cycle) {
        $journal = $this->getJournal('ventes'); // Ou 'operations' selon votre plan
        
        $reference = 'CYCLE-' . $cycle->numero_cycle . '-' . now()->format('Ymd');
        
        // Débit: Compte de produits d'épargne (compte produit)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'ouverture_cycle',
            'compte_number' => '758200', // Compte produits épargne - à adapter
            'libelle' => "Ouverture cycle {$cycle->numero_cycle} - {$cycle->client_nom}",
            'montant_debit' => $cycle->solde_initial,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $cycle->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte spécial du cycle
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'ouverture_cycle',
            'compte_number' => '511300', // Compte spécial cycles - à adapter
            'libelle' => "Ouverture cycle {$cycle->numero_cycle} - {$cycle->client_nom}",
            'montant_debit' => 0,
            'montant_credit' => $cycle->solde_initial,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $cycle->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        Log::info("Écriture comptable créée pour l'ouverture du cycle", [
            'cycle_id' => $cycle->id,
            'reference' => $reference,
            'montant' => $cycle->solde_initial
        ]);
    });
}

/**
 * Enregistre l'écriture comptable pour une épargne individuelle ou de groupe
 */
public function enregistrerEpargne(Epargne $epargne): void
{
    DB::transaction(function () use ($epargne) {
        $journal = $this->getJournal('ventes');
        
        $typeEpargne = $epargne->type_epargne === 'individuel' ? 'Individuelle' : 'Groupe';
        $reference = 'EPARGNE-' . $epargne->id . '-' . now()->format('Ymd');
        
        // Débit: Compte de produits d'épargne
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depot_epargne',
            'compte_number' => '758200', // Compte produits épargne
            'libelle' => "Épargne {$typeEpargne} - {$epargne->client_nom} - Cycle {$epargne->cycle->numero_cycle}",
            'montant_debit' => $epargne->montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $epargne->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte spécial du cycle
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depot_epargne',
            'compte_number' => '511300', // Compte spécial cycles
            'libelle' => "Épargne {$typeEpargne} - {$epargne->client_nom} - Cycle {$epargne->cycle->numero_cycle}",
            'montant_debit' => 0,
            'montant_credit' => $epargne->montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $epargne->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        Log::info("Écriture comptable créée pour l'épargne", [
            'epargne_id' => $epargne->id,
            'type' => $epargne->type_epargne,
            'montant' => $epargne->montant
        ]);
    });
}

/**
 * Enregistre la clôture d'un cycle
 */
public function enregistrerClotureCycle(Cycle $cycle): void
{
    DB::transaction(function () use ($cycle) {
        $journal = $this->getJournal('ventes');
        
        $soldeTotal = $cycle->solde_initial + $cycle->epargnes()->where('statut', 'valide')->sum('montant');
        $reference = 'CLOTURE-CYCLE-' . $cycle->numero_cycle;
        
        // Débit: Compte spécial du cycle (vider le compte)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'cloture_cycle',
            'compte_number' => '511300', // Compte spécial cycles
            'libelle' => "Clôture cycle {$cycle->numero_cycle} - {$cycle->client_nom}",
            'montant_debit' => $soldeTotal,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $cycle->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte de résultat (bénéfice) ou compte de restitution
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'cloture_cycle',
            'compte_number' => '791000', // Compte de résultat - à adapter
            'libelle' => "Clôture cycle {$cycle->numero_cycle} - {$cycle->client_nom}",
            'montant_debit' => 0,
            'montant_credit' => $soldeTotal,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $cycle->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        Log::info("Écriture comptable créée pour la clôture du cycle", [
            'cycle_id' => $cycle->id,
            'solde_total' => $soldeTotal
        ]);
    });
}


}