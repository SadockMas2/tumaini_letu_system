<?php
// app/Services/ComptabilityService.php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Cycle;
use App\Models\EcritureComptable;
use App\Models\Epargne;
use App\Models\JournalComptable;
use App\Models\Mouvement;
use App\Models\MouvementCoffre;
use App\Models\Caisse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

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
                    Mouvement::create([
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
    // Dans ComptabilityService.php - Améliorer la méthode getSoldeCompte()

public function getSoldeCompte(string $compteNumber, string $devise): float
{
    try {
        $debits = EcritureComptable::where('compte_number', $compteNumber)
            ->where('devise', $devise)
            ->where('statut', 'comptabilise')
            ->sum('montant_debit');

        $credits = EcritureComptable::where('compte_number', $compteNumber)
            ->where('devise', $devise)
            ->where('statut', 'comptabilise')
            ->sum('montant_credit');

        $solde = $debits - $credits;
        
        Log::info("Solde compte calculé", [
            'compte' => $compteNumber,
            'devise' => $devise,
            'debits' => $debits,
            'credits' => $credits,
            'solde' => $solde
        ]);

        return $solde;
        
    } catch (\Exception $e) {
        Log::error("Erreur calcul solde compte", [
            'compte' => $compteNumber,
            'devise' => $devise,
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}


// Dans ComptabilityService.php - Ajouter cette méthode

public function synchroniserGrandeCaisse(): array
{
    return DB::transaction(function () {
        // Récupérer les soldes physiques réels
        $grandeCaisseUSD = Caisse::where('type_caisse', 'grande_caisse')
                                ->where('devise', 'USD')
                                ->first();
        $grandeCaisseCDF = Caisse::where('type_caisse', 'grande_caisse')
                                ->where('devise', 'CDF')
                                ->first();

        $soldesPhysiques = [
            'usd' => $grandeCaisseUSD ? $grandeCaisseUSD->solde : 0,
            'cdf' => $grandeCaisseCDF ? $grandeCaisseCDF->solde : 0
        ];

        // Récupérer les soldes comptables actuels
        $soldeComptableUSD = $this->getSoldeCompte(self::COMPTE_CAISSE, 'USD');
        $soldeComptableCDF = $this->getSoldeCompte(self::COMPTE_CAISSE, 'CDF');

        $ecarts = [
            'usd' => $soldesPhysiques['usd'] - $soldeComptableUSD,
            'cdf' => $soldesPhysiques['cdf'] - $soldeComptableCDF
        ];

        // Si les écarts sont importants, créer des écritures d'ajustement
        if (abs($ecarts['usd']) > 0.01 || abs($ecarts['cdf']) > 0.01) {
            $journal = $this->getJournal('caisse');
            $reference = 'AJUST-GRANDE-CAISSE-' . now()->format('Ymd-His');

            // Ajustement pour USD
            if (abs($ecarts['usd']) > 0.01) {
                if ($ecarts['usd'] > 0) {
                    // Débit: Grande Caisse (on augmente le solde comptable)
                    EcritureComptable::create([
                        'journal_comptable_id' => $journal->id,
                        'reference_operation' => $reference,
                        'type_operation' => 'ajustement_solde',
                        'compte_number' => self::COMPTE_CAISSE,
                        'libelle' => "Ajustement solde grande caisse USD - Écart: " . number_format($ecarts['usd'], 2),
                        'montant_debit' => $ecarts['usd'],
                        'montant_credit' => 0,
                        'date_ecriture' => now(),
                        'date_valeur' => now(),
                        'devise' => 'USD',
                        'statut' => 'comptabilise',
                        'created_by' => Auth::id(),
                    ]);

                    // Crédit: Compte de régularisation
                    EcritureComptable::create([
                        'journal_comptable_id' => $journal->id,
                        'reference_operation' => $reference,
                        'type_operation' => 'ajustement_solde',
                        'compte_number' => '471000', // Compte de régularisation
                        'libelle' => "Ajustement solde grande caisse USD - Écart: " . number_format($ecarts['usd'], 2),
                        'montant_debit' => 0,
                        'montant_credit' => $ecarts['usd'],
                        'date_ecriture' => now(),
                        'date_valeur' => now(),
                        'devise' => 'USD',
                        'statut' => 'comptabilise',
                        'created_by' => Auth::id(),
                    ]);
                } else {
                    // Crédit: Grande Caisse (on diminue le solde comptable)
                    EcritureComptable::create([
                        'journal_comptable_id' => $journal->id,
                        'reference_operation' => $reference,
                        'type_operation' => 'ajustement_solde',
                        'compte_number' => self::COMPTE_CAISSE,
                        'libelle' => "Ajustement solde grande caisse USD - Écart: " . number_format(abs($ecarts['usd']), 2),
                        'montant_debit' => 0,
                        'montant_credit' => abs($ecarts['usd']),
                        'date_ecriture' => now(),
                        'date_valeur' => now(),
                        'devise' => 'USD',
                        'statut' => 'comptabilise',
                        'created_by' => Auth::id(),
                    ]);

                    // Débit: Compte de régularisation
                    EcritureComptable::create([
                        'journal_comptable_id' => $journal->id,
                        'reference_operation' => $reference,
                        'type_operation' => 'ajustement_solde',
                        'compte_number' => '471000', // Compte de régularisation
                        'libelle' => "Ajustement solde grande caisse USD - Écart: " . number_format(abs($ecarts['usd']), 2),
                        'montant_debit' => abs($ecarts['usd']),
                        'montant_credit' => 0,
                        'date_ecriture' => now(),
                        'date_valeur' => now(),
                        'devise' => 'USD',
                        'statut' => 'comptabilise',
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Ajustement pour CDF (même logique)
            if (abs($ecarts['cdf']) > 0.01) {
                // ... même logique que pour USD
            }
        }

        return [
            'soldes_physiques' => $soldesPhysiques,
            'soldes_comptables_avant' => [
                'usd' => $soldeComptableUSD,
                'cdf' => $soldeComptableCDF
            ],
            'ecarts' => $ecarts,
            'ajustement_effectue' => abs($ecarts['usd']) > 0.01 || abs($ecarts['cdf']) > 0.01
        ];
    });
}




public function verifierCohérenceSoldes(): array
{
    $etat = $this->getEtatComptes();
    
    // Récupérer les soldes physiques réels (déjà utilisés dans getEtatComptes())
    $caissesPhysiquesUSD = Caisse::where('devise', 'USD')->sum('solde');
    $caissesPhysiquesCDF = Caisse::where('devise', 'CDF')->sum('solde');
    
    $coffresPhysiquesUSD = CashRegister::where('devise', 'USD')->sum('solde_actuel');
    $coffresPhysiquesCDF = CashRegister::where('devise', 'CDF')->sum('solde_actuel');
    
    // Maintenant les soldes "comptables" pour les caisses sont les mêmes que les physiques
    // puisque nous utilisons directement les soldes physiques
    $ecartUSD = ($etat['grande_caisse_usd'] + $etat['petite_caisse_usd']) - $caissesPhysiquesUSD;
    $ecartCDF = ($etat['grande_caisse_cdf'] + $etat['petite_caisse_cdf']) - $caissesPhysiquesCDF;
    
    // Pour les coffres, on compare toujours comptable vs physique
    $ecartCoffreUSD = $etat['coffre_usd'] - $coffresPhysiquesUSD;
    $ecartCoffreCDF = $etat['coffre_cdf'] - $coffresPhysiquesCDF;
    
    return [
        'etat_comptable' => $etat,
        'soldes_physiques' => [
            'caisses_usd' => $caissesPhysiquesUSD,
            'caisses_cdf' => $caissesPhysiquesCDF,
            'coffres_usd' => $coffresPhysiquesUSD,
            'coffres_cdf' => $coffresPhysiquesCDF,
        ],
        'ecarts' => [
            'caisses_usd' => $ecartUSD, // Devrait être 0 maintenant
            'caisses_cdf' => $ecartCDF, // Devrait être 0 maintenant
            'coffres_usd' => $ecartCoffreUSD,
            'coffres_cdf' => $ecartCoffreCDF,
        ],
        'coherent' => abs($ecartUSD) < 0.01 && abs($ecartCDF) < 0.01 
                   && abs($ecartCoffreUSD) < 0.01 && abs($ecartCoffreCDF) < 0.01
    ];
}

    /**
     * Vérifie les fonds disponibles pour distribution
     */
    public function getFondsDisponiblesTresorerie(string $devise = 'USD'): float
    {
        return $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, $devise);
    }

 // Dans ComptabilityService.php - Corriger la méthode getEtatComptes()

// Dans ComptabilityService.php - Version robuste

// Dans ComptabilityService.php - Corriger les clés

// Dans ComptabilityService.php - Remplacer la méthode getEtatComptes()

public function getEtatComptes(): array
{
    // Récupérer les soldes PHYSIQUES réels des caisses
    $grandeCaisseUSD = Caisse::where('type_caisse', 'grande_caisse')
                            ->where('devise', 'USD')
                            ->first();
    $grandeCaisseCDF = Caisse::where('type_caisse', 'grande_caisse')
                            ->where('devise', 'CDF')
                            ->first();
    
    $petiteCaisseUSD = Caisse::where('type_caisse', 'petite_caisse')
                            ->where('devise', 'USD')
                            ->first();
    $petiteCaisseCDF = Caisse::where('type_caisse', 'petite_caisse')
                            ->where('devise', 'CDF')
                            ->first();

    $etat = [
        // Compte Transit Trésorerie (comptable)
        'transit_usd' => $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'USD'),
        'transit_cdf' => $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'CDF'),
        
        // Coffre Fort (comptable)
        'coffre_usd' => $this->getSoldeCompte(self::COMPTE_COFFRE_FORT, 'USD'),
        'coffre_cdf' => $this->getSoldeCompte(self::COMPTE_COFFRE_FORT, 'CDF'),
        
        // Banque (comptable)
        'banque_usd' => $this->getSoldeCompte(self::COMPTE_BANQUE, 'USD'),
        'banque_cdf' => $this->getSoldeCompte(self::COMPTE_BANQUE, 'CDF'),
        
        // GRANDE CAISSE (SOLDE PHYSIQUE RÉEL)
        'grande_caisse_usd' => $grandeCaisseUSD ? (float) $grandeCaisseUSD->solde : 0,
        'grande_caisse_cdf' => $grandeCaisseCDF ? (float) $grandeCaisseCDF->solde : 0,
        
        // PETITE CAISSE (SOLDE PHYSIQUE RÉEL)
        'petite_caisse_usd' => $petiteCaisseUSD ? (float) $petiteCaisseUSD->solde : 0,
        'petite_caisse_cdf' => $petiteCaisseCDF ? (float) $petiteCaisseCDF->solde : 0,
    ];
    
    // Calculer les totaux
    $etat['total_usd'] = array_sum([
        $etat['transit_usd'],
        $etat['coffre_usd'],
        $etat['banque_usd'],
        $etat['grande_caisse_usd'], // Maintenant le solde physique réel
        $etat['petite_caisse_usd']  // Maintenant le solde physique réel
    ]);
    
    $etat['total_cdf'] = array_sum([
        $etat['transit_cdf'],
        $etat['coffre_cdf'],
        $etat['banque_cdf'],
        $etat['grande_caisse_cdf'], // Maintenant le solde physique réel
        $etat['petite_caisse_cdf']  // Maintenant le solde physique réel
    ]);
    
    return $etat;
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

public function enregistrerRetourPetiteCaisse($caisseId, $montant, $devise, $reference, $description)
{
    return DB::transaction(function () use ($caisseId, $montant, $devise, $reference, $description) {
        $journal = $this->getJournal('caisse');
        
        // Débit: Compte de transit (retour vers comptabilité)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'retour_petite_caisse',
            'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
            'libelle' => "Retour petite caisse - {$description}",
            'montant_debit' => $montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Petite caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'retour_petite_caisse',
            'compte_number' => self::COMPTE_PETITE_CAISSE,
            'libelle' => "Retour petite caisse - {$description}",
            'montant_debit' => 0,
            'montant_credit' => $montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        return true;
    });
}


// Ajouter ces méthodes dans ComptabilityService.php

/**
 * Enregistre un paiement de salaire/charge (débit direct sur compte)
 */
/**
 * Enregistre un paiement de salaire/charge (crédit direct sur compte)
 */
public function enregistrerPaiementSalaireCharge($mouvement, $compte, $typeCharge, $description, $beneficiaire)
{
    return DB::transaction(function () use ($mouvement, $compte, $typeCharge, $description, $beneficiaire) {
        $journal = $this->getJournal('caisse');
        
        $reference = 'SAL-' . now()->format('Ymd-His');
        $compteCharge = $this->getCompteChargeSalaire($typeCharge);
        
        // Débit: Compte de charge (la charge pour l'organisation)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'paiement_salaire_charge',
            'compte_number' => $compteCharge,
            'libelle' => "Paiement {$typeCharge} - {$beneficiaire} - {$description}",
            'montant_debit' => $mouvement->montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $mouvement->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte du membre (le membre reçoit l'argent)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'paiement_salaire_charge',
            'compte_number' => '422000', // Compte membres
            'libelle' => "Paiement {$typeCharge} - {$beneficiaire} - {$description}",
            'montant_debit' => 0,
            'montant_credit' => $mouvement->montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $mouvement->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        return true;
    });
}

/**
 * Enregistre une dépense diverse (utilisation petite caisse)
 */
public function enregistrerDepenseDiverse($caisseId, $montant, $devise, $compteCharge, $description, $beneficiaire)
{
    return DB::transaction(function () use ($caisseId, $montant, $devise, $compteCharge, $description, $beneficiaire) {
        $journal = $this->getJournal('achats');
        
        $reference = 'DEP-DIV-' . now()->format('Ymd-His');
        
        // Débit: Compte de charge
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depense_diverse',
            'compte_number' => $compteCharge,
            'libelle' => "Dépense diverse - {$beneficiaire} - {$description}",
            'montant_debit' => $montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Petite caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'depense_diverse',
            'compte_number' => self::COMPTE_PETITE_CAISSE,
            'libelle' => "Dépense diverse - {$beneficiaire} - {$description}",
            'montant_debit' => 0,
            'montant_credit' => $montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        return true;
    });
}

/**
 * Enregistre le délaistage de la petite caisse
 */
public function enregistrerDelaisagePetiteCaisse($montant, $devise, $reference, $motif)
{
    return DB::transaction(function () use ($montant, $devise, $reference, $motif) {
        $journal = $this->getJournal('caisse');
        
        // Débit: Compte de transit
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'delaisage_petite_caisse',
            'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
            'libelle' => "Délaistage petite caisse - {$motif}",
            'montant_debit' => $montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Petite caisse
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'delaisage_petite_caisse',
            'compte_number' => self::COMPTE_PETITE_CAISSE,
            'libelle' => "Délaistage petite caisse - {$motif}",
            'montant_debit' => 0,
            'montant_credit' => $montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        return true;
    });
}

/**
 * Détermine le compte de charge pour les salaires
 */
private function getCompteChargeSalaire(string $typeCharge): string
{
    return match($typeCharge) {
        'salaire' => '661100',
        'transport' => '661800', 
        'communication' => '661800',
        'prime' => '661200',
        'autres' => '661800',
        default => '661100'
    };
}

// public function forcerExactement630USD()
// {
//     return DB::transaction(function () {
//         $journal = $this->getJournal('caisse');
//         $reference = 'FORCE-630-USD-' . now()->format('Ymd-His');
//         $montantForce = 630;
        
//         // 1. D'abord, réinitialiser le compte transit à 0
//         $soldeActuel = $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'USD');
        
//         if ($soldeActuel > 0) {
//             // Vider le compte transit
//             EcritureComptable::create([
//                 'journal_comptable_id' => $journal->id,
//                 'reference_operation' => $reference . '-VIDAGE',
//                 'type_operation' => 'force_reset_transit',
//                 'compte_number' => '471000', // Compte de régularisation
//                 'libelle' => "Vidage forcé compte transit -{$soldeActuel} USD",
//                 'montant_debit' => $soldeActuel,
//                 'montant_credit' => 0,
//                 'date_ecriture' => now(),
//                 'date_valeur' => now(),
//                 'devise' => 'USD',
//                 'statut' => 'comptabilise',
//                 'created_by' => Auth::id(),
//             ]);

//             EcritureComptable::create([
//                 'journal_comptable_id' => $journal->id,
//                 'reference_operation' => $reference . '-VIDAGE',
//                 'type_operation' => 'force_reset_transit',
//                 'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
//                 'libelle' => "Vidage forcé compte transit -{$soldeActuel} USD",
//                 'montant_debit' => 0,
//                 'montant_credit' => $soldeActuel,
//                 'date_ecriture' => now(),
//                 'date_valeur' => now(),
//                 'devise' => 'USD',
//                 'statut' => 'comptabilise',
//                 'created_by' => Auth::id(),
//             ]);
//         }

//         // 2. Maintenant, mettre EXACTEMENT 630 USD
//         EcritureComptable::create([
//             'journal_comptable_id' => $journal->id,
//             'reference_operation' => $reference . '-ALIMENT',
//             'type_operation' => 'force_aliment_transit',
//             'compte_number' => self::COMPTE_TRANSIT_TRESORERIE,
//             'libelle' => "Alimentation forcée compte transit +630 USD",
//             'montant_debit' => $montantForce,
//             'montant_credit' => 0,
//             'date_ecriture' => now(),
//             'date_valeur' => now(),
//             'devise' => 'USD',
//             'statut' => 'comptabilise',
//             'created_by' => Auth::id(),
//         ]);

//         EcritureComptable::create([
//             'journal_comptable_id' => $journal->id,
//             'reference_operation' => $reference . '-ALIMENT',
//             'type_operation' => 'force_aliment_transit',
//             'compte_number' => '471000', // Compte de régularisation
//             'libelle' => "Alimentation forcée compte transit +630 USD",
//             'montant_debit' => 0,
//             'montant_credit' => $montantForce,
//             'date_ecriture' => now(),
//             'date_valeur' => now(),
//             'devise' => 'USD',
//             'statut' => 'comptabilise',
//             'created_by' => Auth::id(),
//         ]);

//         $nouveauSolde = $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'USD');
        
//         return [
//             'reference' => $reference,
//             'ancien_solde' => $soldeActuel,
//             'nouveau_solde' => $nouveauSolde,
//             'montant_force' => $montantForce
//         ];
//     });
// }


 /**
     * Générer un rapport instantané de la comptabilité
     */
    public function rapportInstantaneeComptabilite($date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        // Récupérer les petites caisses
        $petitesCaisses = Caisse::where('type_caisse', 'petite_caisse')
            ->with(['mouvements' => function($query) use ($date) {
                $query->whereDate('created_at', $date)
                      ->orderBy('created_at', 'asc');
            }])
            ->get();

        // Récupérer les mouvements de transit (compte 511100)
        $mouvementsTransit = EcritureComptable::where('compte_number', self::COMPTE_TRANSIT_TRESORERIE)
            ->whereDate('date_ecriture', $date)
            ->orderBy('date_ecriture', 'asc')
            ->get();

        $rapport = [
            'date_rapport' => $date->format('d/m/Y'),
            'date_generation' => Carbon::now()->format('d/m/Y H:i:s'),
            'logo_base64' => $this->getLogoBase64(),
            'type' => 'comptabilite_instantanee',
            
            // Soldes des comptes
            'soldes_comptes' => [
                'transit_usd' => $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'USD'),
                'transit_cdf' => $this->getSoldeCompte(self::COMPTE_TRANSIT_TRESORERIE, 'CDF'),
                'petite_caisse_usd' => $this->getSoldeCompte(self::COMPTE_PETITE_CAISSE, 'USD'),
                'petite_caisse_cdf' => $this->getSoldeCompte(self::COMPTE_PETITE_CAISSE, 'CDF'),
                'coffre_usd' => $this->getSoldeCompte(self::COMPTE_COFFRE_FORT, 'USD'),
                'coffre_cdf' => $this->getSoldeCompte(self::COMPTE_COFFRE_FORT, 'CDF'),
                'banque_usd' => $this->getSoldeCompte(self::COMPTE_BANQUE, 'USD'),
                'banque_cdf' => $this->getSoldeCompte(self::COMPTE_BANQUE, 'CDF'),
            ],
            
            // Petites caisses par devise
            'petites_caisses' => [
                'usd' => [
                    'solde_total' => 0,
                    'depots' => 0,
                    'retraits' => 0,
                    'operations' => 0,
                    'caisses' => []
                ],
                'cdf' => [
                    'solde_total' => 0,
                    'depots' => 0,
                    'retraits' => 0,
                    'operations' => 0,
                    'caisses' => []
                ]
            ],
            
            // Mouvements de transit
            'transit' => [
                'usd' => [
                    'entrees' => 0,
                    'sorties' => 0,
                    'solde' => 0,
                    'mouvements' => []
                ],
                'cdf' => [
                    'entrees' => 0,
                    'sorties' => 0,
                    'solde' => 0,
                    'mouvements' => []
                ]
            ],
            
            // Synthèse des opérations
            'synthese' => [
                'total_entrees' => 0,
                'total_sorties' => 0,
                'solde_net' => 0
            ]
        ];

        // Traitement des petites caisses
        foreach ($petitesCaisses as $caisse) {
            $mouvements = $caisse->mouvements;
            
            $depots = $mouvements->where('type', 'depot')->sum('montant');
            $retraits = $mouvements->where('type', 'retrait')->sum('montant');
            $operations = $mouvements->count();

            $caisseData = [
                'nom' => $caisse->nom,
                'solde_actuel' => $caisse->solde,
                'plafond' => $caisse->plafond,
                'depots' => $depots,
                'retraits' => $retraits,
                'operations' => $operations,
                'pourcentage_utilisation' => $caisse->plafond > 0 ? ($caisse->solde / $caisse->plafond) * 100 : 0,
                'mouvements' => $mouvements->map(function($mouvement) {
                    return [
                        'type' => $mouvement->type,
                        'type_mouvement' => $mouvement->type_mouvement,
                        'montant' => $mouvement->montant,
                        'description' => $mouvement->description,
                        'beneficiaire' => $mouvement->nom_deposant ?? $mouvement->client_nom,
                        'operateur' => $mouvement->operateur->name ?? 'N/A',
                        'heure' => $mouvement->created_at->format('H:i:s')
                    ];
                })->values()
            ];

            if ($caisse->devise === 'USD') {
                $rapport['petites_caisses']['usd']['solde_total'] += $caisse->solde;
                $rapport['petites_caisses']['usd']['depots'] += $depots;
                $rapport['petites_caisses']['usd']['retraits'] += $retraits;
                $rapport['petites_caisses']['usd']['operations'] += $operations;
                $rapport['petites_caisses']['usd']['caisses'][] = $caisseData;
            } elseif ($caisse->devise === 'CDF') {
                $rapport['petites_caisses']['cdf']['solde_total'] += $caisse->solde;
                $rapport['petites_caisses']['cdf']['depots'] += $depots;
                $rapport['petites_caisses']['cdf']['retraits'] += $retraits;
                $rapport['petites_caisses']['cdf']['operations'] += $operations;
                $rapport['petites_caisses']['cdf']['caisses'][] = $caisseData;
            }
        }

        // Traitement des mouvements de transit
        foreach ($mouvementsTransit as $mouvement) {
            $estEntree = $mouvement->montant_debit > 0;
            $montant = $estEntree ? $mouvement->montant_debit : $mouvement->montant_credit;
            
            $mouvementData = [
                'type_operation' => $mouvement->type_operation,
                'libelle' => $mouvement->libelle,
                'montant' => $montant,
                'type' => $estEntree ? 'entree' : 'sortie',
                'reference' => $mouvement->reference_operation,
                'date_heure' => $mouvement->date_ecriture->format('H:i:s'),
                'operateur' => $mouvement->createdBy->name ?? 'N/A'
            ];

            if ($mouvement->devise === 'USD') {
                if ($estEntree) {
                    $rapport['transit']['usd']['entrees'] += $montant;
                } else {
                    $rapport['transit']['usd']['sorties'] += $montant;
                }
                $rapport['transit']['usd']['mouvements'][] = $mouvementData;
            } elseif ($mouvement->devise === 'CDF') {
                if ($estEntree) {
                    $rapport['transit']['cdf']['entrees'] += $montant;
                } else {
                    $rapport['transit']['cdf']['sorties'] += $montant;
                }
                $rapport['transit']['cdf']['mouvements'][] = $mouvementData;
            }
        }

        // Calcul des soldes de transit
        $rapport['transit']['usd']['solde'] = $rapport['transit']['usd']['entrees'] - $rapport['transit']['usd']['sorties'];
        $rapport['transit']['cdf']['solde'] = $rapport['transit']['cdf']['entrees'] - $rapport['transit']['cdf']['sorties'];

        // Synthèse générale
        $rapport['synthese']['total_entrees'] = $rapport['transit']['usd']['entrees'] + $rapport['transit']['cdf']['entrees'];
        $rapport['synthese']['total_sorties'] = $rapport['transit']['usd']['sorties'] + $rapport['transit']['cdf']['sorties'];
        $rapport['synthese']['solde_net'] = $rapport['synthese']['total_entrees'] - $rapport['synthese']['total_sorties'];

        return $rapport;
    }

    /**
     * Générer un rapport période pour la comptabilité
     */
    public function rapportPeriodeComptabilite($dateDebut, $dateFin = null)
    {
        $dateDebut = Carbon::parse($dateDebut);
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now();

        $rapport = [
            'periode' => $dateDebut->format('d/m/Y') . ' - ' . $dateFin->format('d/m/Y'),
            'date_generation' => Carbon::now()->format('d/m/Y H:i:s'),
            'type' => 'comptabilite_periode',
            
            'evolution_journaliere' => [],
            'totaux_periode' => [
                'usd' => ['entrees' => 0, 'sorties' => 0, 'solde' => 0],
                'cdf' => ['entrees' => 0, 'sorties' => 0, 'solde' => 0]
            ]
        ];

        // Générer l'évolution jour par jour
        $dateCourante = $dateDebut->copy();
        while ($dateCourante <= $dateFin) {
            $jourData = [
                'date' => $dateCourante->format('d/m/Y'),
                'transit_usd' => ['entrees' => 0, 'sorties' => 0],
                'transit_cdf' => ['entrees' => 0, 'sorties' => 0],
                'petites_caisses_usd' => ['depots' => 0, 'retraits' => 0],
                'petites_caisses_cdf' => ['depots' => 0, 'retraits' => 0]
            ];

            // Mouvements de transit du jour
            $mouvementsTransit = EcritureComptable::where('compte_number', self::COMPTE_TRANSIT_TRESORERIE)
                ->whereDate('date_ecriture', $dateCourante)
                ->get();

            foreach ($mouvementsTransit as $mouvement) {
                $estEntree = $mouvement->montant_debit > 0;
                $montant = $estEntree ? $mouvement->montant_debit : $mouvement->montant_credit;
                
                if ($mouvement->devise === 'USD') {
                    if ($estEntree) {
                        $jourData['transit_usd']['entrees'] += $montant;
                        $rapport['totaux_periode']['usd']['entrees'] += $montant;
                    } else {
                        $jourData['transit_usd']['sorties'] += $montant;
                        $rapport['totaux_periode']['usd']['sorties'] += $montant;
                    }
                } elseif ($mouvement->devise === 'CDF') {
                    if ($estEntree) {
                        $jourData['transit_cdf']['entrees'] += $montant;
                        $rapport['totaux_periode']['cdf']['entrees'] += $montant;
                    } else {
                        $jourData['transit_cdf']['sorties'] += $montant;
                        $rapport['totaux_periode']['cdf']['sorties'] += $montant;
                    }
                }
            }

            // Mouvements petites caisses du jour
            $mouvementsPetitesCaisses = Mouvement::whereHas('caisse', function($query) {
                    $query->where('type_caisse', 'petite_caisse');
                })
                ->whereDate('created_at', $dateCourante)
                ->get();

            foreach ($mouvementsPetitesCaisses as $mouvement) {
                if ($mouvement->devise === 'USD') {
                    if ($mouvement->type === 'depot') {
                        $jourData['petites_caisses_usd']['depots'] += $mouvement->montant;
                    } else {
                        $jourData['petites_caisses_usd']['retraits'] += $mouvement->montant;
                    }
                } elseif ($mouvement->devise === 'CDF') {
                    if ($mouvement->type === 'depot') {
                        $jourData['petites_caisses_cdf']['depots'] += $mouvement->montant;
                    } else {
                        $jourData['petites_caisses_cdf']['retraits'] += $mouvement->montant;
                    }
                }
            }

            $rapport['evolution_journaliere'][] = $jourData;
            $dateCourante->addDay();
        }

        // Calcul des soldes finaux
        $rapport['totaux_periode']['usd']['solde'] = 
            $rapport['totaux_periode']['usd']['entrees'] - $rapport['totaux_periode']['usd']['sorties'];
        $rapport['totaux_periode']['cdf']['solde'] = 
            $rapport['totaux_periode']['cdf']['entrees'] - $rapport['totaux_periode']['cdf']['sorties'];

        return $rapport;
    }

    /**
     * Convertir une image en base64 pour l'inclure dans le PDF
     */
    private function getLogoBase64()
    {
        $logoPath = public_path('images/logo-tumaini1.png');
        
        $possiblePaths = [
            public_path('images/logo-tumaini1.png'),
            public_path('images/logo-tumaini1.jpg'),
            public_path('images/logo-tumaini1.jpeg'),
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('storage/images/logo-tumaini1.png'),
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $logoPath = $path;
                break;
            }
        }
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $imageInfo = getimagesize($logoPath);
            $mimeType = $imageInfo['mime'] ?? 'image/png';
            
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
        
        return $this->getDefaultLogo();
    }

    /**
     * Générer un logo par défaut en base64
     */
    private function getDefaultLogo()
    {
        $svg = '<svg width="140" height="70" xmlns="http://www.w3.org/2000/svg">
                <rect width="140" height="70" fill="#f0f0f0" stroke="#ccc" stroke-width="1"/>
                <text x="70" y="35" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="12" fill="#666">
                    TUMAINI LETU
                </text>
                <text x="70" y="50" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="8" fill="#999">
                    COMPTABILITÉ
                </text>
            </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
 * Enregistre un virement vers un compte (similaire au paiement salaire)
 */
public function enregistrerVirement($mouvement, $compte, $motif, $description, $beneficiaire)
{
    return DB::transaction(function () use ($mouvement, $compte, $motif, $description, $beneficiaire) {
        $journal = $this->getJournal('caisse');
        
        $reference = 'VIR-' . now()->format('Ymd-His');
        
        // Débit: Compte de charge selon le motif
        $compteCharge = $this->getCompteChargeVirement($motif);
        
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'virement_comptabilite',
            'compte_number' => $compteCharge,
            'libelle' => "Virement {$motif} - {$beneficiaire} - {$description}",
            'montant_debit' => $mouvement->montant,
            'montant_credit' => 0,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $mouvement->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        // Crédit: Compte du membre (le membre reçoit l'argent)
        EcritureComptable::create([
            'journal_comptable_id' => $journal->id,
            'reference_operation' => $reference,
            'type_operation' => 'virement_comptabilite',
            'compte_number' => '422000', // Compte membres
            'libelle' => "Virement {$motif} - {$beneficiaire} - {$description}",
            'montant_debit' => 0,
            'montant_credit' => $mouvement->montant,
            'date_ecriture' => now(),
            'date_valeur' => now(),
            'devise' => $mouvement->devise,
            'statut' => 'comptabilise',
            'created_by' => Auth::id(),
        ]);

        Log::info("Écriture comptable créée pour virement", [
            'reference' => $reference,
            'montant' => $mouvement->montant,
            'devise' => $mouvement->devise,
            'motif' => $motif,
            'compte_beneficiaire' => $compte->numero_compte,
            'beneficiaire' => $beneficiaire
        ]);

        return true;
    });
}

/**
 * Détermine le compte de charge pour les virements
 */
private function getCompteChargeVirement(string $motif): string
{
    return match($motif) {
        'remboursement' => '658200', // Compte remboursements
        'avance' => '658300',        // Compte avances
        'commission' => '658400',    // Compte commissions
        'facture_client' => '658500',         // Compte Factures clients
        'transfert' => '658100',     // Compte transferts divers
        'autres' => '658600',        // Autres charges
        default => '658100'          // Par défaut
    };
}


public function rapportClasse6Charges($dateDebut, $dateFin = null)
{
    $dateDebut = Carbon::parse($dateDebut)->startOfDay();
    $dateFin = $dateFin ? Carbon::parse($dateFin)->endOfDay() : Carbon::now()->endOfDay();

    // Les comptes de la classe 6 commencent par '6'
    $comptesClasse6 = EcritureComptable::where('compte_number', 'like', '6%')
        ->whereBetween('date_ecriture', [$dateDebut, $dateFin])
        ->orderBy('date_ecriture', 'asc')
        ->get();

    // Regrouper par type de compte (premier chiffre après '6')
    $chargesParType = [];
    $totalUSD = 0;
    $totalCDF = 0;

    foreach ($comptesClasse6 as $ecriture) {
        // Déterminer le type de charge basé sur le numéro de compte
        $typeCharge = $this->getTypeChargeFromCompte($ecriture->compte_number);
        $montant = $ecriture->montant_debit > 0 ? $ecriture->montant_debit : $ecriture->montant_credit;
        
        if (!isset($chargesParType[$typeCharge])) {
            $chargesParType[$typeCharge] = [
                'type' => $typeCharge,
                'comptes' => [],
                'total_usd' => 0,
                'total_cdf' => 0,
                'operations' => 0
            ];
        }

        // Regrouper par compte spécifique
        $compteKey = $ecriture->compte_number;
        if (!isset($chargesParType[$typeCharge]['comptes'][$compteKey])) {
            $chargesParType[$typeCharge]['comptes'][$compteKey] = [
                'compte_number' => $ecriture->compte_number,
                'libelle' => $this->getLibelleCompte($ecriture->compte_number),
                'operations' => [],
                'total_usd' => 0,
                'total_cdf' => 0,
                'nombre_operations' => 0
            ];
        }

        // Ajouter l'opération
        $chargesParType[$typeCharge]['comptes'][$compteKey]['operations'][] = [
            'date' => $ecriture->date_ecriture->format('d/m/Y H:i'),
            'reference' => $ecriture->reference_operation,
            'type_operation' => $ecriture->type_operation,
            'libelle' => $ecriture->libelle,
            'montant' => $montant,
            'devise' => $ecriture->devise,
            'operateur' => $ecriture->createdBy->name ?? 'N/A',
            'journal' => $ecriture->journal_comptable->code_journal ?? 'N/A'
        ];

        // Mettre à jour les totaux
        if ($ecriture->devise === 'USD') {
            $chargesParType[$typeCharge]['comptes'][$compteKey]['total_usd'] += $montant;
            $chargesParType[$typeCharge]['total_usd'] += $montant;
            $totalUSD += $montant;
        } elseif ($ecriture->devise === 'CDF') {
            $chargesParType[$typeCharge]['comptes'][$compteKey]['total_cdf'] += $montant;
            $chargesParType[$typeCharge]['total_cdf'] += $montant;
            $totalCDF += $montant;
        }

        $chargesParType[$typeCharge]['comptes'][$compteKey]['nombre_operations']++;
        $chargesParType[$typeCharge]['operations']++;
    }

    // Calculer les pourcentages
    foreach ($chargesParType as &$type) {
        $type['pourcentage_usd'] = $totalUSD > 0 ? ($type['total_usd'] / $totalUSD * 100) : 0;
        $type['pourcentage_cdf'] = $totalCDF > 0 ? ($type['total_cdf'] / $totalCDF * 100) : 0;
        
        // Convertir les comptes en tableau indexé
        $type['comptes'] = array_values($type['comptes']);
    }

    // Trier par montant total décroissant
    uasort($chargesParType, function($a, $b) {
        $totalA = $a['total_usd'] + $a['total_cdf'];
        $totalB = $b['total_usd'] + $b['total_cdf'];
        return $totalB <=> $totalA;
    });

    return [
        'periode' => [
            'debut' => $dateDebut->format('d/m/Y'),
            'fin' => $dateFin->format('d/m/Y'),
            'jours' => $dateDebut->diffInDays($dateFin) + 1
        ],
        'date_generation' => Carbon::now()->format('d/m/Y H:i:s'),
        'type' => 'rapport_classe6_charges',
        'logo_base64' => $this->getLogoBase64(),
        
        'totaux_generaux' => [
            'total_usd' => $totalUSD,
            'total_cdf' => $totalCDF,
            'total_operations' => $comptesClasse6->count(),
            'nombre_types_charges' => count($chargesParType)
        ],
        
        'charges_par_type' => $chargesParType,
        
        'distribution_periodique' => $this->getDistributionPeriodiqueCharges($dateDebut, $dateFin),
        
        'top_operations' => $comptesClasse6->take(10)->map(function($ecriture) {
            return [
                'date' => $ecriture->date_ecriture->format('d/m/Y'),
                'compte' => $ecriture->compte_number,
                'libelle' => $ecriture->libelle,
                'montant' => $ecriture->montant_debit > 0 ? $ecriture->montant_debit : $ecriture->montant_credit,
                'devise' => $ecriture->devise,
                'type' => $ecriture->type_operation
            ];
        })->values()->toArray()
    ];
}
/**
 * Détermine le type de charge à partir du numéro de compte
 */
private function getTypeChargeFromCompte(string $compteNumber): string
{
    // Les deux premiers chiffres déterminent la catégorie
    $categorie = substr($compteNumber, 0, 2);
    
    return match($categorie) {
        '60' => 'Achats',
        '61' => 'Services extérieurs',
        '62' => 'Autres services extérieurs',
        '63' => 'Impôts et taxes',
        '64' => 'Charges de personnel',
        '65' => 'Autres charges d\'exploitation',
        '66' => 'Charges financières',
        '67' => 'Charges exceptionnelles',
        '68' => 'Dotations aux amortissements',
        '69' => 'Dotations aux provisions',
        default => 'Charges diverses'
    };
}

/**
 * Obtient le libellé d'un compte
 */
private function getLibelleCompte(string $compteNumber): string
{
    $libelles = [
        '661100' => 'Salaires et appointements',
        '661200' => 'Primes et gratifications',
        '661800' => 'Autres charges de personnel',
        '613100' => 'Frais de bureau',
        '613200' => 'Frais de transport',
        '613300' => 'Frais de communication',
        '613400' => 'Frais d\'entretien',
        '613500' => 'Frais de fournitures',
        '613600' => 'Autres frais généraux',
        '658100' => 'Charges diverses d\'exploitation',
        '658200' => 'Remboursements',
        '658300' => 'Avances',
        '658400' => 'Commissions',
        '658500' => 'Frais de facturation',
        '658600' => 'Autres charges',
    ];
    
    return $libelles[$compteNumber] ?? "Compte {$compteNumber}";
}

/**
 * Calcule la distribution périodique des charges
 */
private function getDistributionPeriodiqueCharges($dateDebut, $dateFin): array
{
    $distribution = [];
    $dateCourante = $dateDebut->copy();
    
    while ($dateCourante <= $dateFin) {
        $jour = $dateCourante->format('Y-m-d');
        
        $chargesJour = EcritureComptable::where('compte_number', 'like', '6%')
            ->whereDate('date_ecriture', $dateCourante)
            ->get();
        
        $totalUSD = 0;
        $totalCDF = 0;
        
        foreach ($chargesJour as $ecriture) {
            $montant = $ecriture->montant_debit > 0 ? $ecriture->montant_debit : $ecriture->montant_credit;
            
            if ($ecriture->devise === 'USD') {
                $totalUSD += $montant;
            } elseif ($ecriture->devise === 'CDF') {
                $totalCDF += $montant;
            }
        }
        
        $distribution[] = [
            'date' => $dateCourante->format('d/m/Y'),
            'jour_semaine' => $dateCourante->format('l'),
            'total_usd' => $totalUSD,
            'total_cdf' => $totalCDF,
            'operations' => $chargesJour->count(),
            'moyenne_usd' => $chargesJour->count() > 0 ? $totalUSD / $chargesJour->count() : 0,
            'moyenne_cdf' => $chargesJour->count() > 0 ? $totalCDF / $chargesJour->count() : 0
        ];
        
        $dateCourante->addDay();
    }
    
    return $distribution;
}


}