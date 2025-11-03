<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Dans la migration
public function up()
{
    Schema::table('historique_compte_special', function (Blueprint $table) {
        $table->unsignedBigInteger('cycle_id')->nullable()->change();
        
        // Optionnel : ajouter la colonne description si elle n'existe pas
        if (!Schema::hasColumn('historique_compte_special', 'description')) {
            $table->text('description')->nullable()->after('devise');
        }
    });
}

public function down()
{
    Schema::table('historique_compte_special', function (Blueprint $table) {
        $table->unsignedBigInteger('cycle_id')->nullable(false)->change();
        
        if (Schema::hasColumn('historique_compte_special', 'description')) {
            $table->dropColumn('description');
        }
    });
}
};
