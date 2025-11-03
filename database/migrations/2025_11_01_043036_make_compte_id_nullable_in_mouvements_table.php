<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // Rendre compte_id nullable
            $table->foreignId('compte_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->foreignId('compte_id')->nullable(false)->change();
        });
    }
};