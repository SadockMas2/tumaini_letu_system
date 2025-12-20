<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class CorrigerSoldeParCompte extends Command
{
    protected $signature = 'corriger:solde-compte 
                           {compte : Numéro du compte}
                           {--test : Voir sans corriger}';
    
    protected $description = 'Corrige le solde d\'un compte selon MouvementHelper';
    
    public function handle()
    {
        $compteNum = $this->argument('compte');
        $test = $this->option('test');
        
        $compte = DB::table('comptes')
            ->where('numero_compte', $compteNum)
            ->first();
            
        if (!$compte) {
            $this->error("❌ Compte {$compteNum} non trouvé");
            return 1;
        }
        
        $this->info("🎯 CORRECTION DU COMPTE {$compteNum}");
        $this->info("💰 Solde actuel : " . number_format($compte->solde, 2));
        
        // 1. Calculer le nouveau solde
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'type_mouvement', 'montant']);
        
        $nouveauSolde = 0;
        $operations = [];
        
        foreach ($mouvements as $m) {
            $avant = $nouveauSolde;
            $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
            
            if ($typeAffichage === 'depot') {
                $nouveauSolde += $m->montant;
                $op = '+';
            } elseif ($typeAffichage === 'retrait') {
                $nouveauSolde -= $m->montant;
                $op = '-';
            } else {
                $nouveauSolde += $m->montant;
                $op = $m->montant >= 0 ? '+' : '-';
            }
            
            $apres = $nouveauSolde;
            
            $operations[] = [
                'mouvement_id' => $m->id,
                'avant' => $avant,
                'operation' => $op . abs($m->montant),
                'apres' => $apres,
                'type' => $m->type_mouvement
            ];
        }
        
        $nouveauSolde = round($nouveauSolde, 2);
        $difference = $nouveauSolde - $compte->solde;
        
        $this->info("💰 Nouveau solde calculé : " . number_format($nouveauSolde, 2));
        $this->info("📈 Différence : " . number_format($difference, 2));
        
        if (abs($difference) < 0.01) {
            $this->info("\n✅ Le solde est déjà correct !");
            return 0;
        }
        
        // 2. Afficher les 10 premières opérations
        $this->info("\n📋 10 PREMIÈRES OPÉRATIONS :");
        $this->table(
            ['ID', 'Solde avant', 'Opération', 'Solde après', 'Type'],
            array_slice(array_map(function($op) {
                return [
                    $op['mouvement_id'],
                    number_format($op['avant'], 2),
                    $op['operation'],
                    number_format($op['apres'], 2),
                    $op['type']
                ];
            }, $operations), 0, 10)
        );
        
        // 3. Demander confirmation
        if ($test) {
            $this->warn("\n🔍 MODE TEST - Aucune modification");
            return 0;
        }
        
        $this->warn("\n⚠️  Cette opération va modifier le solde !");
        
        if (!$this->confirm("Remplacer {$compte->solde} par {$nouveauSolde} ?")) {
            $this->error('❌ Annulé');
            return 1;
        }
        
        // 4. Appliquer les corrections
        try {
            DB::beginTransaction();
            
            // Mettre à jour le solde du compte
            DB::table('comptes')
                ->where('id', $compte->id)
                ->update(['solde' => $nouveauSolde]);
            
            // Mettre à jour les soldes des mouvements
            foreach ($operations as $op) {
                DB::table('mouvements')
                    ->where('id', $op['mouvement_id'])
                    ->update([
                        'solde_avant' => $op['avant'],
                        'solde_apres' => $op['apres']
                    ]);
            }
            
            DB::commit();
            
            $this->info("\n✅ Solde corrigé avec succès !");
            $this->info("   Ancien : " . number_format($compte->solde, 2));
            $this->info("   Nouveau : " . number_format($nouveauSolde, 2));
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Erreur : ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}