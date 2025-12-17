<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $baseUrl;
    protected $apiId;
    protected $apiPassword;
    protected $senderId;

    public function __construct()
    {
        $this->baseUrl = config('services.asmsc.url', 'https://api2.dream-digital.info/api');
        $this->apiId = config('services.asmsc.api_id', 'API31096676593');
        $this->apiPassword = config('services.asmsc.api_password', '83H2gwbXBp');
        $this->senderId = config('services.asmsc.sender_id', 'TUMAINI');
        
        Log::info('SmsService initialisé', [
            'base_url' => $this->baseUrl,
            'api_id' => substr($this->apiId, 0, 10) . '...',
            'sender_id' => $this->senderId
        ]);
    }

    /**
     * Envoyer un SMS transactionnel
     */
    public function sendTransactionSMS($phoneNumber, $message, $uid = null)
    {
        Log::info('📱 Début envoi SMS', [
            'phone' => $phoneNumber,
            'message_length' => strlen($message),
            'uid' => $uid
        ]);
        
        try {
            // Nettoyer le numéro (enlever le + et espaces)
            $originalPhone = $phoneNumber;
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            if (empty($phoneNumber)) {
                throw new \Exception('Numéro de téléphone vide ou invalide');
            }
            
            // Si le numéro commence par 243, le garder tel quel
            // Sinon, ajouter 243 pour la RDC
            if (!str_starts_with($phoneNumber, '243')) {
                // Supposer que c'est un numéro local sans indicatif
                $phoneNumber = '243' . ltrim($phoneNumber, '0');
            }

            Log::info('Numéro formaté', [
                'original' => $originalPhone,
                'formatted' => $phoneNumber,
                'length' => strlen($phoneNumber)
            ]);

            // PRÉPARER LES DONNÉES POUR L'API
            $apiData = [
                'api_id' => $this->apiId,
                'api_password' => $this->apiPassword,
                'sms_type' => 'T', // Transactionnel
                'encoding' => 'T', // Texte
                'sender_id' => $this->senderId,
                'phonenumber' => $phoneNumber,
                'textmessage' => $message,
                'uid' => $uid ?? uniqid('txn_', true),
                'ValidityPeriodInSeconds' => 3600, // 1 heure
            ];

            Log::info('Données API préparées', [
                'url' => $this->baseUrl . '/SendSMS',
                'data_keys' => array_keys($apiData),
                'message_preview' => substr($message, 0, 50) . '...'
            ]);

            // ENVOYER LA REQUÊTE AVEC SSL DÉSACTIVÉ
            Log::info('Envoi requête API avec SSL désactivé...');
            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false, // ← DÉSACTIVER LA VÉRIFICATION SSL
                ])
                ->post($this->baseUrl . '/SendSMS', $apiData);

            // Log de la réponse
            $rawResponse = $response->body();
            $statusCode = $response->status();
            
            Log::info('Réponse API', [
                'status_code' => $statusCode,
                'body_preview' => substr($rawResponse, 0, 200) . '...'
            ]);

            if ($statusCode !== 200) {
                // Essayer avec GET si POST échoue
                Log::info('POST échoué, essai avec GET...');
                $getUrl = $this->baseUrl . '/SendSMS?' . http_build_query($apiData);
                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->get($getUrl);
                    
                $rawResponse = $response->body();
                $statusCode = $response->status();
                
                Log::info('Réponse GET', [
                    'status_code' => $statusCode,
                    'method' => 'GET'
                ]);
            }

            $result = $response->json();
            
            if (!is_array($result)) {
                // Essayer de parser manuellement
                $result = json_decode($rawResponse, true) ?: ['status' => 'F', 'remarks' => 'Réponse JSON invalide'];
            }
            
            Log::info('Résultat SMS', [
                'status' => $result['status'] ?? 'unknown',
                'remarks' => $result['remarks'] ?? 'N/A',
                'message_id' => $result['message_id'] ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('🚨 Erreur envoi SMS', [
                'phone' => $phoneNumber ?? $originalPhone ?? 'N/A',
                'message' => $message,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Essayer avec file_get_contents comme fallback
            $fallbackResult = $this->sendViaFileGetContents(
                $phoneNumber ?? $originalPhone,
                $message,
                $uid
            );
            
            if ($fallbackResult) {
                Log::info('Fallback réussi', $fallbackResult);
                return $fallbackResult;
            }
            
            return [
                'status' => 'F',
                'remarks' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Méthode fallback avec file_get_contents
     */
    private function sendViaFileGetContents($phoneNumber, $message, $uid = null)
    {
        try {
            Log::info('Tentative fallback avec file_get_contents...');
            
            $apiData = [
                'api_id' => $this->apiId,
                'api_password' => $this->apiPassword,
                'sms_type' => 'T',
                'encoding' => 'T',
                'sender_id' => $this->senderId,
                'phonenumber' => $phoneNumber,
                'textmessage' => $message,
                'uid' => $uid ?? uniqid('txn_', true),
                'ValidityPeriodInSeconds' => 3600,
            ];
            
            $url = $this->baseUrl . '/SendSMS?' . http_build_query($apiData);
            
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'timeout' => 30,
                    'ignore_errors' => true,
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                Log::warning('Fallback file_get_contents échoué');
                return null;
            }
            
            $result = json_decode($response, true);
            
            Log::info('Fallback réussi', [
                'response' => $result,
                'method' => 'file_get_contents'
            ]);
            
            return $result ?: ['status' => 'F', 'remarks' => 'Réponse invalide'];
            
        } catch (\Exception $e) {
            Log::error('Erreur fallback', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Vérifier le solde du compte SMS
     */
    public function checkBalance()
    {
        try {
            Log::info('Vérification du solde SMS...');
            
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->post($this->baseUrl . '/CheckBalance', [
                    'api_id' => $this->apiId,
                    'api_password' => $this->apiPassword,
                ]);

            $result = $response->json();
            
            Log::info('Résultat vérification solde', $result);
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Erreur vérification solde SMS', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Vérifier le statut de livraison
     */
    public function checkDeliveryStatus($messageId, $uid = null)
    {
        $params = [
            'api_id' => $this->apiId,
            'api_password' => $this->apiPassword,
        ];

        if ($messageId) {
            $params['message_id'] = $messageId;
        }

        if ($uid) {
            $params['uid'] = $uid;
        }

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->post($this->baseUrl . '/GetDeliveryStatus', $params);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Erreur vérification statut', ['error' => $e->getMessage()]);
            return ['status' => 'F', 'remarks' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Tester la connexion à l'API
     */
    public function testConnection()
    {
        try {
            Log::info('=== TEST CONNEXION API SMS ===');
            
            // Test 1: Vérifier le solde
            $balance = $this->checkBalance();
            Log::info('Test 1 - Solde:', $balance ?? 'Non disponible');
            
            // Test 2: Envoyer un SMS de test
            $testNumber = '243992187530';
            $testMessage = 'Test connexion API - TUMAINI LETU - ' . date('H:i:s');
            
            Log::info('Test 2 - Envoi SMS...', [
                'number' => $testNumber,
                'message' => $testMessage
            ]);
            
            $result = $this->sendTransactionSMS(
                $testNumber,
                $testMessage,
                'test_connection_' . time()
            );

            return [
                'success' => isset($result['status']) && $result['status'] === 'S',
                'message' => $result['remarks'] ?? 'Aucune réponse',
                'details' => $result,
                'balance' => $balance
            ];

        } catch (\Exception $e) {
            Log::error('Erreur test connexion', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Méthode de test direct
     */
    public function testDirect()
    {
        try {
            $testNumber = '243992187530';
            $testMessage = 'Test direct SMS - TUMAINI LETU - ' . date('H:i:s');
            
            return $this->sendTransactionSMS($testNumber, $testMessage, 'direct_test_' . time());
            
        } catch (\Exception $e) {
            return [
                'status' => 'F',
                'remarks' => 'Erreur test direct: ' . $e->getMessage()
            ];
        }
    }


    // Ajoutez cette méthode à votre SmsService.php existant
public function sendTransactionSMSWithLog($phoneNumber, $message, $uid = null, $relatedData = [])
{
    $result = $this->sendTransactionSMS($phoneNumber, $message, $uid);
    
    // Créer le log SMS
    $smsLog = \App\Models\SmsLog::create([
        'phone_number' => $phoneNumber,
        'message' => $message,
        'message_id' => $result['message_id'] ?? null,
        'status' => $result['status'] === 'S' ? 'sent' : 'failed',
        'type' => 'transaction',
        'uid' => $uid,
        'response_data' => $result,
        'remarks' => $result['remarks'] ?? 'SMS transaction',
        'compte_reference' => $relatedData['compte_reference'] ?? null,
        'mouvement_reference' => $relatedData['mouvement_reference'] ?? null,
        'compte_epargne_reference' => $relatedData['compte_epargne_reference'] ?? null,
    ]);
    
    return array_merge($result, ['log_id' => $smsLog->id]);
}

// Ajoutez cette méthode à votre SmsService.php
public function sendEpargneSMS($phoneNumber, $message, $uid = null, $relatedData = [])
{
    $result = $this->sendTransactionSMS($phoneNumber, $message, $uid);
    
    // Créer un log spécifique épargne
    $smsLog = \App\Models\SmsLog::create([
        'phone_number' => $phoneNumber,
        'message' => $message,
        'message_id' => $result['message_id'] ?? null,
        'status' => $result['status'] === 'S' ? 'sent' : 'failed',
        'type' => 'epargne',
        'uid' => $uid,
        'response_data' => $result,
        'remarks' => 'SMS épargne - ' . ($result['remarks'] ?? ''),
        'compte_epargne_reference' => $relatedData['compte_epargne_reference'] ?? null,
        'mouvement_reference' => $relatedData['mouvement_reference'] ?? null,
    ]);
    
    return array_merge($result, ['log_id' => $smsLog->id]);
}
}