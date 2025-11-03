<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->string('compte_number')->nullable()->after('operateur_id');
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->dropColumn('compte_number');
        });
    }
};
