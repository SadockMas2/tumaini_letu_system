<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epargnes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // agent
            $table->foreignId('cycle_id')->constrained('cycles')->onDelete('cascade');
            $table->decimal('montant', 18, 2)->default(0);
            $table->string('numero_compte_membre', 20)->nullable();

            $table->enum('devise', ['USD', 'CDF'])->default('CDF');
            $table->enum('statut', ['en_attente_dispatch', 'en_attente_validation', 'valide', 'rejet'])->default('en_attente_dispatch');
            $table->string('client_nom'); // pour l'historique
            $table->string('agent_nom');  // pour l'historique
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('epargnes');
    }
};
