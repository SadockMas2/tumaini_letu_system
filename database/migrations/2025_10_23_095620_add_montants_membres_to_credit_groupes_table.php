<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credit_groupes', function (Blueprint $table) {
            $table->json('montants_membres')->nullable()->after('repartition_membres');
        });
    }

    public function down()
    {
        Schema::table('credit_groupes', function (Blueprint $table) {
            $table->dropColumn('montants_membres');
        });
    }
};