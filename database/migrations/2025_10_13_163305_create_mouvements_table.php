<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade');    
            $table->string('numero_compte');
            $table->string('client_nom');
            $table->string('nom_deposant')->nullable();
            $table->enum('type', ['depot', 'retrait']); // type de mouvement
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_apres', 15, 2)->nullable(); // solde après le mouvement
            $table->string('description')->nullable(); // facultatif
            $table->boolean('est_cloture')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements');
    }
};
