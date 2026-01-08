<?php

namespace App\Observers;

use App\Models\Epargne;
use App\Models\CompteEpargne;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class EpargneObserver
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function updated(Epargne $epargne)
    {
        if ($epargne->isDirty('statut') && $epargne->statut === 'valide') {
            Log::info('📝 Épargne validée', [
                'epargne_id' => $epargne->id,
                'montant' => $epargne->montant,
                'client_id' => $epargne->client_id,
                'devise' => $epargne->devise
            ]);

            $this->sendSmsForEpargne($epargne);
        }
    }

    private function sendSmsForEpargne(Epargne $epargne)
    {
        try {
            Log::info('Début sendSmsForEpargne', ['epargne_id' => $epargne->id]);
            
            $compteEpargne = $this->findCompteEpargneForEpargne($epargne);
            if (!$compteEpargne) {
                Log::warning('Compte épargne non trouvé pour épargne', ['epargne_id' => $epargne->id]);
                return;
            }

            if (!$this->checkSmsNotifications($compteEpargne)) {
                Log::info('SMS désactivés pour ce compte épargne', ['compte_id' => $compteEpargne->id]);
                return;
            }

            $recipientInfo = $this->getRecipientInfo($compteEpargne);
            if (!$recipientInfo['telephone']) {
                Log::warning('Pas de numéro de téléphone pour compte épargne', [
                    'compte_id' => $compteEpargne->id,
                    'client_id' => $compteEpargne->client_id
                ]);
                return;
            }

            $message = $this->formatMessage($epargne, $compteEpargne, $recipientInfo);
            Log::info('Message épargne formaté', ['longueur' => strlen($message)]);

            // Nettoyer le numéro de téléphone
            $cleanPhone = preg_replace('/[^0-9]/', '', $recipientInfo['telephone']);
            
            // Vérifier que c'est un numéro valide
            if (strlen($cleanPhone) < 9) {
                Log::error('❌ Numéro de téléphone invalide', ['telephone' => $cleanPhone]);
                return;
            }
            
            // Ajouter l'indicatif si nécessaire
            if (!str_starts_with($cleanPhone, '243')) {
                $cleanPhone = '243' . ltrim($cleanPhone, '0');
            }

            Log::info('📱 Envoi SMS épargne', [
                'epargne_id' => $epargne->id,
                'longueur' => strlen($message),
                'telephone' => substr($cleanPhone, -4)
            ]);

            $result = $this->smsService->sendTransactionSMS(
                $cleanPhone,
                $message,
                'epargne_' . $epargne->id
            );

            // Créer le log SMS
            $this->createSmsLog($result, $recipientInfo, $message, $epargne, $compteEpargne, $cleanPhone);

        } catch (\Exception $e) {
            Log::error('❌ Erreur SMS épargne', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'epargne_id' => $epargne->id
            ]);
        }
    }

    /**
     * FORMAT MESSAGE avec UTF-8
     */
    private function formatMessage(Epargne $epargne, CompteEpargne $compteEpargne, array $recipientInfo): string
    {
        $genre = $recipientInfo['clientGenre'] === 'Chère' ? 'Chère' : 'Cher';
        $nom = $this->getNomCourt($recipientInfo['clientName']);
        
        $message = sprintf(
            "%s membre %s, un dépôt de %s %s a été effectué sur votre compte épargne %s, le %s. Nouveau solde : %s %s. \"",
            $genre,
            $nom,
            number_format($epargne->montant, 0, ',', ' '),
            $epargne->devise,
            $compteEpargne->numero_compte,
            now()->format('d-m-Y'),
            number_format($compteEpargne->solde, 0, ',', ' '),
            $epargne->devise
        );
        
        // Assurer l'encodage UTF-8
        return mb_convert_encoding($message, 'UTF-8', 'auto');
    }

    private function getNomCourt(string $nomComplet): string
    {
        $parties = explode(' ', trim($nomComplet));
        return count($parties) > 1 ? $parties[0] . ' ' . substr($parties[1], 0, 1) . '.' : $parties[0];
    }

    private function findCompteEpargneForEpargne(Epargne $epargne)
    {
        if ($epargne->type_epargne === 'individuel' && $epargne->client_id) {
            $compte = CompteEpargne::where('client_id', $epargne->client_id)
                ->where('devise', $epargne->devise)
                ->first();
            
            Log::info('Recherche compte épargne', [
                'client_id' => $epargne->client_id,
                'devise' => $epargne->devise,
                'trouvé' => $compte ? 'oui' : 'non'
            ]);
            
            return $compte;
        }
        return null;
    }

    private function checkSmsNotifications(CompteEpargne $compteEpargne): bool
    {
        if (isset($compteEpargne->sms_notifications) && $compteEpargne->sms_notifications === false) {
            return false;
        }

        if ($compteEpargne->type_compte === 'individuel' && $compteEpargne->client) {
            return !(isset($compteEpargne->client->sms_notifications) && 
                   $compteEpargne->client->sms_notifications === false);
        }

        return true;
    }

    private function getRecipientInfo(CompteEpargne $compteEpargne): array
    {
        $telephone = null;
        $clientName = '';
        $clientGenre = 'Cher';
        
        if ($compteEpargne->type_compte === 'individuel' && $compteEpargne->client) {
            $client = $compteEpargne->client;
            $telephone = $client->telephone; // Colonne 'telephone' dans la table clients
            $clientName = $client->nom_complet;
            $clientGenre = isset($client->genre) && $client->genre === 'F' ? 'Chère' : 'Cher';
        }
        
        return [
            'telephone' => $telephone,
            'clientName' => $clientName,
            'clientGenre' => $clientGenre
        ];
    }

    private function createSmsLog(array $result, array $recipientInfo, string $message, Epargne $epargne, CompteEpargne $compteEpargne, string $cleanPhone)
    {
        $smsLogData = [
            'telephone' => $cleanPhone, // Utiliser 'telephone' au lieu de 'phone_number'
            'message' => $message,
            'message_id' => $result['message_id'] ?? null,
            'status' => $result['status'] === 'S' ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
            'type' => 'epargne_depot',
            'uid' => 'epargne_' . $epargne->id,
            'response_data' => $result,
            'remarks' => 'SMS dépôt épargne validé',
            'compte_epargne_reference' => $compteEpargne->numero_compte,
            'sent_at' => now(),
            'delivery_status' => $result['status'] ?? 'unknown',
            'cost' => $result['cost'] ?? 0,
        ];

        if ($compteEpargne->client) {
            $smsLogData['client_id'] = $compteEpargne->client->id;
        }
        
        $smsLogData['compte_epargne_id'] = $compteEpargne->id;

        $smsLog = SmsLog::create($smsLogData);
        Log::info('Log SMS épargne créé', ['sms_log_id' => $smsLog->id]);
    }
}