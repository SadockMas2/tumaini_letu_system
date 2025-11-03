<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Supprimer la table si elle existe
        Schema::dropIfExists('ecriture_comptables');
        
        // Recréer la table avec la structure correcte
        Schema::create('ecriture_comptables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_comptable_id')->constrained()->onDelete('cascade');
            $table->string('reference_operation');
            $table->string('type_operation');
            $table->string('compte_number');
            $table->string('libelle');
            $table->decimal('montant_debit', 15, 2)->default(0);
            $table->decimal('montant_credit', 15, 2)->default(0);
            $table->dateTime('date_ecriture');
            $table->dateTime('date_valeur')->useCurrent(); // Valeur par défaut
            $table->string('devise', 10)->default('USD');
            $table->string('statut')->default('brouillon');
            $table->text('notes')->nullable();
            $table->string('piece_jointe')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Index
            $table->index(['journal_comptable_id', 'date_ecriture']);
            $table->index('compte_number');
            $table->index('type_operation');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ecriture_comptables');
    }
};