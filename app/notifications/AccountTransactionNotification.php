<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Jobs\SendTransactionSms;

class AccountTransactionNotification extends Notification
{
    use Queueable;

    protected $transaction;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function via($notifiable)
    {
        // Vérifier si l'utilisateur a activé les notifications SMS
        if ($notifiable->sms_notifications && $notifiable->phone) {
            return ['database', 'sms'];
        }
        
        return ['database'];
    }

    public function toSms($notifiable)
    {
        $message = $this->formatTransactionMessage();
        
        // Dispatch le job pour envoi asynchrone
        SendTransactionSms::dispatch(
            $notifiable->phone,
            $message,
            'txn_' . $this->transaction->id
        )->onQueue('sms');
    }

    public function toDatabase($notifiable)
    {
        return [
            'transaction_id' => $this->transaction->id,
            'type' => $this->transaction->type,
            'amount' => $this->transaction->amount,
            'reference' => $this->transaction->reference,
            'balance_after' => $this->transaction->balance_after,
            'message' => $this->formatTransactionMessage(),
        ];
    }

    protected function formatTransactionMessage()
    {
        $type = $this->transaction->type == 'credit' ? 'CRÉDIT' : 'DÉBIT';
        
        return sprintf(
            "TUMAINI LETU\n%s de %s CDF\nRef: %s\nSolde: %s CDF\n%s",
            $type,
            number_format($this->transaction->amount, 0, ',', '.'),
            $this->transaction->reference,
            number_format($this->transaction->balance_after, 0, ',', '.'),
            date('d/m/Y H:i')
        );
    }
}