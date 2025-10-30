<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('caisse_journalieres', function (Blueprint $table) {
            $table->string('devise')->default('CDF'); // ou 'USD', selon ton choix
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisse_journalieres', function (Blueprint $table) {
             $table->dropColumn('devise');
        });
    }
};
