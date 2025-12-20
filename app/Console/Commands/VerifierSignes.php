<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class VerifierSignes extends Command
{
    protected $signature = 'verifier:signes {--compte=}';
    
    protected $description = 'Vérifie les signes des montants par type de mouvement';
    
    public function handle()
    {
        $compteNum = $this->option('compte');
        
        $this->info('🔍 VÉRIFICATION DES SIGNES DES MONTANTS');
        
        // 1. Vérifier tous les types de mouvement
        $types = DB::table('mouvements')
            ->select('type_mouvement')
            ->distinct()
            ->pluck('type_mouvement');
        
        $this->info("\n📊 Types de mouvement : " . $types->count());
        
        $stats = [];
        
        foreach ($types as $type) {
            $affichage = MouvementHelper::getTypeAffichage($type);
            $signeAttendu = MouvementHelper::getSigne($type);
            
            // Analyser les montants pour ce type
            $result = DB::table('mouvements')
                ->where('type_mouvement', $type)
                ->selectRaw('
                    COUNT(*) as total,
                    AVG(montant) as moyenne,
                    MIN(montant) as minimum,
                    MAX(montant) as maximum,
                    SUM(CASE WHEN montant >= 0 THEN 1 ELSE 0 END) as positifs,
                    SUM(CASE WHEN montant < 0 THEN 1 ELSE 0 END) as negatifs
                ')
                ->first();
            
            $stats[] = [
                'type' => $type,
                'affichage' => $affichage,
                'signe_attendu' => $signeAttendu,
                'total' => $result->total,
                'moyenne' => round($result->moyenne, 2),
                'min' => round($result->minimum, 2),
                'max' => round($result->maximum, 2),
                'positifs' => $result->positifs,
                'negatifs' => $result->negatifs,
                'probleme' => ($affichage === 'depot' && $result->moyenne < 0) || 
                             ($affichage === 'retrait' && $result->moyenne > 0) ? '⚠️' : '✅'
            ];
        }
        
        $this->table(
            ['Type', 'Affichage', 'Signe Attendu', 'Total', 'Moyenne', 'Min', 'Max', '+', '-', 'État'],
            $stats
        );
        
        // 2. Vérifier un compte spécifique si demandé
        if ($compteNum) {
            $this->verifierCompte($compteNum);
        }
        
        return 0;
    }
    
    private function verifierCompte($numeroCompte)
    {
        $compte = DB::table('comptes')
            ->where('numero_compte', $numeroCompte)
            ->first();
            
        if (!$compte) {
            $this->error("Compte {$numeroCompte} non trouvé");
            return;
        }
        
        $this->info("\n🔍 COMPTE {$numeroCompte} - Solde actuel : " . number_format($compte->solde, 2));
        
        // Calculer le solde à partir des mouvements
        $somme = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->sum('montant');
            
        $this->info("💰 Somme des montants : " . number_format($somme, 2));
        $this->info("📈 Différence : " . number_format($somme - $compte->solde, 2));
        
        // Afficher les mouvements
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->orderBy('date_mouvement', 'asc')
            ->limit(20)
            ->get(['type_mouvement', 'montant', 'description']);
        
        $this->info("\n📋 Derniers mouvements :");
        
        foreach ($mouvements as $m) {
            $signe = $m->montant >= 0 ? '+' : '-';
            $this->line("  {$signe} " . abs($m->montant) . " - {$m->type_mouvement} - " . substr($m->description ?? '', 0, 40));
        }
    }
}