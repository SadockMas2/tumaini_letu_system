<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            // Rendre credit_id nullable
            $table->foreignId('credit_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('paiement_credits', function (Blueprint $table) {
            // Remettre credit_id en non nullable (si nécessaire)
            $table->foreignId('credit_id')->nullable(false)->change();
        });
    }
};