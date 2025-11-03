<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Dans la migration
public function up()
{
    Schema::table('cautions', function (Blueprint $table) {
        $table->unsignedBigInteger('credit_id')->nullable()->after('compte_id');
        $table->foreign('credit_id')->references('id')->on('credits')->onDelete('cascade');
        
        // Rendre credit_groupe_id nullable
        $table->unsignedBigInteger('credit_groupe_id')->nullable()->change();
    });
}

public function down()
{
    Schema::table('cautions', function (Blueprint $table) {
        $table->dropForeign(['credit_id']);
        $table->dropColumn('credit_id');
        $table->unsignedBigInteger('credit_groupe_id')->nullable(false)->change();
    });
}
};
