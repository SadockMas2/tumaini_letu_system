<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddMissingCreditMouvementsSql extends Command
{
    protected $signature = 'add:missing-credit-mouvements-sql 
                           {--dry-run : Voir ce qui sera ajouté sans appliquer}';
    
    protected $description = 'Ajoute les mouvements manquants d\'octroi de crédit via SQL avec dates correctes';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Recherche des crédits individuels sans mouvement d\'octroi...');
        
        // SQL pour identifier les crédits sans mouvement
        $sqlCount = "
            SELECT COUNT(*) as count
            FROM credits c
            WHERE c.statut_demande = 'approuve'
            AND c.type_credit = 'individuel'
            AND c.montant_accorde > 0
            AND NOT EXISTS (
                SELECT 1 FROM mouvements m 
                WHERE m.compte_id = c.compte_id 
                AND m.type_mouvement = 'credit_octroye'
                AND m.reference LIKE CONCAT('CREDIT-', c.id, '%')
            )
        ";
        
        $count = DB::select($sqlCount)[0]->count;
        
        $this->info("📊 Trouvés : {$count} crédits sans mouvement d'octroi");
        
        if ($count == 0) {
            $this->info('✅ Tous les crédits ont déjà leur mouvement d\'octroi.');
            return 0;
        }
        
        // Afficher les détails en mode dry-run
        if ($dryRun) {
            $sqlDetails = "
                SELECT 
                    c.id as credit_id,
                    c.compte_id,
                    c.montant_accorde,
                    c.date_octroi,
                    c.created_at,
                    ct.numero_compte,
                    ct.nom,
                    ct.prenom
                FROM credits c
                INNER JOIN comptes ct ON c.compte_id = ct.id
                WHERE c.statut_demande = 'approuve'
                AND c.type_credit = 'individuel'
                AND c.montant_accorde > 0
                AND NOT EXISTS (
                    SELECT 1 FROM mouvements m 
                    WHERE m.compte_id = c.compte_id 
                    AND m.type_mouvement = 'credit_octroye'
                    AND m.reference LIKE CONCAT('CREDIT-', c.id, '%')
                )
                ORDER BY c.date_octroi
            ";
            
            $credits = DB::select($sqlDetails);
            
            $this->info("\n📋 Résumé des crédits à traiter :");
            $this->table(
                ['ID', 'Compte', 'Montant', 'Date octroi', 'Créé le'],
                array_map(function($c) {
                    return [
                        $c->credit_id,
                        $c->numero_compte,
                        $c->montant_accorde . ' USD',
                        $c->date_octroi ? date('d/m/Y H:i', strtotime($c->date_octroi)) : 'N/A',
                        date('d/m/Y H:i', strtotime($c->created_at))
                    ];
                }, $credits)
            );
            
            $this->warn('🔍 Mode DRY RUN - Aucun mouvement ne sera ajouté');
            return 0;
        }
        
        $this->warn('⚠️  Cette opération va ajouter des mouvements d\'octroi de crédit manquants.');
        $this->warn('⚠️  Les mouvements auront la date d\'octroi comme date de création et de mise à jour.');
        
        if (!$this->confirm('Êtes-vous sûr de vouloir continuer ?')) {
            $this->error('❌ Opération annulée.');
            return 1;
        }
        
        $this->info('🔄 Ajout des mouvements manquants avec dates d\'octroi via SQL...');
        
        // SQL pour insérer les mouvements manquants
        $sqlInsert = "
            INSERT INTO mouvements (
                compte_id,
                type_mouvement,
                type,
                montant,
                solde_avant,
                solde_apres,
                description,
                reference,
                date_mouvement,
                nom_deposant,
                created_at,
                updated_at
            )
            SELECT 
                c.compte_id,
                'credit_octroye' as type_mouvement,
                'depot' as type,
                c.montant_accorde as montant,
                COALESCE((
                    SELECT m.solde_apres 
                    FROM mouvements m 
                    WHERE m.compte_id = c.compte_id 
                    AND m.date_mouvement < COALESCE(c.date_octroi, c.created_at)
                    ORDER BY m.date_mouvement DESC, m.id DESC 
                    LIMIT 1
                ), 0) as solde_avant,
                COALESCE((
                    SELECT m.solde_apres 
                    FROM mouvements m 
                    WHERE m.compte_id = c.compte_id 
                    AND m.date_mouvement < COALESCE(c.date_octroi, c.created_at)
                    ORDER BY m.date_mouvement DESC, m.id DESC 
                    LIMIT 1
                ), 0) + c.montant_accorde as solde_apres,
                CONCAT('Octroi de crédit individuel #', c.id, ' - Montant: ', c.montant_accorde, ' USD') as description,
                CONCAT('CREDIT-', c.id) as reference,
                COALESCE(c.date_octroi, c.created_at) as date_mouvement,
                'TUMAINI LETU Finances' as nom_deposant,
                COALESCE(c.date_octroi, c.created_at) as created_at,
                COALESCE(c.date_octroi, c.created_at) as updated_at
            FROM credits c
            WHERE c.statut_demande = 'approuve'
            AND c.type_credit = 'individuel'
            AND c.montant_accorde > 0
            AND NOT EXISTS (
                SELECT 1 FROM mouvements m 
                WHERE m.compte_id = c.compte_id 
                AND m.type_mouvement = 'credit_octroye'
                AND m.reference LIKE CONCAT('CREDIT-', c.id, '%')
            )
        ";
        
        try {
            $affected = DB::affectingStatement($sqlInsert);
            
            $this->info("✅ {$affected} mouvements ajoutés avec succès !");
            $this->info("📅 Tous les mouvements ont été créés avec leur date d'octroi respective.");
            
            // Vérification
            $this->info("\n🔍 Vérification des dates des nouveaux mouvements :");
            $sqlCheck = "
                SELECT 
                    COUNT(*) as count,
                    MIN(date_mouvement) as date_min,
                    MAX(date_mouvement) as date_max,
                    DATE(date_mouvement) as date_jour,
                    COUNT(*) as par_jour
                FROM mouvements 
                WHERE type_mouvement = 'credit_octroye'
                AND reference LIKE 'CREDIT-%'
                AND DATE(created_at) = DATE(date_mouvement)
                GROUP BY DATE(date_mouvement)
                ORDER BY DATE(date_mouvement)
            ";
            
            $results = DB::select($sqlCheck);
            
            foreach ($results as $result) {
                $this->line("  {$result->date_jour} : {$result->par_jour} mouvement(s)");
            }
            
            Log::info('Ajout mouvements octroi de crédit via SQL', [
                'mouvements_ajoutes' => $affected
            ]);
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'insertion : " . $e->getMessage());
            Log::error('Erreur ajout mouvements crédit SQL', ['error' => $e->getMessage()]);
            return 1;
        }
        
        $this->info("\n✅ Opération terminée avec succès !");
        
        return 0;
    }
}