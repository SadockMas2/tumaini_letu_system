// database/migrations/xxxx_xx_xx_xxxxxx_add_sms_fields_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('sms_notifications')->default(true)->after('phone');
            $table->boolean('sms_transactions')->default(true)->after('sms_notifications');
            $table->boolean('sms_alerts')->default(true)->after('sms_transactions');
            $table->string('phone_verified_at')->nullable()->after('sms_alerts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'sms_notifications',
                'sms_transactions',
                'sms_alerts',
                'phone_verified_at'
            ]);
        });
    }
};