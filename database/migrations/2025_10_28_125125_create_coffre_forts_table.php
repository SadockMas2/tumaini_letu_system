<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_coffre_forts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('coffre_forts', function (Blueprint $table) {
            $table->id();
            $table->string('nom_coffre');
            $table->string('devise')->default('USD');
            $table->decimal('solde_actuel', 15, 2)->default(0);
            $table->foreignId('responsable_id')->constrained('users');
            $table->string('agence');
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });

        Schema::create('mouvements_coffres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffre_fort_id')->constrained('coffre_forts');
            $table->enum('type_mouvement', ['entree', 'sortie']);
            $table->string('provenance_destination');
            $table->string('motif');
            $table->string('reference');
            $table->decimal('montant', 15, 2);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('date_mouvement');
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('rapports_coffres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffre_fort_id')->constrained('coffre_forts');
            $table->date('date_rapport');
            $table->string('numero_rapport');
            $table->string('responsable_nom');
            $table->string('guichet_agence');
            
            $table->decimal('solde_ouverture', 15, 2);
            $table->decimal('total_entrees', 15, 2)->default(0);
            $table->decimal('total_sorties', 15, 2)->default(0);
            $table->decimal('solde_cloture_theorique', 15, 2);
            $table->decimal('solde_physique_reel', 15, 2);
            $table->decimal('ecart', 15, 2)->default(0);
            $table->text('explication_ecart')->nullable();
            $table->text('observations')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapports_coffres');
        Schema::dropIfExists('mouvements_coffres');
        Schema::dropIfExists('coffre_forts');
    }
};