<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
        
            if (Schema::hasColumn('sms_logs', 'phone_number')) {
                $table->renameColumn('phone_number', 'telephone');
            }
            
          
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
          
        });
    }
};