<?php
// app/Services/ComptabilityService.php

namespace App\Services;

use App\Models\EcritureComptable;
use App\Models\JournalComptable;
use App\Models\MouvementCoffre;
use App\Models\Depense;
use Illuminate\Support\Facades\DB;

class ComptabilityService
{
    const COMPTE_CAISSE = '571100';
    const COMPTE_BANQUE = '521100';
    const COMPTE_COFFRE_FORT = '571200';
    const COMPTE_PETITE_CAISSE = '571300';
    const COMPTE_CHARGES_DIVERSES = '658100';
    const COMPTE_PRODUITS_DIVERS = '758100';
    const COMPTE_CLIENTS = '411100';

    public function enregistrerAlimentationCoffre(int $mouvementCoffreId, string $referenceBanque)
    {
        $mouvement = MouvementCoffre::findOrFail($mouvementCoffreId);
        $journal = JournalComptable::where('type_journal', 'banque')->first();

        DB::transaction(function () use ($mouvement, $journal, $referenceBanque) {
            EcritureComptable::create([
                'journal_id' => $journal->id,
                'reference_operation' => $referenceBanque,
                'type_operation' => 'banque_vers_coffre',
                'compte_number' => self::COMPTE_COFFRE_FORT,
                'libelle' => "Alimentation coffre depuis banque - Ref: {$referenceBanque}",
                'montant_debit' => $mouvement->montant,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'coffre_mouvement_id' => $mouvement->id
            ]);

            EcritureComptable::create([
                'journal_id' => $journal->id,
                'reference_operation' => $referenceBanque,
                'type_operation' => 'banque_vers_coffre',
                'compte_number' => self::COMPTE_BANQUE,
                'libelle' => "Alimentation coffre depuis banque - Ref: {$referenceBanque}",
                'montant_debit' => 0,
                'montant_credit' => $mouvement->montant,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'coffre_mouvement_id' => $mouvement->id
            ]);
        });
    }

    public function enregistrerTransfertCoffreVersComptable(int $mouvementCoffreId)
    {
        $mouvement = MouvementCoffre::findOrFail($mouvementCoffreId);
        $journal = JournalComptable::where('type_journal', 'caisse')->first();

        DB::transaction(function () use ($mouvement, $journal) {
            EcritureComptable::create([
                'journal_id' => $journal->id,
                'reference_operation' => $mouvement->reference,
                'type_operation' => 'coffre_vers_comptable',
                'compte_number' => self::COMPTE_CAISSE,
                'libelle' => "Transfert coffre vers comptable - {$mouvement->description}",
                'montant_debit' => $mouvement->montant,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'coffre_mouvement_id' => $mouvement->id
            ]);

            EcritureComptable::create([
                'journal_id' => $journal->id,
                'reference_operation' => $mouvement->reference,
                'type_operation' => 'coffre_vers_comptable',
                'compte_number' => self::COMPTE_COFFRE_FORT,
                'libelle' => "Transfert coffre vers comptable - {$mouvement->description}",
                'montant_debit' => 0,
                'montant_credit' => $mouvement->montant,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $mouvement->devise,
                'statut' => 'comptabilise',
                'coffre_mouvement_id' => $mouvement->id
            ]);
        });
    }

    public function enregistrerDepense(int $depenseId, string $compteCharge, string $libelle)
    {
        $depense = Depense::findOrFail($depenseId);
        $journal = JournalComptable::where('type_journal', 'achats')->first();

        DB::transaction(function () use ($depense, $journal, $compteCharge, $libelle) {
            EcritureComptable::create([
                'journal_id' => $journal->id,
                'reference_operation' => $depense->reference,
                'type_operation' => 'depense',
                'compte_number' => $compteCharge,
                'libelle' => $libelle,
                'montant_debit' => $depense->montant,
                'montant_credit' => 0,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $depense->devise,
                'piece_justificative' => $depense->piece_justificative,
                'statut' => 'comptabilise'
            ]);

            $compteCaisse = $depense->caisse->type_caisse === 'petite_caisse' 
                ? self::COMPTE_PETITE_CAISSE 
                : self::COMPTE_CAISSE;

            EcritureComptable::create([
                'journal_id' => $journal->id,
                'reference_operation' => $depense->reference,
                'type_operation' => 'depense',
                'compte_number' => $compteCaisse,
                'libelle' => $libelle,
                'montant_debit' => 0,
                'montant_credit' => $depense->montant,
                'date_ecriture' => now(),
                'date_valeur' => now(),
                'devise' => $depense->devise,
                'piece_justificative' => $depense->piece_justificative,
                'statut' => 'comptabilise'
            ]);
        });
    }

    public function verifierEquilibrePeriodique(string $dateDebut, string $dateFin): bool
    {
        $resultat = EcritureComptable::whereBetween('date_ecriture', [$dateDebut, $dateFin])
            ->where('statut', 'comptabilise')
            ->selectRaw('SUM(montant_debit) as total_debit, SUM(montant_credit) as total_credit')
            ->first();

        return $resultat->total_debit == $resultat->total_credit;
    }
}