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
            
            // Relations
            $table->foreignId('compte_epargne_id')->constrained()->onDelete('cascade');
            $table->foreignId('epargne_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Informations mouvement
            $table->enum('type', ['depot', 'retrait']);
            $table->decimal('montant', 15, 2);
            $table->decimal('solde_avant', 15, 2);
            $table->decimal('solde_apres', 15, 2);
            $table->string('devise', 3)->default('USD');
            $table->string('reference')->unique();
            $table->text('description')->nullable();
            
            // Pour les retraits
            $table->string('beneficiaire')->nullable();
            $table->string('motif_retrait')->nullable();
            
            // Statut
            $table->enum('statut', ['pending', 'completed', 'cancelled'])->default('completed');
            
            // Timestamps
            $table->timestamp('date_mouvement')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->index('compte_epargne_id');
            $table->index('reference');
            $table->index('type');
            $table->index('date_mouvement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_epargnes');
    }
};