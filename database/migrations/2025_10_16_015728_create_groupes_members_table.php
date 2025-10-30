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
        Schema::create('groupes_membres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_solidaire_id')
                ->constrained('groupes_solidaires')
                ->onDelete('cascade');
            $table->foreignId('client_id')
                ->constrained('clients')
                ->onDelete('cascade');
            $table->timestamps();

            // ✅ clé unique pour éviter les doublons
            $table->unique(['groupe_solidaire_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groupes_membres');
    }
};

