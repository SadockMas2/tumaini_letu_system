<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsCampaignService;
use Illuminate\Support\Facades\Auth;

class SendSmsToAllClients extends Command
{
    protected $signature = 'sms:send-all {message} {--test} {--no-name}';
    protected $description = 'Envoyer un SMS à tous les clients';
    
    protected $smsCampaignService;
    
    public function __construct(SmsCampaignService $smsCampaignService)
    {
        parent::__construct();
        $this->smsCampaignService = $smsCampaignService;
    }
    
    public function handle()
    {
        $message = $this->argument('message');
        $testMode = $this->option('test');
        $includeName = !$this->option('no-name');
        
        $this->info("📱 ENVOI DE SMS À TOUS LES CLIENTS");
        $this->info("Message: " . substr($message, 0, 100) . "...");
        $this->info("Longueur: " . strlen($message) . " caractères");
        $this->info("Inclure nom: " . ($includeName ? 'Oui' : 'Non'));
        
        $recipientsCount = $this->smsCampaignService->getRecipientsCount();
        $this->info("Destinataires: " . $recipientsCount . " clients");
        
        if ($testMode) {
            $this->info("🔬 MODE TEST - Envoi à 1 client seulement");
            $result = $this->smsCampaignService->testWithOneClient($message, $includeName);
            
            if (isset($result['error'])) {
                $this->error($result['error']);
                return 1;
            }
            
            $this->info("Client test: " . $result['client']);
            $this->info("Téléphone: " . $result['telephone']);
            $this->info("Résultat: " . ($result['result']['status'] === 'S' ? '✅ SUCCÈS' : '❌ ÉCHEC'));
            $this->info("Message ID: " . ($result['result']['message_id'] ?? 'N/A'));
            $this->info("Remarque: " . ($result['result']['remarks'] ?? 'N/A'));
            
        } else {
            if ($this->confirm("Êtes-vous sûr d'envoyer ce SMS à {$recipientsCount} clients?", true)) {
                $this->info("⏳ Envoi en cours...");
                
                $result = $this->smsCampaignService->sendToAllClients(
                    $message, 
                    $includeName,
                    Auth::id() ?? 1
                );
                
                $this->info("✅ ENVOI TERMINÉ");
                $this->info("Total: " . $result['total_clients']);
                $this->info("Succès: " . $result['success']);
                $this->info("Échecs: " . $result['failed']);
                
                // Afficher les échecs
                $failures = array_filter($result['results'], function($item) {
                    return !$item['success'];
                });
                
                if (!empty($failures)) {
                    $this->warn("Échecs détaillés:");
                    foreach ($failures as $failure) {
                        $this->line("- {$failure['client_name']} ({$failure['telephone']}): {$failure['remarks']}");
                    }
                }
            }
        }
        
        return 0;
    }
}