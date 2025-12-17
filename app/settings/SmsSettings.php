<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SmsSettings extends Settings
{
    public string $api_url;
    public string $api_id;
    public string $api_password;
    public string $sender_id;
    public string $sms_type;
    public string $encoding;
    public int $validity_period;
    public bool $auto_check_balance;
    public bool $enable_sms_notifications;
    public bool $transaction_sms_enabled;
    public bool $alert_sms_enabled;
    public bool $marketing_sms_enabled;
    public bool $otp_sms_enabled;
    public string $default_phone_prefix;
    
    public static function group(): string
    {
        return 'sms';
    }
}