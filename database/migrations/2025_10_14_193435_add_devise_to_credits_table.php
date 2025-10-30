<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credits', function (Blueprint $table) {
       
            if (!Schema::hasColumn('credits', 'devise')) {
                $table->string('devise', 10)->default('USD')->after('montant_total');
            }
            
            $table->decimal('taux_interet', 5, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn('devise');
            $table->decimal('taux_interet', 5, 2)->nullable(false)->change();
        });
    }
};