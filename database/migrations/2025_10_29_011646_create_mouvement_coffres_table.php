<?php
// database/migrations/2024_01_01_000004_create_mouvement_coffres_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mouvement_coffres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffre_source_id')->nullable()->constrained('cash_registers');
            $table->foreignId('coffre_destination_id')->nullable()->constrained('cash_registers');
            $table->enum('type_mouvement', ['entree', 'sortie']);
            $table->decimal('montant', 15, 2);
            $table->string('devise')->default('USD');
            $table->string('source_type')->nullable();
            $table->string('destination_type')->nullable();
            $table->string('reference');
            $table->text('description');
            $table->dateTime('date_mouvement');
            $table->foreignId('operateur_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mouvement_coffres');
    }
};