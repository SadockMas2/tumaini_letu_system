<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('mouvements', function (Blueprint $table) {
        $table->foreignId('caisse_id')->nullable()->after('id')->constrained();
     
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mouvements', function (Blueprint $table) {
            //
        });
    }
};
