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
        Schema::create('comptes_bancaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom_banque');
            $table->string('numero_compte');
            $table->enum('devise', ['CDF','USD']);
            $table->decimal('solde', 18, 2)->default(0);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes_bancaires');
    }
};
