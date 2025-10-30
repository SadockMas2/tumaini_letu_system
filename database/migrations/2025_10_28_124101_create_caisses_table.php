<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_caisses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            $table->enum('type_caisse', ['petite_caisse', 'grande_caisse']);
            $table->string('nom_caisse');
            $table->decimal('solde_actuel', 15, 2)->default(0);
            $table->decimal('plafond', 15, 2)->default(0);
            $table->string('devise')->default('USD');
            $table->foreignId('responsable_id')->constrained('users');
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });

        Schema::create('mouvements_caisses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses');
            $table->enum('type_mouvement', ['entree', 'sortie']);
            $table->string('source_destination');
            $table->string('motif');
            $table->string('reference');
            $table->decimal('montant', 15, 2);
            $table->enum('categorie', ['charge', 'produit', 'transfert']);
            $table->string('compte_ohada')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('date_mouvement');
            $table->text('justificatif')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mouvements_caisses');
        Schema::dropIfExists('caisses');
    }
};