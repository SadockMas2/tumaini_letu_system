<?php
// database/migrations/2024_01_01_000000_create_rapport_tresoreries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rapport_tresoreries', function (Blueprint $table) {
            $table->id();
            $table->date('date_rapport');
            $table->string('numero_rapport')->unique();
            $table->decimal('total_depots', 15, 2)->default(0);
            $table->decimal('total_retraits', 15, 2)->default(0);
            $table->decimal('solde_total_caisses', 15, 2)->default(0);
            $table->integer('nombre_operations')->default(0);
            $table->text('observations')->nullable();
            $table->enum('statut', ['brouillon', 'finalise', 'valide'])->default('brouillon');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Index pour les recherches par date
            $table->index('date_rapport');
            $table->index('numero_rapport');
            $table->index('statut');
        });

        Schema::create('rapport_tresorerie_caisses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_tresorerie_id')->constrained('rapport_tresoreries')->onDelete('cascade');
            $table->foreignId('caisse_id')->constrained('caisses')->onDelete('cascade');
            $table->string('type_caisse');
            $table->decimal('solde_initial', 15, 2)->default(0);
            $table->decimal('solde_final', 15, 2)->default(0);
            $table->integer('nombre_operations')->default(0);
            $table->decimal('total_mouvements', 15, 2)->default(0);
            $table->timestamps();

            // Index
            $table->index('rapport_tresorerie_id');
            $table->index('caisse_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapport_tresorerie_caisses');
        Schema::dropIfExists('rapport_tresoreries');
    }
};