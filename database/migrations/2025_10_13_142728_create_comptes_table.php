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
        Schema::create('comptes', function (Blueprint $table) {
              $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('nom');
            $table->string('postnom');
            $table->string('prenom');
            $table->string('numero_compte')->unique(); // 
            $table->string('numero_membre'); 
            $table->enum('devise', ['USD', 'CDF']);
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('statut')->default('actif'); // actif / bloqué / clôturé
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
