<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            $table->enum('devise', ['USD', 'CDF']);
            $table->decimal('solde', 18, 2)->default(0);
            $table->string('nom')->nullable(); // ex: "Caisse Microfinance USD"
            $table->enum('statut', ['actif','bloque'])->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};
