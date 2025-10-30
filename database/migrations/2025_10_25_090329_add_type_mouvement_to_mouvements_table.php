<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->string('type_mouvement')->nullable()->after('type');
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->dropColumn('type_mouvement');
        });
    }
};