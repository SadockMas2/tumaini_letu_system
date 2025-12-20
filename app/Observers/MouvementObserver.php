<?php

namespace App\Observers;

use App\Models\Mouvement;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class MouvementObserver
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function created(Mouvement $mouvement)
    {
        Log::info('🎯 ========== NOUVEAU MOUVEMENT ==========', [
            'mouvement_id' => $mouvement->id,
            'type' => $mouvement->type,
            'type_mouvement' => $mouvement->type_mouvement,
            'compte_id' => $mouvement->compte_id,
            'compte_epargne_id' => $mouvement->compte_epargne_id,
            'montant' => $mouvement->montant,
            'devise' => $mouvement->devise
        ]);

        // ====== EXCLUSIONS ======
        $excludedTypes = [
            'credit_octroye',
            'credit_octroye_groupe', 
            'depense_comptabilite',
            'depense_diverse_comptabilite',
            'deblocage_caution_auto',
            'versement_agent',
            'paiement_groupes',
            'paiement_credit_groupe',
            'paiement_credit_automatique',
            
        ];
        
        if (in_array($mouvement->type_mouvement, $excludedTypes)) {
            Log::info('❌ Type exclu de SMS', ['type' => $mouvement->type_mouvement]);
            return;
        }
        
        // DEBUG: Vérifier si c'est un retrait épargne
        if ($mouvement->type_mouvement === 'retrait_epargne') {
            Log::info('🎯🎯🎯 RETRAIT ÉPARGNE DÉTECTÉ 🎯🎯🎯', [
                'mouvement_id' => $mouvement->id,
                'compte_epargne_id' => $mouvement->compte_epargne_id,
                'solde_avant' => $mouvement->solde_avant,
                'solde_apres' => $mouvement->solde_apres
            ]);
        }
        
        // ====== GESTION DES MOUVEMENTS ======
        if ($mouvement->compte_id) {
            Log::info('📋 Gestion compte courant', ['mouvement_id' => $mouvement->id]);
            $this->handleCompteMouvement($mouvement);
        }
        
        // Gérer TOUS les mouvements sur comptes épargne
        if ($mouvement->compte_epargne_id) {
            Log::info('💰 Gestion compte épargne', [
                'mouvement_id' => $mouvement->id,
                'type_mouvement' => $mouvement->type_mouvement,
                'is_retrait_epargne' => ($mouvement->type_mouvement === 'retrait_epargne') ? 'OUI' : 'NON'
            ]);
            $this->handleCompteEpargneMouvement($mouvement);
        } else {
            Log::warning('⚠️ Mouvement sans compte_epargne_id', [
                'mouvement_id' => $mouvement->id,
                'type_mouvement' => $mouvement->type_mouvement
            ]);
        }
        
        Log::info('🏁 ========== FIN TRAITEMENT MOUVEMENT ==========');
    }
    
    /**
     * Gérer les mouvements de comptes normaux
     */
    private function handleCompteMouvement(Mouvement $mouvement)
    {
        Log::info('🔍 Début handleCompteMouvement', ['mouvement_id' => $mouvement->id]);
        
        $compte = $mouvement->compte;
        if (!$compte) {
            Log::warning('⚠️ Compte non trouvé', ['mouvement_id' => $mouvement->id]);
            return;
        }
        
        Log::info('✅ Compte trouvé', [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte,
            'sms_notifications' => $compte->sms_notifications,
            'type_compte' => $compte->type_compte
        ]);
        
        if ($compte->sms_notifications === false) {
            Log::info('🔕 SMS désactivés pour ce compte', ['compte_id' => $compte->id]);
            return;
        }
        
        $recipientInfo = $this->getRecipientInfoForCompte($compte);
        Log::info('📞 Infos destinataire compte', [
            'telephone' => $recipientInfo['telephone'] ? '****' . substr($recipientInfo['telephone'], -4) : 'NULL',
            'clientName' => $recipientInfo['clientName'],
            'has_telephone' => !empty($recipientInfo['telephone']) ? 'OUI' : 'NON'
        ]);
        
        if (!$recipientInfo['telephone']) {
            Log::warning('📵 Pas de numéro de téléphone', ['compte_id' => $compte->id]);
            return;
        }
        
        $message = $this->formatMessageCompte($mouvement, $compte, $recipientInfo);
        Log::info('💬 Message formaté', [
            'longueur' => strlen($message),
            'preview' => substr($message, 0, 50) . '...'
        ]);
        
        $this->sendSms($mouvement, $recipientInfo['telephone'], $message, 'compte');
    }
    
    /**
     * Gérer les mouvements sur comptes épargne
     */
    private function handleCompteEpargneMouvement(Mouvement $mouvement)
    {
        Log::info('🔍 Début handleCompteEpargneMouvement', [
            'mouvement_id' => $mouvement->id,
            'type_mouvement' => $mouvement->type_mouvement,
            'type' => $mouvement->type
        ]);
        
        $compteEpargne = $mouvement->compteEpargne;
        if (!$compteEpargne) {
            Log::warning('⚠️ Compte épargne non trouvé', ['mouvement_id' => $mouvement->id]);
            return;
        }
        
        Log::info('✅ Compte épargne trouvé', [
            'compte_id' => $compteEpargne->id,
            'numero_compte' => $compteEpargne->numero_compte,
            'sms_notifications' => $compteEpargne->sms_notifications ?? 'non défini',
            'client_id' => $compteEpargne->client_id,
            'type_compte' => $compteEpargne->type_compte,
            'solde' => $compteEpargne->solde
        ]);
        
        // Vérifier si les SMS sont désactivés pour ce compte épargne
        if (isset($compteEpargne->sms_notifications) && $compteEpargne->sms_notifications === false) {
            Log::info('🔕 SMS désactivés pour compte épargne', ['compte_id' => $compteEpargne->id]);
            return;
        }
        
        $recipientInfo = $this->getRecipientInfoForCompteEpargne($compteEpargne);
        Log::info('📞 Infos destinataire épargne', [
            'telephone' => $recipientInfo['telephone'] ? '****' . substr($recipientInfo['telephone'], -4) : 'NULL',
            'clientName' => $recipientInfo['clientName'],
            'clientGenre' => $recipientInfo['clientGenre'],
            'has_telephone' => !empty($recipientInfo['telephone']) ? 'OUI' : 'NON'
        ]);
        
        if (!$recipientInfo['telephone']) {
            Log::warning('📵 Pas de numéro de téléphone pour compte épargne', [
                'compte_id' => $compteEpargne->id,
                'type_mouvement' => $mouvement->type_mouvement,
                'client_id' => $compteEpargne->client_id,
                'clientName' => $recipientInfo['clientName']
            ]);
            return;
        }
        
        $message = $this->formatMessageCompteEpargne($mouvement, $compteEpargne, $recipientInfo);
        Log::info('💬 Message épargne formaté', [
            'longueur' => strlen($message),
            'preview' => substr($message, 0, 50) . '...',
            'type_mouvement' => $mouvement->type_mouvement,
            'type' => $mouvement->type
        ]);
        
        $this->sendSms($mouvement, $recipientInfo['telephone'], $message, 'epargne_mvt');
    }
    
    /**
     * FORMAT MESSAGE COMPTE - UTF-8 pour les accents
     */
    private function formatMessageCompte(Mouvement $mouvement, $compte, array $recipientInfo): string
    {
        $genre = $recipientInfo['clientGenre'] === 'Chère' ? 'Chère' : 'Cher';
        $nom = $this->getNomCourt($recipientInfo['clientName']);
        $typeOperation = $mouvement->type === 'depot' ? 'dépôt' : 'retrait';
        
        $message = sprintf(
            "%s membre %s, un %s de %s %s a été effectué sur votre compte %s, le %s. Nouveau solde : %s %s.\nTUMAINI LETU \"Réussir ensemble !\"",
            $genre,
            $nom,
            $typeOperation,
            number_format($mouvement->montant, 0, ',', ' '),
            $mouvement->devise,
            $compte->numero_compte,
            now()->format('d-m-Y'),
            number_format($mouvement->solde_apres, 0, ',', ' '),
            $mouvement->devise
        );
        
        // Assurer l'encodage UTF-8
        return mb_convert_encoding($message, 'UTF-8', 'auto');
    }
    
    /**
     * FORMAT MESSAGE COMPTE ÉPARGNE - UTF-8 pour les accents
     */
    private function formatMessageCompteEpargne(Mouvement $mouvement, $compteEpargne, array $recipientInfo): string
    {
        $genre = $recipientInfo['clientGenre'] === 'Chère' ? 'Chère' : 'Cher';
        $nom = $this->getNomCourt($recipientInfo['clientName']);
        
        // Déterminer l'opération
        if ($mouvement->type_mouvement === 'retrait_epargne' || $mouvement->type === 'retrait') {
            $typeOperation = 'retrait';
        } else {
            $typeOperation = 'dépôt';
        }
        
        // Utiliser solde_apres du mouvement si disponible, sinon solde du compte
        $solde = $mouvement->solde_apres ?? $compteEpargne->solde;
        
        $message = sprintf(
            "%s membre %s, un %s de %s %s a été effectué sur votre compte épargne %s, le %s. Nouveau solde : %s %s.\nTUMAINI LETU \"Réussir ensemble !\"",
            $genre,
            $nom,
            $typeOperation,
            number_format($mouvement->montant, 0, ',', ' '),
            $mouvement->devise,
            $compteEpargne->numero_compte,
            now()->format('d-m-Y'),
            number_format($solde, 0, ',', ' '),
            $mouvement->devise
        );
        
        Log::info('📝 Détails formatage message épargne', [
            'genre' => $genre,
            'nom' => $nom,
            'typeOperation' => $typeOperation,
            'montant' => $mouvement->montant,
            'numero_compte' => $compteEpargne->numero_compte,
            'solde_apres' => $mouvement->solde_apres,
            'solde_compte' => $compteEpargne->solde,
            'solde_utilise' => $solde
        ]);
        
        // Assurer l'encodage UTF-8
        return mb_convert_encoding($message, 'UTF-8', 'auto');
    }
    
    /**
     * Nom court pour économiser des caractères
     */
    private function getNomCourt(string $nomComplet): string
    {
        $parties = explode(' ', trim($nomComplet));
        return count($parties) > 1 ? $parties[0] . ' ' . substr($parties[1], 0, 1) . '.' : $parties[0];
    }
    
    /**
     * Obtenir infos destinataire pour compte normal
     */
    private function getRecipientInfoForCompte($compte): array
    {
        $telephone = null;
        $clientName = '';
        $clientGenre = 'Cher';
        
        if ($compte->type_compte === 'individuel' && $compte->client) {
            $client = $compte->client;
            Log::info('👤 Client trouvé pour compte', [
                'client_id' => $client->id,
                'sms_notifications' => $client->sms_notifications ?? 'non défini',
                'telephone_existe' => !empty($client->telephone) ? 'OUI' : 'NON'
            ]);
            
            if (isset($client->sms_notifications) && $client->sms_notifications === false) {
                Log::info('🔕 Client a désactivé les SMS', ['client_id' => $client->id]);
                return ['telephone' => null];
            }
            
            $telephone = $client->telephone;
            $clientName = $client->nom_complet;
            $clientGenre = isset($client->genre) && $client->genre === 'F' ? 'Chère' : 'Cher';
            
            Log::info('✅ Infos client récupérées', [
                'nom_complet' => $clientName,
                'genre' => $clientGenre,
                'telephone_longueur' => strlen($telephone ?? '')
            ]);
        } else {
            Log::warning('👥 Pas de client individuel pour ce compte', [
                'compte_id' => $compte->id,
                'type_compte' => $compte->type_compte
            ]);
        }
        
        return [
            'telephone' => $telephone,
            'clientName' => $clientName,
            'clientGenre' => $clientGenre
        ];
    }
    
    /**
     * Obtenir infos destinataire pour compte épargne
     */
    private function getRecipientInfoForCompteEpargne($compteEpargne): array
    {
        $telephone = null;
        $clientName = '';
        $clientGenre = 'Cher';
        
        Log::info('🔍 Recherche client pour compte épargne', [
            'compte_id' => $compteEpargne->id,
            'type_compte' => $compteEpargne->type_compte,
            'client_id' => $compteEpargne->client_id
        ]);
        
        if ($compteEpargne->type_compte === 'individuel' && $compteEpargne->client) {
            $client = $compteEpargne->client;
            Log::info('👤 Client trouvé pour compte épargne', [
                'client_id' => $client->id,
                'nom_complet' => $client->nom_complet,
                'sms_notifications' => $client->sms_notifications ?? 'non défini',
                'telephone' => $client->telephone ? '****' . substr($client->telephone, -4) : 'NULL'
            ]);
            
            if (isset($client->sms_notifications) && $client->sms_notifications === false) {
                Log::info('🔕 Client a désactivé les SMS', ['client_id' => $client->id]);
                return ['telephone' => null];
            }
            
            $telephone = $client->telephone;
            $clientName = $client->nom_complet;
            $clientGenre = isset($client->genre) && $client->genre === 'F' ? 'Chère' : 'Cher';
            
            Log::info('✅ Infos client épargne récupérées', [
                'telephone_trouve' => !empty($telephone) ? 'OUI' : 'NON',
                'telephone_longueur' => strlen($telephone ?? '')
            ]);
        } else {
            Log::warning('👥 Pas de client trouvé pour compte épargne', [
                'compte_id' => $compteEpargne->id,
                'type_compte' => $compteEpargne->type_compte,
                'client_id' => $compteEpargne->client_id
            ]);
        }
        
        return [
            'telephone' => $telephone,
            'clientName' => $clientName,
            'clientGenre' => $clientGenre
        ];
    }
    
    /**
     * Envoyer SMS avec UTF-8
     */
    private function sendSms(Mouvement $mouvement, string $telephone, string $message, string $sourceType)
    {
        try {
            Log::info('📱 ========== ENVOI SMS DÉBUT ==========', [
                'mouvement_id' => $mouvement->id,
                'type_mouvement' => $mouvement->type_mouvement,
                'longueur_message' => strlen($message),
                'telephone_original' => substr($telephone, -8),
                'source' => $sourceType
            ]);
            
            // Nettoyer le numéro de téléphone
            $cleanPhone = preg_replace('/[^0-9]/', '', $telephone);
            
            Log::info('🔧 Nettoyage téléphone', [
                'original' => $telephone,
                'nettoye' => $cleanPhone,
                'longueur' => strlen($cleanPhone)
            ]);
            
            // Vérifier que c'est un numéro valide
            if (strlen($cleanPhone) < 9) {
                Log::error('❌ Numéro de téléphone invalide', [
                    'telephone' => $cleanPhone,
                    'longueur' => strlen($cleanPhone)
                ]);
                return;
            }
            
            // Ajouter l'indicatif si nécessaire
            if (!str_starts_with($cleanPhone, '243')) {
                $cleanPhone = '243' . ltrim($cleanPhone, '0');
                Log::info('🌍 Ajout indicatif 243', ['telephone_final' => $cleanPhone]);
            }
            
            // Créer le log SMS AVANT l'envoi pour le tracking
            $smsLogData = [
                'telephone' => $cleanPhone,
                'message' => $message,
                'message_id' => null,
                'status' => SmsLog::STATUS_PENDING,
                'type' => 'transaction',
                'uid' => 'mvt_' . $mouvement->id,
                'response_data' => null,
                'remarks' => 'SMS mouvement - ' . $mouvement->type_mouvement . ' - ' . $sourceType,
                'sent_at' => now(),
                'mouvement_id' => $mouvement->id,
            ];
            
            if ($mouvement->compte_id) {
                $smsLogData['compte_id'] = $mouvement->compte_id;
            }
            
            if ($mouvement->compte_epargne_id) {
                $smsLogData['compte_epargne_id'] = $mouvement->compte_epargne_id;
                $smsLogData['client_id'] = $mouvement->compteEpargne->client_id ?? null;
            }
            
            $smsLog = SmsLog::create($smsLogData);
            Log::info('📝 Log SMS créé', [
                'sms_log_id' => $smsLog->id,
                'telephone' => substr($cleanPhone, -4),
                'status' => 'PENDING'
            ]);
            
            // Envoyer le SMS via le service
            Log::info('🚀 Appel service SMS...');
            $result = $this->smsService->sendTransactionSMS(
                $cleanPhone,
                $message,
                'mvt_' . $mouvement->id . '_' . $sourceType
            );
            
            Log::info('📊 Résultat service SMS', [
                'result' => $result,
                'status_service' => $result['status'] ?? 'non défini',
                'message_id' => $result['message_id'] ?? 'non défini'
            ]);
            
            // Mettre à jour le log SMS avec le résultat
            $updateData = [
                'message_id' => $result['message_id'] ?? null,
                'status' => ($result['status'] ?? '') === 'S' ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
                'response_data' => $result,
                'delivery_status' => $result['status'] ?? 'unknown',
                'cost' => $result['cost'] ?? 0,
            ];
            
            $smsLog->update($updateData);
            
            Log::info('✅ SMS envoyé avec succès', [
                'sms_log_id' => $smsLog->id,
                'status' => $updateData['status'],
                'message_id' => $updateData['message_id']
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌❌❌ ERREUR CRITIQUE SMS mouvement ❌❌❌', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'mouvement_id' => $mouvement->id,
                'type_mouvement' => $mouvement->type_mouvement,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Mettre à jour le log SMS en cas d'erreur
            if (isset($smsLog)) {
                $smsLog->update([
                    'status' => SmsLog::STATUS_FAILED,
                    'remarks' => 'Erreur: ' . $e->getMessage()
                ]);
            }
        }
        
        Log::info('🏁 ========== FIN ENVOI SMS ==========');
    }
}