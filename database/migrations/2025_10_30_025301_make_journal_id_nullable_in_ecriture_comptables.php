<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ecriture_comptables', function (Blueprint $table) {
            // Rendre journal_id nullable
            $table->foreignId('journal_comptable_id')->nullable()->change();
            
            // Ou si le champ s'appelle journal_comptable_id
            // $table->foreignId('journal_comptable_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('ecriture_comptables', function (Blueprint $table) {
            $table->foreignId('journal_comptable_id')->nullable(false)->change();
            
            // Ou pour journal_comptable_id
            // $table->foreignId('journal_comptable_id')->nullable(false)->change();
        });
    }
};