<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCautionsAndEcheanciersTables extends Migration
{
    public function up()
    {
        // Table des cautions bloquées
        Schema::create('cautions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compte_id');
            $table->unsignedBigInteger('credit_groupe_id');
            $table->decimal('montant', 10, 2);
            $table->enum('statut', ['bloquee', 'debloquee', 'utilisee'])->default('bloquee');
            $table->dateTime('date_blocage');
            $table->dateTime('date_deblocage')->nullable();
            $table->timestamps();

            $table->foreign('compte_id')->references('id')->on('comptes');
            $table->foreign('credit_groupe_id')->references('id')->on('credit_groupes');
        });

        // Table des échéanciers
        Schema::create('echeanciers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_id')->nullable();
            $table->unsignedBigInteger('credit_groupe_id');
            $table->unsignedBigInteger('compte_id');
            $table->integer('semaine');
            $table->date('date_echeance');
            $table->decimal('montant_a_payer', 10, 2);
            $table->decimal('capital_restant', 10, 2);
            $table->enum('statut', ['a_venir', 'echeance', 'paye', 'en_retard'])->default('a_venir');
            $table->dateTime('date_paiement')->nullable();
            $table->timestamps();

            $table->foreign('credit_id')->references('id')->on('credits');
            $table->foreign('credit_groupe_id')->references('id')->on('credit_groupes');
            $table->foreign('compte_id')->references('id')->on('comptes');
        });

        // Ajouter colonne caution_bloquee dans credits
        Schema::table('credits', function (Blueprint $table) {
            $table->decimal('caution_bloquee', 10, 2)->default(0)->after('caution');
        });
    }

    public function down()
    {
        Schema::dropIfExists('echeanciers');
        Schema::dropIfExists('cautions');
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn('caution_bloquee');
        });
    }
}