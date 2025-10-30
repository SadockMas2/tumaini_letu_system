<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paiement_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained()->onDelete('cascade');
            $table->decimal('montant_paye', 10, 2);
            $table->string('methode_paiement');
            $table->dateTime('date_paiement');
            $table->string('statut')->default('complet');
            $table->text('notes')->nullable();
            $table->string('reference')->unique();
            $table->timestamps();
            
            // Index pour les performances
            $table->index('credit_id');
            $table->index('date_paiement');
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement_credits');
    }
};