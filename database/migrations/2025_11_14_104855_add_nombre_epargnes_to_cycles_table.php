<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->integer('nombre_max_epargnes')->default(30)->after('statut');
            $table->integer('nombre_epargnes_actuel')->default(0)->after('nombre_max_epargnes');
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn(['nombre_max_epargnes', 'nombre_epargnes_actuel']);
        });
    }
};