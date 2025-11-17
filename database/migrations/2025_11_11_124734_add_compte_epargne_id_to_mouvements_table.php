<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->foreignId('compte_epargne_id')
                  ->nullable()
                  ->after('compte_id')
                  ->constrained('compte_epargnes')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->dropForeign(['compte_epargne_id']);
            $table->dropColumn('compte_epargne_id');
        });
    }
};