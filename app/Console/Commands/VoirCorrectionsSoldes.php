<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VoirCorrectionsSoldes extends Command
{
    protected $signature = 'voir:corrections-soldes 
                           {--compte= : Numéro de compte spécifique}
                           {--limit=10 : Nombre max de comptes à afficher}
                           {--export : Exporter les résultats en CSV}';
    
    protected $description = 'Voir les corrections qui seront apportées aux soldes';
    
    public function handle()
    {
        $compteNum = $this->option('compte');
        $limit = $this->option('limit');
        $export = $this->option('export');
        
        $this->info('🔍 SIMULATION DES CORRECTIONS DE SOLDES');
        $this->info('========================================');
        
        // Récupérer les comptes
        $query = DB::table('comptes')->select('id', 'numero_compte', 'solde');
        
        if ($compteNum) {
            $query->where('numero_compte', $compteNum);
        } else {
            $query->limit($limit);
        }
        
        $comptes = $query->get();
        
        $this->info("📊 Analyse de {$comptes->count()} comptes...");
        
        $corrections = [];
        
        foreach ($comptes as $compte) {
            // Calculer le solde à partir des mouvements
            $result = DB::table('mouvements')
                ->where('compte_id', $compte->id)
                ->selectRaw('COALESCE(SUM(montant), 0) as somme_montants')
                ->first();
            
            $soldeCalcule = round($result->somme_montants ?? 0, 2);
            $soldeActuel = round($compte->solde, 2);
            $difference = $soldeCalcule - $soldeActuel;
            
            if (abs($difference) > 0.01) {
                $corrections[] = [
                    'id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'solde_actuel' => $soldeActuel,
                    'solde_calcule' => $soldeCalcule,
                    'difference' => $difference,
                    'pourcentage' => ($soldeActuel != 0) ? abs(($difference / $soldeActuel) * 100) : 100
                ];
            }
        }
        
        // Afficher les résultats
        if (!empty($corrections)) {
            $this->info("\n🚨 " . count($corrections) . " COMPTES SERAIENT CORRIGÉS");
            
            $this->table(
                ['Compte', 'Solde actuel', 'Solde calculé', 'Différence', 'Écart %'],
                array_map(function($c) {
                    $ecart = abs($c['difference']) > 100 ? '🔴' : (abs($c['difference']) > 10 ? '🟡' : '🟢');
                    return [
                        $c['numero_compte'],
                        number_format($c['solde_actuel'], 2),
                        number_format($c['solde_calcule'], 2),
                        number_format($c['difference'], 2),
                        number_format($c['pourcentage'], 1) . '% ' . $ecart
                    ];
                }, $corrections)
            );
            
            // Statistiques
            $totalDifference = array_sum(array_column($corrections, 'difference'));
            $moyenneDifference = $totalDifference / count($corrections);
            
            $this->info("\n📈 STATISTIQUES :");
            $this->line("  • Total à corriger : " . count($corrections) . " comptes");
            $this->line("  • Différence totale : " . number_format($totalDifference, 2));
            $this->line("  • Différence moyenne : " . number_format($moyenneDifference, 2));
            
            // Afficher un exemple détaillé
            $this->afficherExempleDetaille($corrections[0]['id']);
            
            if ($export) {
                $this->exporterCSV($corrections);
            }
            
            $this->warn("\n⚠️  CE N'EST QU'UNE SIMULATION");
            $this->info("Pour appliquer : php artisan appliquer:corrections-soldes");
            
        } else {
            $this->info("\n✅ Tous les soldes semblent déjà corrects !");
        }
        
        return 0;
    }
    
    private function afficherExempleDetaille($compteId)
    {
        $this->info("\n📋 EXEMPLE DÉTAILLÉ POUR LE COMPTE #{$compteId}:");
        
        // Récupérer les mouvements
        $mouvements = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->orderBy('date_mouvement', 'asc')
            ->limit(15)
            ->get(['type_mouvement', 'montant', 'description']);
        
        $solde = 0;
        $details = [];
        
        foreach ($mouvements as $m) {
            $avant = $solde;
            $solde += $m->montant;
            $apres = $solde;
            
            $details[] = [
                'type' => $m->type_mouvement,
                'montant' => number_format($m->montant, 2),
                'signe' => $m->montant >= 0 ? '+' : '-',
                'solde_avant' => number_format($avant, 2),
                'solde_apres' => number_format($apres, 2),
                'description' => substr($m->description ?? '', 0, 30)
            ];
        }
        
        $this->table(
            ['Type', 'Montant', 'Signe', 'Solde avant', 'Solde après', 'Description'],
            $details
        );
        
        // Vérifier la cohérence
        $total = DB::table('mouvements')
            ->where('compte_id', $compteId)
            ->sum('montant');
            
        $this->info("💰 TOTAL CALCULÉ : " . number_format($total, 2));
    }
    
    private function exporterCSV($corrections)
    {
        $filename = 'corrections_soldes_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/' . $filename);
        
        $handle = fopen($filepath, 'w');
        
        // En-tête
        fputcsv($handle, [
            'ID Compte', 
            'Numéro Compte', 
            'Solde Actuel', 
            'Solde Calculé', 
            'Différence',
            'Pourcentage'
        ]);
        
        // Données
        foreach ($corrections as $correction) {
            fputcsv($handle, [
                $correction['id'],
                $correction['numero_compte'],
                $correction['solde_actuel'],
                $correction['solde_calcule'],
                $correction['difference'],
                $correction['pourcentage']
            ]);
        }
        
        fclose($handle);
        
        $this->info("\n💾 Exporté vers : " . $filepath);
    }
}