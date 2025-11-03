<?php
// database/migrations/2024_01_01_000007_add_type_caisse_to_caisses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            // Ajouter la colonne type_caisse si elle n'existe pas
            if (!Schema::hasColumn('caisses', 'type_caisse')) {
                $table->enum('type_caisse', ['petite_caisse', 'grande_caisse', 'caisse_operations'])
                    ->default('grande_caisse')
                    ->after('id');
            }
            
            // Ajouter la colonne devise si elle n'existe pas
            if (!Schema::hasColumn('caisses', 'devise')) {
                $table->string('devise')->default('USD')->after('type_caisse');
            }
            
            // Ajouter la colonne plafond si elle n'existe pas
            if (!Schema::hasColumn('caisses', 'plafond')) {
                $table->decimal('plafond', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            // Vous pouvez choisir de supprimer les colonnes si nécessaire
            // $table->dropColumn(['type_caisse', 'devise', 'plafond']);
        });
    }
};