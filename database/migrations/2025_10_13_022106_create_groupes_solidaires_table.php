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
                    Schema::create('groupes_solidaires', function (Blueprint $table) {
                        $table->id();
                         $table->string('numero_groupe')->unique();
                        $table->string('nom_groupe')->unique();
                        $table->text('adresse')->nullable();
                        $table->date('date_debut_cycle');
                        $table->date('date_fin_cycle');
                        $table->timestamps();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('groupes_solidaires');
                }

};
