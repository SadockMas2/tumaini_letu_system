<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // Rendre tous les champs liés aux comptes nullable
            $table->foreignId('compte_id')->nullable()->change();
            $table->string('numero_compte')->nullable()->change();
            $table->string('client_nom')->nullable()->change();
            $table->string('type_mouvement')->nullable()->change();
            $table->string('reference')->nullable()->change();
            $table->dateTime('date_mouvement')->nullable()->change();
            
            // Ajouter les champs manquants s'ils n'existent pas
            if (!Schema::hasColumn('mouvements', 'solde_avant')) {
                $table->decimal('solde_avant', 15, 2)->nullable()->after('montant');
            }
            
            if (!Schema::hasColumn('mouvements', 'devise')) {
                $table->string('devise', 3)->default('USD')->nullable()->after('compte_number');
            }
        });
    }

    public function down()
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // Optionnel: remettre les champs en non-nullable en rollback
        });
    }
};