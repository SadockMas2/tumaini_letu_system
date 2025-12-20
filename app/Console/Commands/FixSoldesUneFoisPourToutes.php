<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSoldesUneFoisPourToutes extends Command
{
    protected $signature = 'fix:soldes-une-fois';
    
    protected $description = 'Corrige les soldes une fois pour toutes';
    
    public function handle()
    {
        $this->info('🎯 CORRECTION DÉFINITIVE DES SOLDES');
        $this->info('===================================');
        
        if (!$this->confirm('⚠️  Cette opération est IRREVERSIBLE. Continuer ?')) {
            $this->error('❌ Annulé');
            return 1;
        }
        
        // Pour chaque compte, faire simplement : SOMME(montant)
        // Parce que dans votre base, les montants sont déjà signés correctement !
        
        $comptes = DB::table('comptes')->get(['id', 'numero_compte', 'solde']);
        
        $corriges = 0;
        
        foreach ($comptes as $compte) {
            // Somme des montants (déjà signés)
            $result = DB::table('mouvements')
                ->where('compte_id', $compte->id)
                ->selectRaw('COALESCE(SUM(montant), 0) as somme')
                ->first();
            
            $nouveauSolde = round($result->somme ?? 0, 2);
            
            if (abs($compte->solde - $nouveauSolde) > 0.01) {
                DB::table('comptes')
                    ->where('id', $compte->id)
                    ->update(['solde' => $nouveauSolde]);
                
                $corriges++;
                
                $this->line("  ✅ {$compte->numero_compte} : " . 
                    number_format($compte->solde, 2) . " → " . 
                    number_format($nouveauSolde, 2));
            }
        }
        
        $this->info("\n✅ {$corriges} comptes corrigés sur {$comptes->count()}");
        
        // Afficher un exemple
        $this->afficherExemple();
        
        return 0;
    }
    
    private function afficherExemple()
    {
        $this->info("\n📊 EXEMPLE : Compte C00001");
        
        $mouvements = DB::table('mouvements')
            ->where('compte_id', 1) // C00001
            ->orderBy('date_mouvement', 'asc')
            ->limit(5)
            ->get(['type_mouvement', 'montant', 'description']);
        
        $solde = 0;
        
        foreach ($mouvements as $m) {
            $avant = $solde;
            $solde += $m->montant;
            
            $signe = $m->montant >= 0 ? '+' : '-';
            
            $this->line("  {$avant} {$signe} " . abs($m->montant) . " = {$solde} | {$m->type_mouvement}");
        }
    }
}