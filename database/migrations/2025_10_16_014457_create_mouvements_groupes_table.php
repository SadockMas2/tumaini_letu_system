<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_mouvements_groupes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mouvements_groupes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_groupe_id')->constrained('comptes_groupes')->onDelete('cascade');
            $table->string('numero_compte');
            $table->string('nom_groupe');
            $table->string('nom_deposant');
            $table->enum('type', ['depot', 'retrait']);
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_apres', 15, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mouvements_groupes');
    }
};