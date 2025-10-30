<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreditGroupeIdToCreditsTable extends Migration
{
    public function up()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_groupe_id')->nullable()->after('compte_id');
            $table->foreign('credit_groupe_id')->references('id')->on('credit_groupes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropForeign(['credit_groupe_id']);
            $table->dropColumn('credit_groupe_id');
        });
    }
}