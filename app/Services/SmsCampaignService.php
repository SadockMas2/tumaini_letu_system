<?php

namespace App\Services;

use App\Models\Client;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsCampaignService
{
    protected $smsService;
    
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }
    
    /**
     * Envoyer un SMS à tous les clients
     */
    public function sendToAllClients(string $message, bool $includeName = true, ?int $userId = null)
    {
        Log::info('🚀 Début envoi SMS à tous les clients', [
            'message_length' => strlen($message),
            'include_name' => $includeName
        ]);
        
        try {
            // Récupérer tous les clients avec téléphone
            $clients = Client::whereNotNull('telephone')
                            ->where('telephone', '!=', '')
                            ->get();
            
            $successCount = 0;
            $failedCount = 0;
            $results = [];
            
            foreach ($clients as $client) {
                try {
                    // Préparer le message final
                    $finalMessage = $this->prepareMessage($client, $message, $includeName);
                    
                    // Log pour débogage
                    Log::info('Envoi à client', [
                        'client_id' => $client->id,
                        'telephone' => $client->telephone,
                        'message_preview' => substr($finalMessage, 0, 50) . '...'
                    ]);
                    
                    // Envoyer le SMS
                    $result = $this->smsService->sendTransactionSMS(
                        $client->telephone,
                        $finalMessage,
                        'campaign_' . time() . '_' . $client->id
                    );
                    
                    // Créer le log dans la base de données
                    $smsLogData = [
                        'client_id' => $client->id,
                        'telephone' => $client->telephone,
                        'message' => $finalMessage,
                        'message_id' => $result['message_id'] ?? null,
                        'status' => ($result['status'] ?? 'F') === 'S' ? 'sent' : 'failed',
                        'remarks' => $result['remarks'] ?? 'Envoi campagne',
                        'uid' => 'campaign_' . time() . '_' . $client->id,
                        'user_id' => $userId ?? Auth::id(),
                        'type' => 'campaign',
                        'sent_at' => now(),
                    ];
                    
                    // Ajouter response_data seulement si présent
                    if (isset($result) && is_array($result)) {
                        $smsLogData['response_data'] = $result;
                    }
                    
                    SmsLog::create($smsLogData);
                    
                    if (($result['status'] ?? 'F') === 'S') {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }
                    
                    $results[] = [
                        'client_id' => $client->id,
                        'client_name' => $client->nom_complet,
                        'telephone' => $client->telephone,
                        'success' => ($result['status'] ?? 'F') === 'S',
                        'message_id' => $result['message_id'] ?? null,
                        'remarks' => $result['remarks'] ?? 'N/A'
                    ];
                    
                    // Petite pause pour éviter de surcharger l'API
                    usleep(100000); // 0.1 seconde
                    
                } catch (Exception $e) {
                    Log::error('Erreur envoi SMS client', [
                        'client_id' => $client->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $failedCount++;
                    
                    try {
                        // Log de l'échec dans la base - version simplifiée
                        SmsLog::create([
                            'client_id' => $client->id,
                            'telephone' => $client->telephone ?? 'ERREUR',
                            'message' => $message,
                            'status' => 'failed',
                            'remarks' => substr('Erreur: ' . $e->getMessage(), 0, 250), // Limiter la longueur
                            'user_id' => $userId ?? auth()->id(),
                            'type' => 'campaign',
                            'sent_at' => now(),
                        ]);
                    } catch (Exception $logException) {
                        Log::error('Erreur création log SMS', [
                            'error' => $logException->getMessage()
                        ]);
                    }
                }
            }
            
            Log::info('✅ Envoi campagne terminé', [
                'total_clients' => $clients->count(),
                'success' => $successCount,
                'failed' => $failedCount
            ]);
            
            return [
                'total_clients' => $clients->count(),
                'success' => $successCount,
                'failed' => $failedCount,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            Log::error('🚨 Erreur générale dans sendToAllClients', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'total_clients' => 0,
                'success' => 0,
                'failed' => 0,
                'error' => $e->getMessage(),
                'results' => []
            ];
        }
    }
    
    /**
     * Préparer le message avec le nom du client si demandé
     */
    private function prepareMessage(Client $client, string $message, bool $includeName): string
    {
        if ($includeName && !empty($client->nom_complet)) {
            return "Cher {$client->nom_complet},\n" . $message;
        }
        
        return $message;
    }
    
    /**
     * Vérifier combien de clients recevront le SMS
     */
    public function getRecipientsCount(): int
    {
        return Client::whereNotNull('telephone')
                    ->where('telephone', '!=', '')
                    ->count();
    }
    
    /**
     * Tester avec un seul client
     */
    public function testWithOneClient(string $message, bool $includeName = true)
    {
        try {
            $client = Client::whereNotNull('telephone')
                           ->where('telephone', '!=', '')
                           ->first();
            
            if (!$client) {
                return ['error' => 'Aucun client avec téléphone trouvé'];
            }
            
            $finalMessage = $this->prepareMessage($client, $message, $includeName);
            
            $result = $this->smsService->sendTransactionSMS(
                $client->telephone,
                $finalMessage,
                'test_campaign_' . time()
            );
            
            // Log du test
            SmsLog::create([
                'client_id' => $client->id,
                'telephone' => $client->telephone,
                'message' => $finalMessage,
                'message_id' => $result['message_id'] ?? null,
                'status' => ($result['status'] ?? 'F') === 'S' ? 'sent' : 'failed',
                'remarks' => 'Test campagne',
                'uid' => 'test_campaign_' . time(),
                'user_id' => auth()->id(),
                'type' => 'test_campaign',
                'response_data' => $result,
                'sent_at' => now(),
            ]);
            
            return [
                'client' => $client->nom_complet,
                'telephone' => $client->telephone,
                'message_sent' => $finalMessage,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            Log::error('Erreur test SMS', ['error' => $e->getMessage()]);
            
            return [
                'error' => 'Erreur test: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Version simplifiée pour éviter les erreurs
     */
    public function sendSimpleSms(string $phone, string $message): array
    {
        try {
            $result = $this->smsService->sendTransactionSMS($phone, $message, 'simple_' . time());
            
            return [
                'success' => ($result['status'] ?? 'F') === 'S',
                'message_id' => $result['message_id'] ?? null,
                'remarks' => $result['remarks'] ?? 'N/A'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}