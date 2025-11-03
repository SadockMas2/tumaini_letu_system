<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // Vérifier et ajouter chaque colonne manquante
            if (!Schema::hasColumn('mouvements', 'caisse_id')) {
                $table->foreignId('caisse_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('mouvements', 'solde_avant')) {
                $table->decimal('solde_avant', 15, 2)->nullable()->after('montant');
            }
            
            if (!Schema::hasColumn('mouvements', 'compte_number')) {
                $table->string('compte_number')->nullable()->after('operateur_id');
            }
            
            if (!Schema::hasColumn('mouvements', 'devise')) {
                $table->string('devise', 3)->default('USD')->after('compte_number');
            }
            
            if (!Schema::hasColumn('mouvements', 'type_mouvement')) {
                $table->string('type_mouvement')->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('mouvements', 'reference')) {
                $table->string('reference')->nullable()->after('type_mouvement');
            }
            
            if (!Schema::hasColumn('mouvements', 'date_mouvement')) {
                $table->dateTime('date_mouvement')->nullable()->after('devise');
            }
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // On ne supprime pas les colonnes en rollback pour éviter la perte de données
            // $table->dropColumn(['caisse_id', 'solde_avant', 'compte_number', 'devise', 'type_mouvement', 'reference', 'date_mouvement']);
        });
    }
};