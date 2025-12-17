<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvement_epargnes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_epargne_id')->constrained()->onDelete('cascade');
            $table->foreignId('epargne_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['depot', 'retrait']);
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_avant', 15, 2);
            $table->decimal('solde_apres', 15, 2);
            $table->string('devise', 3)->default('USD');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('operateur_nom')->nullable();
            $table->foreignId('operateur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index
            $table->index('compte_epargne_id');
            $table->index('type');
            $table->index('reference');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_epargnes');
    }
};