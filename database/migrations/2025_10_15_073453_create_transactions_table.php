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
        Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->enum('type', ['approvisionnement', 'decaissement', 'credit', 'remboursement']);
        $table->string('source_type');
        $table->unsignedBigInteger('source_id');
        $table->string('destination_type');
        $table->unsignedBigInteger('destination_id');
        $table->unsignedBigInteger('membre_id')->nullable();
        $table->decimal('montant', 18, 2);
        $table->enum('devise', ['CDF','USD']);
        $table->text('description')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
