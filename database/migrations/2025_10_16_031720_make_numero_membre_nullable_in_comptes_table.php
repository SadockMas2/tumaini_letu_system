<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->string('numero_membre')->nullable()->change();
            $table->string('postnom')->nullable()->change();
            $table->string('prenom')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->string('numero_membre')->nullable(false)->change();
            $table->string('postnom')->nullable(false)->change();
            $table->string('prenom')->nullable(false)->change();
        });
    }
};