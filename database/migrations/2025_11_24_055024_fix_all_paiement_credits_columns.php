<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            // Étendre la longueur de type_paiement
            });
    }

    public function down()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->string('type_paiement', 20)->change();
            $table->string('statut', 10)->change();
            $table->dropColumn(['capital_rembourse', 'interets_payes']);
        });
    }
};