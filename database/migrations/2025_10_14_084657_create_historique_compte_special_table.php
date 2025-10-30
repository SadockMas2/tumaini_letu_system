<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historique_compte_special', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cycles')->onDelete('cascade');
            $table->decimal('montant', 20, 2);
            $table->string('devise', 5)->default('CDF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_compte_special');
    }
};
