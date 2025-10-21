<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('achat_fournitures', function (Blueprint $table) {
        $table->id();
        $table->string('type_achat'); // carnet, livre, fourniture, autre
        $table->integer('quantite');
        $table->decimal('prix_unitaire', 15, 2);
        $table->decimal('montant_total', 15, 2);
        $table->string('devise')->default('USD');
        $table->string('fournisseur');
        $table->text('description')->nullable();
        $table->foreignId('operateur_id')->constrained('users');
        $table->date('date_achat');
        $table->string('reference')->unique();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achat_fournitures');
    }
};
