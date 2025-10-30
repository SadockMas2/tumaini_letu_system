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
        $table->decimal('solde_apres_membre', 15, 2)->nullable()->after('numero_compte_membre');
    });
}

public function down(): void
{
    Schema::table('epargnes', function (Blueprint $table) {
        $table->dropColumn('solde_apres_membre');
    });
}

};
