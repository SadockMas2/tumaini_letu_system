<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('compte_epargnes')) {
            Schema::create('compte_epargnes', function (Blueprint $table) {
                $table->id();
                $table->string('numero_compte')->unique();
                $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
                
                // CORRECTION: Utiliser le bon nom de table
                $table->foreignId('groupe_solidaire_id')->nullable()->constrained('groupes_solidaires')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->enum('type_compte', ['individuel', 'groupe_solidaire']);
                $table->decimal('solde', 15, 2)->default(0);
                $table->string('devise')->default('USD');
                $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
                $table->decimal('taux_interet', 5, 2)->default(0);
                $table->decimal('solde_minimum', 15, 2)->default(0);
                $table->text('conditions')->nullable();
          
                $table->timestamps();
                
                // Index
                $table->index('numero_compte');
                $table->index('client_id');
                $table->index('groupe_solidaire_id');
                $table->index('type_compte');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('compte_epargnes');
    }
};