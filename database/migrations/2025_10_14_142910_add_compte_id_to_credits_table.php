<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credits', function (Blueprint $table) {
            // Ajouter la colonne compte_id si elle n'existe pas
            if (!Schema::hasColumn('credits', 'compte_id')) {
                $table->foreignId('compte_id')->after('id')->constrained()->onDelete('cascade');
            }
            
            // Ou si vous avez une colonne avec un nom différent, renommez-la
            // $table->renameColumn('ancien_nom', 'compte_id');
        });
    }

    public function down()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropForeign(['compte_id']);
            $table->dropColumn('compte_id');
        });
    }
};