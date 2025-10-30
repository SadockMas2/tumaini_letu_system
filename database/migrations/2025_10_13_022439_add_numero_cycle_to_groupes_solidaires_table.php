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
        Schema::table('groupes_solidaires', function (Blueprint $table) {
            $table->string('numero_cycle')->after('nom_groupe')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('groupes_solidaires', function (Blueprint $table) {
            $table->dropColumn('numero_cycle');
        });
    }
};
