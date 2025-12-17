<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->text('message');
            $table->string('message_id')->nullable();
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending', 'undeliverable'])->default('pending');
            $table->enum('type', ['transaction', 'alert', 'marketing', 'otp', 'reminder'])->default('transaction');
            $table->text('remarks')->nullable();
            $table->string('uid')->nullable();
            $table->json('response_data')->nullable();
            $table->string('delivery_status')->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->timestamp('sent_at')->nullable();
            
            // Relations
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('compte_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('compte_epargne_id')->nullable()->constrained('compte_epargnes')->onDelete('set null');
            $table->foreignId('mouvement_id')->nullable()->constrained()->onDelete('set null');
            
            // Indexes
            $table->index('phone_number');
            $table->index('status');
            $table->index('uid');
            $table->index('type');
            $table->index('sent_at');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};