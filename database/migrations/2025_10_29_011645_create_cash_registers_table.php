<?php
// database/migrations/2024_01_01_000002_create_cash_registers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('devise')->default('USD');
            $table->decimal('solde_actuel', 15, 2)->default(0);
            $table->decimal('solde_ouverture', 15, 2)->default(0);
            $table->decimal('solde_cloture', 15, 2)->default(0);
            $table->foreignId('agence_id')->constrained();
            $table->foreignId('responsable_id')->constrained('users');
            $table->enum('statut', ['actif', 'inactif', 'bloque'])->default('actif');
            $table->decimal('plafond_journalier', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_registers');
    }
};