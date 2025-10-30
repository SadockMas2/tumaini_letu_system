<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // obligatoire
            $table->string('email')->unique();      // obligatoire
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');             // obligatoire
            $table->rememberToken();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
