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
    Schema::table('sms_logs', function (Blueprint $table) {
        $table->string('type', 50)->default('transaction')->change();
    });
}

public function down()
{
    Schema::table('sms_logs', function (Blueprint $table) {
        $table->string('type', 20)->default('transaction')->change();
    });
}
};
