<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compte_special', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->default('Compte de la structure'); // Nom du compte spécial
            $table->decimal('solde', 20, 2)->default(0); // Solde total
            $table->string('devise', 5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_special');
    }
};
