<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // caissier qui exécute
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade'); // agent pour lequel on dispatch
            $table->string('agent_nom'); 
            $table->enum('devise', ['USD','CDF']);
            $table->decimal('montant_total', 18, 2);
            $table->text('notes')->nullable();
            $table->enum('statut', ['effectue','partiel','echec'])->default('effectue');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
