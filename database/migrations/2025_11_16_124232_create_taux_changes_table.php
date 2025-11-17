<?php
// database/migrations/2024_01_01_000000_create_taux_changes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTauxChangesTable extends Migration
{
    public function up()
    {
        Schema::create('taux_changes', function (Blueprint $table) {
            $table->id();
            $table->string('devise_source', 3); // USD, CDF
            $table->string('devise_destination', 3); // USD, CDF
            $table->decimal('taux', 10, 4); // Taux avec 4 décimales
            $table->dateTime('date_effet');
            $table->boolean('est_actif')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['devise_source', 'devise_destination', 'est_actif']);
            $table->index('date_effet');
        });
    }

    public function down()
    {
        Schema::dropIfExists('taux_changes');
    }
}