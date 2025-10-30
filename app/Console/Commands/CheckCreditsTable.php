<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckCreditsTable extends Command
{
    protected $signature = 'check:credits-table';
    protected $description = 'Vérifier la structure de la table credits';

    public function handle()
    {
        $this->info("=== VÉRIFICATION STRUCTURE TABLE CREDITS ===");
        
        // Vérifier les colonnes
        $columns = DB::getSchemaBuilder()->getColumnListing('credits');
        $this->info("Colonnes disponibles:");
        foreach ($columns as $column) {
            $this->info(" - {$column}");
        }
        
        // Vérifier si credit_groupe_id existe
        if (in_array('credit_groupe_id', $columns)) {
            $this->info("✅ Colonne 'credit_groupe_id' trouvée");
        } else {
            $this->error("❌ Colonne 'credit_groupe_id' manquante");
        }
        
        // Vérifier les contraintes
        $this->info("\nContraintes de la table:");
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'credits' AND TABLE_SCHEMA = DATABASE()
        ");
        
        foreach ($constraints as $constraint) {
            $this->info(" - {$constraint->CONSTRAINT_NAME} sur {$constraint->COLUMN_NAME}");
        }
    }
}