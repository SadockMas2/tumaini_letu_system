<?php
// database/migrations/2024_01_01_000001_create_agences_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agences', function (Blueprint $table) {
            $table->id();
            $table->string('code_agence')->unique();
            $table->string('nom_agence');
            $table->string('adresse');
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->string('devise_principale')->default('USD');
            $table->boolean('statut')->default(true);
            $table->timestamps();
        });

        // Insérer une agence par défaut
        DB::table('agences')->insert([
            'code_agence' => 'AG001',
            'nom_agence' => 'Siège Principal',
            'adresse' => 'Avenue Principale',
            'telephone' => '+243810000001',
            'email' => 'siege@microfinance.cd',
            'devise_principale' => 'USD',
            'statut' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agences');
    }
};