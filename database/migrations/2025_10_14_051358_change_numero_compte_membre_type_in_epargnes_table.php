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
    Schema::table('epargnes', function (Blueprint $table) {
        $table->string('numero_compte_membre', 20)->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('epargnes', function (Blueprint $table) {
        $table->dateTime('numero_compte_membre')->nullable()->change();
    });
}

};
