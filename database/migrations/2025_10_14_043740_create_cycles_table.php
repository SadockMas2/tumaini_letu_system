<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('client_nom'); // nom complet du client pour l'historique
            $table->integer('numero_cycle'); // numéro du cycle incrémental pour ce client
            $table->decimal('solde_initial', 18, 2)->default(0); // montant de départ
            $table->enum('devise', ['USD', 'CDF'])->default('CDF');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['ouvert', 'clôturé'])->default('ouvert');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('cycles');
    }
};
