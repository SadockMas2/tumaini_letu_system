<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->decimal('capital_rembourse', 10, 2)->default(0)->after('montant_paye');
            $table->decimal('interets_payes', 10, 2)->default(0)->after('capital_rembourse');
        });
    }

    public function down()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            $table->dropColumn(['capital_rembourse', 'interets_payes']);
        });
    }
};