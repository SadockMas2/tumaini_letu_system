<?php
// database/migrations/2024_01_01_xxxxxx_create_rapport_coffres_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rapport_coffres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffre_id')->constrained('cash_registers');
            $table->date('date_rapport');
            $table->string('numero_rapport')->unique();
            $table->string('responsable_coffre');
            $table->string('agence');
            $table->decimal('solde_ouverture', 15, 2);
            $table->decimal('total_entrees', 15, 2)->default(0);
            $table->decimal('total_sorties', 15, 2)->default(0);
            $table->decimal('solde_cloture_theorique', 15, 2);
            $table->decimal('solde_physique_reel', 15, 2)->nullable();
            $table->decimal('ecart', 15, 2)->nullable();
            $table->text('observations')->nullable();
            $table->enum('statut', ['brouillon', 'finalise'])->default('brouillon');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapport_coffres');
    }
};