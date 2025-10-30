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
    Schema::table('clients', function (Blueprint $table) {
        $table->string('activites')->nullable()->after('adresse'); // adapte la position
        $table->string('etat_civil')->nullable()->after('activites');
    });
}

public function down(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->dropColumn(['activites', 'etat_civil']);
    });
}

};
