<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Unique;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('numero_membre')->unique();;
            $table->string('nom');
            $table->string('postnom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('email')->unique();
            $table->string('telephone');
            $table->string('adresse');
            $table->string('ville');
            $table->string('pays');
            $table->string('code_postal');
            $table->bigInteger('id_createur');
            $table->string('status')->default('actif');
            $table->string('identifiant_national');
            $table->string('type_compte');
            $table->string('type_client');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
