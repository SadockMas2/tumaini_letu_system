<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groupes_solidaires', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_groupe')->unique()->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('groupes_solidaires', function (Blueprint $table) {
            $table->dropColumn('numero_groupe');
        });
    }
};
