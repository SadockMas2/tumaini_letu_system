<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->foreignId('superviseur_id')->nullable()->constrained('users');
        });

        Schema::table('credit_groupes', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->foreignId('superviseur_id')->nullable()->constrained('users');
        });
    }

    public function down()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['superviseur_id']);
            $table->dropColumn(['agent_id', 'superviseur_id']);
        });

        Schema::table('credit_groupes', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropForeign(['superviseur_id']);
            $table->dropColumn(['agent_id', 'superviseur_id']);
        });
    }
};