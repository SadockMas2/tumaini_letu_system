<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_comptes_groupes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comptes_groupes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_solidaire_id')->constrained('groupes_solidaires')->onDelete('cascade');
            $table->string('numero_compte')->unique();
            $table->enum('devise', ['USD', 'CDF']);
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('statut')->default('actif');
            $table->string('nom_groupe');
            $table->bigInteger('numero_groupe');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comptes_groupes');
    }
};