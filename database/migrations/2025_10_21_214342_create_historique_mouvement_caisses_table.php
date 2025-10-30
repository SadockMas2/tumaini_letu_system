<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('historique_mouvement_caisses', function (Blueprint $table) {
            $table->id();
            $table->date('date_cloture');
            $table->decimal('total_depots', 15, 2);
            $table->decimal('total_retraits', 15, 2);
            $table->decimal('solde_final', 15, 2);
            $table->integer('nombre_operations');
            $table->foreignId('cloture_par')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('historique_mouvement_caisses');
    }
};