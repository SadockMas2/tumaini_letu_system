<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\MouvementHelper;

class CalculerSoldeCorrectement extends Command
{
    protected $signature = 'calculer:solde-correct 
                           {compte : Numéro du compte}
                           {--show : Afficher le détail}';
    
    protected $description = 'Calcule le solde CORRECTEMENT selon MouvementHelper';
    
    public function handle()
    {
        $compteNum = $this->argument('compte');
        $show = $this->option('show');
        
        $compte = DB::table('comptes')
            ->where('numero_compte', $compteNum)
            ->first();
            
        if (!$compte) {
            $this->error("❌ Compte {$compteNum} non trouvé");
            return 1;
        }
        
        $this->info("🔍 COMPTE {$compteNum} - Solde actuel : " . number_format($compte->solde, 2));
        
        // Récupérer tous les mouvements
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compte->id)
            ->orderBy('date_mouvement', 'asc')
            ->orderBy('id', 'asc')
            ->get(['type_mouvement', 'montant', 'description']);
        
        // CALCULER selon MouvementHelper
        $soldeCalcule = 0;
        $details = [];
        
        foreach ($mouvements as $m) {
            $avant = $soldeCalcule;
            
            // UTILISER MouvementHelper pour déterminer l'opération
            $typeAffichage = MouvementHelper::getTypeAffichage($m->type_mouvement);
            
            if ($typeAffichage === 'depot') {
                // DÉPÔT : AJOUTER le montant (qui est positif)
                $soldeCalcule += $m->montant;
                $operation = 'DÉPÔT (+)';
            } elseif ($typeAffichage === 'retrait') {
                // RETRAIT : SOUSTRAIRE le montant (qui est positif)
                $soldeCalcule -= $m->montant;
                $operation = 'RETRAIT (-)';
            } elseif ($typeAffichage === 'neutre') {
                // NEUTRE : selon le signe du montant
                $soldeCalcule += $m->montant;
                $operation = 'NEUTRE';
            } else {
                // AUTRE : selon le signe du montant
                $soldeCalcule += $m->montant;
                $operation = 'AUTRE';
            }
            
            $apres = $soldeCalcule;
            
            $details[] = [
                'type' => $m->type_mouvement,
                'affichage' => $typeAffichage,
                'montant_base' => number_format($m->montant, 2),
                'operation' => $operation,
                'solde_calcule' => number_format($apres, 2),
                'description' => substr($m->description ?? '', 0, 30)
            ];
        }
        
        $soldeCalcule = round($soldeCalcule, 2);
        
        $this->info("💰 Solde calculé : " . number_format($soldeCalcule, 2));
        $this->info("📈 Différence : " . number_format($soldeCalcule - $compte->solde, 2));
        
        if ($show) {
            $this->info("\n📋 DÉTAIL DU CALCUL :");
            $this->table(
                ['Type', 'Affichage', 'Montant (base)', 'Opération', 'Solde calculé', 'Description'],
                $details
            );
        }
        
        // Afficher les problèmes spécifiques
        $this->analyserProblemes($mouvements);
        
        return 0;
    }
    
    private function analyserProblemes($mouvements)
    {
        $this->info("\n🔍 ANALYSE DES TYPES DE MOUVEMENT :");
        
        $types = [];
        foreach ($mouvements as $m) {
            if (!isset($types[$m->type_mouvement])) {
                $types[$m->type_mouvement] = [
                    'count' => 0,
                    'total' => 0,
                    'affichage' => MouvementHelper::getTypeAffichage($m->type_mouvement),
                    'signe' => MouvementHelper::getSigne($m->type_mouvement, $m->montant)
                ];
            }
            
            $types[$m->type_mouvement]['count']++;
            $types[$m->type_mouvement]['total'] += $m->montant;
        }
        
        $this->table(
            ['Type', 'Affichage', 'Signe', 'Occurrences', 'Total montant'],
            array_map(function($type, $data) {
                return [
                    $type,
                    $data['affichage'],
                    $data['signe'],
                    $data['count'],
                    number_format($data['total'], 2)
                ];
            }, array_keys($types), array_values($types))
        );
    }
}