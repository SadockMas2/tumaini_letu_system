<?php

namespace App\Console\Commands;

use App\Models\Credit;
use App\Models\CreditGroupe;
use Illuminate\Console\Command;

class AfficherMontantsCredit extends Command
{
    protected $signature = 'credits:montants 
                            {--type=all : Type de crédit (individuel, groupe, all)}
                            {--id= : ID spécifique d\'un crédit}';
    
    protected $description = 'Afficher les montants totaux des crédits';

    public function handle()
    {
        $type = $this->option('type');
        $id = $this->option('id');
        
        $this->info("=== MONTANTS TOTAUX DES CRÉDITS ===");
        $this->info("Date: " . now()->format('d/m/Y H:i:s'));
        $this->newLine();
        
        if ($type === 'groupe' || $type === 'all') {
            $this->afficherCreditsGroupe($id);
        }
        
        if ($type === 'individuel' || $type === 'all') {
            $this->afficherCreditsIndividuels($id);
        }
        
        $this->newLine();
        $this->info("=== FIN DU RAPPORT ===");
    }
    
    private function afficherCreditsGroupe($id = null)
    {
        $this->info("📊 CRÉDITS DE GROUPE");
        $this->info(str_repeat('-', 80));
        
        $query = CreditGroupe::where('statut_demande', 'approuve');
        
        if ($id) {
            $query->where('id', $id);
        }
        
        $credits = $query->get();
        
        if ($credits->isEmpty()) {
            $this->warn("Aucun crédit groupe trouvé");
            return;
        }
        
        $totalMontantTotal = 0;
        
        foreach ($credits as $credit) {
            // Calcul selon votre formule : montant_accorde * 1.225
            $montantAccorde = floatval($credit->montant_accorde);
            $montantCalcule = $montantAccorde * 1.225;
            $montantTotal = floatval($credit->montant_total);
            
            // Récupérer le nom du groupe avec une vérification
            $nomGroupe = $credit->compte ? $credit->compte->nom : 'N/A';
            
            $this->info("ID: {$credit->id}");
            $this->info("  Groupe: {$nomGroupe}");
            $this->info("  Montant accordé: " . number_format($montantAccorde, 2) . " USD");
            $this->info("  Montant calculé (×1.225): " . number_format($montantCalcule, 2) . " USD");
            $this->info("  Montant total en base: " . number_format($montantTotal, 2) . " USD");
            $this->info("  Différence: " . number_format($montantCalcule - $montantTotal, 2) . " USD");
            
            // Vérification des paiements
            $totalPaiements = $credit->paiements()->sum('montant_paye');
            $resteAPayer = $montantTotal - $totalPaiements;
            
            $this->info("  Total payé: " . number_format($totalPaiements, 2) . " USD");
            $this->info("  Reste à payer: " . number_format($resteAPayer, 2) . " USD");
            $this->newLine();
            
            $totalMontantTotal += $montantTotal;
        }
        
        $this->info("➤ TOTAL CRÉDITS GROUPE: " . number_format($totalMontantTotal, 2) . " USD");
        $this->newLine();
    }
    
    private function afficherCreditsIndividuels($id = null)
    {
        $this->info("👤 CRÉDITS INDIVIDUELS");
        $this->info(str_repeat('-', 80));
        
        $query = Credit::where('statut_demande', 'approuve')
                      ->where('type_credit', 'individuel');
        
        if ($id) {
            $query->where('id', $id);
        }
        
        $credits = $query->get();
        
        if ($credits->isEmpty()) {
            $this->warn("Aucun crédit individuel trouvé");
            return;
        }
        
        $totalMontantTotal = 0;
        
        foreach ($credits as $credit) {
            $montantAccorde = floatval($credit->montant_accorde);
            $montantCalcule = $this->calculerMontantTotalIndividuel($montantAccorde);
            $montantTotal = floatval($credit->montant_total);
            
            // Récupérer le nom du client avec une vérification
            $nomClient = $credit->compte ? $credit->compte->nom : 'N/A';
            
            $this->info("ID: {$credit->id}");
            $this->info("  Client: {$nomClient}");
            $this->info("  Montant accordé: " . number_format($montantAccorde, 2) . " USD");
            $this->info("  Montant calculé: " . number_format($montantCalcule, 2) . " USD");
            $this->info("  Montant total en base: " . number_format($montantTotal, 2) . " USD");
            
            // Vérification des paiements
            $totalPaiements = $credit->paiements()->sum('montant_paye');
            $resteAPayer = $montantTotal - $totalPaiements;
            
            $this->info("  Total payé: " . number_format($totalPaiements, 2) . " USD");
            $this->info("  Reste à payer: " . number_format($resteAPayer, 2) . " USD");
            
            // Ajouter les pourcentages selon le montant
            $pourcentage = $this->getPourcentageParTranche($montantAccorde);
            $this->info("  Pourcentage appliqué: {$pourcentage}");
            
            $this->newLine();
            
            $totalMontantTotal += $montantTotal;
        }
        
        $this->info("➤ TOTAL CRÉDITS INDIVIDUELS: " . number_format($totalMontantTotal, 2) . " USD");
    }
    
    private function calculerMontantTotalIndividuel($montant)
    {
        // Reprendre votre logique du modèle
        if ($montant >= 100 && $montant <= 500) {
            return $montant * 0.308666 * 4;
        } elseif ($montant >= 501 && $montant <= 1000) {
            return $montant * 0.3019166667 * 4;
        } elseif ($montant >= 1001 && $montant <= 1599) {
            return $montant * 0.30866 * 4;
        } elseif ($montant >= 2000 && $montant <= 5000) {
            return $montant * 0.2985666667 * 4;
        }
        return $montant * 0.30 * 4; // Par défaut
    }
    
    private function getPourcentageParTranche($montant)
    {
        // Retourne le pourcentage selon la tranche
        if ($montant >= 100 && $montant <= 500) {
            return "30.8666%";
        } elseif ($montant >= 501 && $montant <= 1000) {
            return "30.19166667%";
        } elseif ($montant >= 1001 && $montant <= 1599) {
            return "30.866%";
        } elseif ($montant >= 2000 && $montant <= 5000) {
            return "29.85666667%";
        }
        return "30%";
    }
}