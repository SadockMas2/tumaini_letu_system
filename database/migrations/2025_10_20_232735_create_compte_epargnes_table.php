<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_compte_epargnes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compte_epargnes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('numero_compte')->unique();
            $table->decimal('solde', 15, 2)->default(0);
            $table->enum('devise', ['USD', 'CDF'])->default('USD');
            $table->enum('type_epargne', ['individuel', 'groupe_solidaire'])->default('individuel');
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->decimal('taux_interet', 5, 2)->default(0);
            $table->date('date_ouverture');
            $table->date('date_derniere_operation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'statut']);
            $table->index(['type_epargne', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_epargnes');
    }
};