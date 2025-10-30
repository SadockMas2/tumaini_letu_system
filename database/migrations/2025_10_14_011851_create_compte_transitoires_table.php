<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compte_transitoires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // l'agent (user)
            $table->string('agent_nom'); 
            $table->enum('devise', ['USD', 'CDF']);
            $table->decimal('solde', 18, 2)->default(0);
            $table->string('statut')->default('actif');
            $table->timestamps();
            $table->unique(['user_id', 'devise']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_transitoires');
    }
};
