<div class="space-y-4">
    <!-- Bannière d'avertissement -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>VÉRIFIEZ ATTENTIVEMENT LES INFORMATIONS</strong><br>
                    Cette action est irréversible. Une fois confirmée, l'opération sera enregistrée dans le système.
                </p>
            </div>
        </div>
    </div>

    <!-- Détails de l'opération -->
    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">
            <svg class="inline-block h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            RÉCAPITULATIF DE L'OPÉRATION
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Type d'opération -->
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-500">Type d'Opération</label>
                <div class="text-lg font-semibold text-gray-900 p-2 bg-gray-50 rounded">
                    @php
                        $types = [
                            'depot_compte' => 'Dépôt vers Compte Membre',
                            'retrait_compte' => 'Retrait depuis Compte Courant',
                            'retrait_epargne' => 'Retrait depuis Compte Épargne',
                            'paiement_credit' => 'Paiement de Crédit',
                            'versement_agent' => 'Versement Agent Collecteur',
                            'transfert_caisse' => 'Transfert entre Caisses',
                            'achat_carnet_livre' => 'Achat Carnet et Livres',
                            'frais_adhesion' => 'Frais d\'Adhésion',
                            'frais_sms' => 'Frais SMS',
                        ];
                        echo $types[$type_operation] ?? $type_operation;
                    @endphp
                </div>
            </div>

            <!-- Montant -->
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-500">Montant</label>
                <div class="text-2xl font-bold text-green-600 p-2 bg-green-50 rounded">
                    {{ number_format($montant, 2) }} {{ $devise }}
                </div>
            </div>
        </div>

        <!-- Informations spécifiques -->
        <div class="border-t pt-4">
            @if($client_nom)
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-500">Client/Agent</label>
                <div class="text-md font-medium text-gray-900 p-2 bg-blue-50 rounded">
                    <svg class="inline-block h-4 w-4 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ $client_nom }}
                </div>
            </div>
            @endif

            @if($compte_numero)
            <div class="space-y-2 mt-3">
                <label class="block text-sm font-medium text-gray-500">Numéro de Compte</label>
                <div class="text-md font-medium text-gray-900 p-2 bg-indigo-50 rounded">
                    <svg class="inline-block h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    {{ $compte_numero }}
                </div>
            </div>
            @endif

            @if($description)
            <div class="space-y-2 mt-3">
                <label class="block text-sm font-medium text-gray-500">Description</label>
                <div class="text-sm text-gray-700 p-2 bg-gray-50 rounded">
                    {{ $description }}
                </div>
            </div>
            @endif
        </div>

        <!-- Informations système -->
        <div class="border-t pt-4">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-500">Informations Système</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div class="p-2 bg-gray-50 rounded">
                        <span class="text-gray-500">Opérateur :</span>
                        <span class="font-medium">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="p-2 bg-gray-50 rounded">
                        <span class="text-gray-500">Date :</span>
                        <span class="font-medium">{{ now()->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message final -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>CONFIRMATION REQUISE</strong><br>
                        Cliquez sur "Confirmer et Exécuter" pour finaliser l'opération.<br>
                        Une fois confirmé, cette opération sera enregistrée définitivement.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>