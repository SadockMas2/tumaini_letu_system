<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->foreignId('groupe_solidaire_id')->nullable()->constrained('groupes_solidaires')->onDelete('cascade');
            $table->enum('type_cycle', ['individuel', 'groupe_solidaire'])->default('individuel');
            
            // Rendre client_id nullable puisque maintenant on peut avoir soit client_id soit groupe_solidaire_id
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropForeign(['groupe_solidaire_id']);
            $table->dropColumn(['groupe_solidaire_id', 'type_cycle']);
        });
    }
};