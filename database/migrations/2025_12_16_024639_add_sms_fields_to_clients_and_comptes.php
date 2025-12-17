<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clients
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(true)->after('telephone');
            }
            if (!Schema::hasColumn('clients', 'phone_verified')) {
                $table->boolean('phone_verified')->default(false)->after('sms_notifications');
            }
        });

        // 2. Comptes
        Schema::table('comptes', function (Blueprint $table) {
            if (!Schema::hasColumn('comptes', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(true)->after('prenom');
            }
        });

        // 3. Comptes Epargne
        Schema::table('compte_epargnes', function (Blueprint $table) {
            if (!Schema::hasColumn('compte_epargnes', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(true)->after('user_id');
            }
        });

        // 4. Groupes
   
    }

    public function down(): void
    {
        // Ne pas supprimer pour éviter la perte de données
    }
};