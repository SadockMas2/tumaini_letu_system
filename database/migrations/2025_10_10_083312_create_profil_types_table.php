<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
            */
        public function up(): void
        {
            Schema::create('profil_types', function (Blueprint $table) {
                $table->id();
                $table->string('nom_profil');
                $table->text('description')->nullable();
                $table->json('permissions')->nullable(); // pour stocker les habilitations
                $table->timestamps();
            });
        }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_types');
    }
};
