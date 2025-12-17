<?php

namespace App\Observers;

use App\Models\MouvementEpargne;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class MouvementEpargneObserver
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function created(MouvementEpargne $mouvementEpargne)
    {
        Log::info('📝 Mouvement épargne créé', [
            'id' => $mouvementEpargne->id,
            'type' => $mouvementEpargne->type,
            'compte_epargne_id' => $mouvementEpargne->compte_epargne_id,
            'montant' => $mouvementEpargne->montant
        ]);

        // Types à exclure
        $excludedTypes = [
            'interet_epargne', // Exclure les intérêts automatiques
            'commission',      // Exclure les commissions
        ];
        
        $description = strtolower($mouvementEpargne->description ?? '');
        foreach ($excludedTypes as $excluded) {
            if (str_contains($description, $excluded)) {
                Log::info('Type de mouvement épargne exclu de SMS', [
                    'type' => $mouvementEpargne->type,
                    'description' => $mouvementEpargne->description
                ]);
                return;
            }
        }

        // Envoyer SMS pour les dépôts et retraits
        $this->sendSmsForMouvementEpargne($mouvementEpargne);
    }

    /**
     * Envoyer SMS pour mouvement d'épargne
     */
    private function sendSmsForMouvementEpargne(MouvementEpargne $mouvementEpargne)
    {
        try {
            $compteEpargne = $mouvementEpargne->compteEpargne;
            if (!$compteEpargne) {
                Log::warning('Compte épargne non trouvé', ['mouvement_id' => $mouvementEpargne->id]);
                return;
            }

            // Vérifier si les SMS sont activés pour ce compte épargne
            if ($compteEpargne->sms_notifications === false) {
                Log::info('SMS désactivés pour compte épargne', [
                    'compte_epargne_id' => $compteEpargne->id,
                    'numero_compte' => $compteEpargne->numero_compte
                ]);
                return;
            }

            $client = $compteEpargne->client;
            $groupe = $compteEpargne->groupeSolidaire;
            
            $phoneNumber = null;
            $clientName = '';
            $clientGenre = 'Cher'; // Par défaut masculin
            
            if ($compteEpargne->type_compte === 'individuel' && $client) {
                // Vérifier si le client a activé les SMS
                if ($client->sms_notifications === false) {
                    Log::info('SMS désactivés pour ce client (épargne)', ['client_id' => $client->id]);
                    return;
                }
                
                $phoneNumber = $client->telephone;
                $clientName = $client->nom_complet;
                
                // Déterminer le genre
                $clientGenre = $this->determinerGenre($client);
                
            } elseif ($compteEpargne->type_compte === 'groupe_solidaire' && $groupe) {
                // Vérifier si le groupe a activé les SMS
                if ($groupe->sms_notifications === false) {
                    Log::info('SMS désactivés pour ce groupe (épargne)', ['groupe_id' => $groupe->id]);
                    return;
                }
                
                $phoneNumber = $groupe->contact_phone;
                $clientName = $groupe->nom_groupe;
                $clientGenre = 'Chers';
            }

            if (empty($phoneNumber)) {
                Log::warning('Numéro de téléphone non trouvé pour compte épargne', [
                    'compte_epargne_id' => $compteEpargne->id,
                    'type_compte' => $compteEpargne->type_compte,
                    'client_name' => $clientName
                ]);
                return;
            }

            // Formater le message avec courtoisie
            $message = $this->formatEpargneMessage($mouvementEpargne, $clientName, $clientGenre, $compteEpargne);

            Log::info('Envoi SMS épargne', [
                'mouvement_id' => $mouvementEpargne->id,
                'telephone' => $phoneNumber,
                'message_length' => strlen($message),
                'type' => $mouvementEpargne->type
            ]);

            // Envoyer le SMS
            $result = $this->smsService->sendTransactionSMS(
                $phoneNumber,
                $message,
                'epg_' . $mouvementEpargne->type . '_' . $mouvementEpargne->id
            );

            // Créer le log SMS
            $smsLogData = [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] === 'S' ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED,
                'type' => 'epargne_' . $mouvementEpargne->type,
                'uid' => 'epg_' . $mouvementEpargne->type . '_' . $mouvementEpargne->id,
                'response_data' => $result,
                'remarks' => 'SMS ' . ($mouvementEpargne->type === 'depot' ? 'dépôt' : 'retrait') . ' épargne',
                'compte_epargne_reference' => $compteEpargne->numero_compte,
                'mouvement_reference' => $mouvementEpargne->reference,
                'sent_at' => now(),
            ];

            // Associer au client ou groupe
            if ($client) {
                $smsLogData['client_id'] = $client->id;
            }
            if ($groupe) {
                $smsLogData['groupe_solidaire_id'] = $groupe->id;
            }

            SmsLog::create($smsLogData);

            Log::info('✅ SMS épargne envoyé', [
                'mouvement_id' => $mouvementEpargne->id,
                'sms_status' => $result['status'],
                'message_id' => $result['message_id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur SMS épargne', [
                'mouvement_id' => $mouvementEpargne->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Déterminer le genre du client
     */
    private function determinerGenre($client): string
    {
        if (isset($client->genre)) {
            return $client->genre === 'F' ? 'Chère' : 'Cher';
        }
        
        // Par défaut, si le champ n'existe pas
        return 'Cher';
    }

    /**
     * Formater le message d'épargne avec courtoisie
     */
    private function formatEpargneMessage(MouvementEpargne $mouvement, string $clientName, string $clientGenre, $compteEpargne): string
    {
        $action = $mouvement->type === 'depot' ? 'a été crédité' : 'a été débité';
        
    
        $description = !empty($mouvement->description) 
            ? $mouvement->description 
            : ($mouvement->type === 'depot' ? 'Versement sur compte épargne' : 'Retrait sur compte épargne');
        
        // Formater le numéro de compte épargne
        $numeroCompte = $compteEpargne->numero_compte ?? 'N/A';
        
       
        $message = sprintf(
            "%s(e) Membre %s,\n\n",
            $clientGenre,
            $clientName
        );
        
        $message .= sprintf(
            "Votre compte épargne N° %s %s du montant de %s %s\n",
            $numeroCompte,
            $action,
            number_format($mouvement->montant, 0, ',', ''),
            $mouvement->devise
        );
        
        // // Ajouter la description/libellé
        // $message .= sprintf("Libellé: %s\n", $description);
        
        // Ajouter le solde épargne restant
        $message .= sprintf(
            "Solde épargne restant: %s %s\n",
            number_format($mouvement->solde_apres, 0, ',', ''),
            $mouvement->devise
        );
        
        //  référence et date
        $message .= sprintf(
            "Ref: %s\Le: %s\n\n",
            $mouvement->reference ?: 'EPG-' . $mouvement->id,
            now()->format('d/m/Y H:i')
        );
        
        // Signature 
        
             $message .= "TUMAINI LETU\nRéussir Ensemble!";

    
        
        return $message;
    }

    /**
     * Formater le message pour les groupes d'épargne
     */
    private function formatGroupeEpargneMessage(MouvementEpargne $mouvement, string $groupeName, $compteEpargne): string
    {
        $action = $mouvement->type === 'depot' ? 'a été crédité' : 'a été débité';
        
        $description = !empty($mouvement->description) 
            ? $mouvement->description 
            : ($mouvement->type === 'depot' ? 'Cotisation groupe épargne' : 'Dépense groupe épargne');
        
        $numeroCompte = $compteEpargne->numero_compte ?? 'N/A';
        
        $message = sprintf(
            "Chers Membres du Groupe %s,\n\n",
            $groupeName
        );
        
        $message .= sprintf(
            "Le compte épargne groupe N° %s %s du montant de %s %s.\n",
            $numeroCompte,
            $action,
            number_format($mouvement->montant, 0, ',', ''),
            $mouvement->devise
        );
        
        $message .= sprintf("Libellé: %s\n", $description);
        
        $message .= sprintf(
            "Solde épargne groupe: %s %s\n",
            number_format($mouvement->solde_apres, 0, ',', ''),
            $mouvement->devise
        );
        
        $message .= sprintf(
            "Ref: %s\Le: %s\n\n",
            $mouvement->reference ?: 'EPG-GRP-' . $mouvement->id,
            now()->format('d/m/Y H:i')
        );
        
        $message .= "TUMAINI-LETU ÉPARGNE GROUPE\nL'union fait la force, l'épargne fait la richesse!";
        
        return $message;
    }
}