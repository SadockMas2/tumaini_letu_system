<?php

namespace App\Notifications;

use App\Jobs\SendTransactionSms;
use App\Models\Mouvement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionSmsNotification extends Notification
{
    use Queueable;

    protected $mouvement;

    public function __construct(Mouvement $mouvement)
    {
        $this->mouvement = $mouvement;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        // Cette méthode crée juste la notification dans la base
        // L'envoi SMS réel se fait via le job
        
        // Déclencher le job d'envoi SMS
        $this->enqueueSmsJob($notifiable);
        
        return [
            'mouvement_id' => $this->mouvement->id,
            'type' => $this->mouvement->type,
            'amount' => $this->mouvement->montant,
            'reference' => $this->mouvement->reference,
            'balance_after' => $this->mouvement->solde_apres,
            'message' => $this->formatMessage(),
        ];
    }

    protected function formatMessage(): string
    {
        $type = $this->mouvement->type === 'depot' ? 'CRÉDIT' : 'DÉBIT';
        $action = $this->mouvement->type === 'depot' ? 'déposé' : 'retiré';
        
        return sprintf(
            "TUMAINI LETU\n%s de %s %s\nRef: %s\nNouveau solde: %s %s\n%s",
            $type,
            number_format($this->mouvement->montant, 0, ',', '.'),
            $this->mouvement->devise,
            $this->mouvement->reference,
            number_format($this->mouvement->solde_apres, 0, ',', '.'),
            $this->mouvement->devise,
            now()->format('d/m/Y H:i')
        );
    }

    protected function enqueueSmsJob($notifiable)
    {
        $phoneNumber = $notifiable->phone;
        
        if (!$phoneNumber) {
            return;
        }
        
        // Formater le numéro
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        if (!str_starts_with($phoneNumber, '243')) {
            $phoneNumber = '243' . ltrim($phoneNumber, '0');
        }
        
        $message = $this->formatMessage();
        
        // Envoyer le job
        SendTransactionSms::dispatch(
            $phoneNumber,
            $message,
            'txn_' . $this->mouvement->id
        )->onQueue('sms');
    }
}