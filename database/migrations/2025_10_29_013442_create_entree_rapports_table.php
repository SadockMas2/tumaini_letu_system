<?php
// database/migrations/2024_01_01_xxxxxx_create_entree_rapports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('entree_rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_coffre_id')->constrained()->onDelete('cascade');
            $table->string('provenance');
            $table->string('motif');
            $table->string('reference');
            $table->decimal('montant', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entree_rapports');
    }
};