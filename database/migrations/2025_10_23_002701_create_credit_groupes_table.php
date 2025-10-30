<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_groupes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained()->onDelete('cascade');
            $table->decimal('montant_demande', 15, 2);
            $table->decimal('montant_accorde', 15, 2)->nullable();
            $table->decimal('montant_total', 15, 2)->nullable();
            $table->decimal('frais_dossier', 8, 2)->default(0);
            $table->decimal('frais_alerte', 8, 2)->default(0);
            $table->decimal('frais_carnet', 8, 2)->default(0);
            $table->decimal('frais_adhesion', 8, 2)->default(0);
            $table->decimal('caution_totale', 8, 2)->default(0);
            $table->decimal('remboursement_hebdo_total', 8, 2)->nullable();
            $table->enum('statut_demande', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->json('repartition_membres')->nullable();
            $table->date('date_demande');
            $table->date('date_octroi')->nullable();
            $table->date('date_echeance')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_groupes');
    }
};