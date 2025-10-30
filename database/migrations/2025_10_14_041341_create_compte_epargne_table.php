<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compte_epargne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('client_nom');
            $table->decimal('solde', 18, 2)->default(0);
            $table->enum('devise', ['USD', 'CDF'])->default('CDF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_epargne');
    }
};
