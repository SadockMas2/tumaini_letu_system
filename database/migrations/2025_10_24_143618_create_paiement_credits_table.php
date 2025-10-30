<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaiementCreditsTable extends Migration
{
    public function up()
    {
        Schema::create('paiement_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_id');
            $table->unsignedBigInteger('compte_id');
            $table->decimal('montant_paye', 10, 2);
            $table->dateTime('date_paiement');
            $table->enum('type_paiement', ['especes', 'mobile_money', 'virement']);
            $table->string('reference')->unique();
            $table->enum('statut', ['complete', 'en_attente', 'annule'])->default('complete');
            $table->timestamps();

            $table->foreign('credit_id')->references('id')->on('credits');
            $table->foreign('compte_id')->references('id')->on('comptes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paiement_credits');
    }
}