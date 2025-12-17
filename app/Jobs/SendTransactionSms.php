<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SendTransactionSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phoneNumber;
    public $message;
    public $uid;
    public $relatedId;
    public $relatedType;

    public function __construct($phoneNumber, $message, $uid = null, $relatedId = null, $relatedType = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->uid = $uid;
        $this->relatedId = $relatedId;
        $this->relatedType = $relatedType;
    }

    public function handle()
    {
        try {
            // Créer le log SMS avant envoi
            $smsLog = SmsLog::create([
                'phone_number' => $this->phoneNumber,
                'message' => $this->message,
                'status' => SmsLog::STATUS_PENDING,
                'type' => 'transaction',
                'uid' => $this->uid,
                'user_id' => Auth::id() ?? 1, // ID système si pas d'utilisateur
            ]);
            
            // Envoyer le SMS
            $smsService = app(SmsService::class);
            $result = $smsService->sendTransactionSMS(
                $this->phoneNumber,
                $this->message,
                $this->uid
            );
            
            // Mettre à jour le log
            if ($result['status'] === 'S') {
                $smsLog->markAsSent($result['message_id'], $result);
                
                // Si c'est lié à un mouvement, mettre à jour la relation
                if ($this->relatedType === 'mouvement' && $this->relatedId) {
                    $smsLog->update(['mouvement_id' => $this->relatedId]);
                }
                
                Log::info('SMS envoyé avec succès', [
                    'phone' => $this->phoneNumber,
                    'message_id' => $result['message_id'],
                    'uid' => $this->uid
                ]);
                
            } else {
                $smsLog->markAsFailed($result['remarks'] ?? 'Échec inconnu');
                
                Log::error('Échec envoi SMS', [
                    'phone' => $this->phoneNumber,
                    'error' => $result['remarks']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur job SMS', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage()
            ]);
            
            // Marquer comme échoué dans le log
            if (isset($smsLog)) {
                $smsLog->markAsFailed($e->getMessage());
            }
        }
    }
}