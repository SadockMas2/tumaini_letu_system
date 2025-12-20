<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class VerifierLogiqueSoldes extends Command
{
    protected $signature = 'verifier:logique-soldes {compte : Numéro du compte}';
    
    protected $description = 'Vérifie la logique de calcul des soldes pour un compte';
    
    public function handle()
    {
        $compteNum = $this->argument('compte');
        
        $compte = DB::table('comptes')
            ->where('numero_compte', $compteNum)
            ->first();
            
        if (!$compte) {
            $this->error("❌ Compte {$compteNum} non trouvé");
            return 1;
        }
        
        $this->info("🔍 COMPTE {$compteNum}");
        $this->info("💰 Solde actuel : " . number_format($compte->solde, 2));
        
        // Calculer selon VOTRE logique
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->orderBy('date_mouvement', 'asc')
            ->get(['type_mouvement', 'montant', 'description', 'solde_avant', 'solde_apres']);
        
        $soldeCalcule = 0;
        $details = [];
        
        foreach ($mouvements as $m) {
            $avant = $soldeCalcule;
            $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
            
            // VOTRE LOGIQUE :
            if ($typeAffichage === 'depot') {
                $soldeCalcule += $m->montant; // AJOUTER
            } elseif ($typeAffichage === 'retrait') {
                $soldeCalcule -= $m->montant; // SOUSTRAIRE
            } else {
                $soldeCalcule += $m->montant; // Ajouter tel quel
            }
            
            $apres = $soldeCalcule;
            
            $details[] = [
                'type' => $m->type_mouvement,
                'affichage' => $typeAffichage,
                'montant' => number_format($m->montant, 2),
                'operation' => $typeAffichage === 'depot' ? '+' : '-',
                'solde_calcule' => number_format($apres, 2),
                'solde_enregistre' => number_format($m->solde_apres, 2),
                'description' => substr($m->description ?? '', 0, 30)
            ];
        }
        
        $this->info("💰 Solde calculé (votre logique) : " . number_format($soldeCalcule, 2));
        $this->info("📈 Différence : " . number_format($soldeCalcule - $compte->solde, 2));
        
        // Afficher les 10 premiers mouvements
        $this->info("\n📋 10 PREMIERS MOUVEMENTS :");
        $this->table(
            ['Type', 'Affichage', 'Montant', 'Opération', 'Solde calculé', 'Solde enregistré', 'Description'],
            array_slice($details, 0, 10)
        );
        
        return 0;
    }
}