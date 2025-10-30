<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('epargnes', function (Blueprint $table) {
            $table->enum('type_epargne', ['individuel', 'groupe_solidaire'])->default('individuel');
        });
    }

    public function down()
    {
        Schema::table('epargnes', function (Blueprint $table) {
            $table->dropColumn('type_epargne');
        });
    }
};