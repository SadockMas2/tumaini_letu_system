<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->foreignId('groupe_solidaire_id')->nullable()->constrained('groupes_solidaires')->onDelete('cascade');
            $table->string('type_compte')->default('individuel'); // 'individuel' ou 'groupe_solidaire'
            
            // Rendre client_id nullable puisque les comptes de groupe n'ont pas de client
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropForeign(['groupe_solidaire_id']);
            $table->dropColumn(['groupe_solidaire_id', 'type_compte']);
        });
    }
};