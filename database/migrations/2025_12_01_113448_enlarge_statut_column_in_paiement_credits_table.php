<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            // Agrandir la colonne statut de 20 à 50 caractères
            $table->string('statut', 50)->change();
        });
    }

    public function down()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            // Revenir à l'ancienne taille si on rollback
            $table->string('statut', 20)->change();
        });
    }
};