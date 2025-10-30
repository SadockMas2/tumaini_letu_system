<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéancier Membre - Crédit Groupe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- En-tête avec informations -->
            <div class="text-center mb-8 border-b pb-6">
                <h1 class="text-2xl font-bold text-gray-800">ÉCHÉANCIER DE REMBOURSEMENT</h1>
                <p class="text-gray-600 mt-2">Crédit Groupe Solidaire</p>
            </div>

            <!-- Informations du membre -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">INFORMATIONS DU MEMBRE</h3>
                    <p class="text-sm"><strong>Nom:</strong> {{ $compteMembre->nom }} {{ $compteMembre->prenom }}</p>
                    <p class="text-sm"><strong>Compte:</strong> {{ $compteMembre->numero_compte }}</p>
                    <p class="text-sm"><strong>Groupe:</strong> {{ $credit->compte->nom ?? 'Groupe Solidaire' }}</p>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-800 mb-2">DÉTAILS DU CRÉDIT</h3>
                    <p class="text-sm"><strong>Montant accordé:</strong> {{ number_format(floatval($creditIndividuel->montant_accorde ?? 0), 2, ',', ' ') }} USD</p>
                    <p class="text-sm"><strong>Total à rembourser:</strong> {{ number_format(floatval($creditIndividuel->montant_total ?? 0), 2, ',', ' ') }} USD</p>
                    <p class="text-sm"><strong>Remboursement/semaine:</strong> {{ number_format(floatval($creditIndividuel->remboursement_hebdo ?? 0), 2, ',', ' ') }} USD</p>
                </div>
            </div>

            <!-- Échéancier -->
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-4 text-center">PLAN DE REMBOURSEMENT</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-800">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="border border-gray-600 p-3 text-center">SEM.</th>
                                <th class="border border-gray-600 p-3">DATE ÉCHÉANCE</th>
                                <th class="border border-gray-600 p-3 text-right">MONTANT À PAYER</th>
                                <th class="border border-gray-600 p-3 text-right">CAPITAL RESTANT</th>
                                <th class="border border-gray-600 p-3 text-center">STATUT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($echeanciers as $echeance)
                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                <td class="border border-gray-300 p-3 text-center font-bold">
                                    {{ $echeance->semaine }}
                                </td>
                                <td class="border border-gray-300 p-3">
                                    {{ \Carbon\Carbon::parse($echeance->date_echeance)->format('d/m/Y') }}
                                </td>
                                <td class="border border-gray-300 p-3 text-right font-bold text-green-700">
                                    {{ number_format(floatval($echeance->montant_a_payer ?? 0), 2, ',', ' ') }} USD
                                </td>
                                <td class="border border-gray-300 p-3 text-right text-gray-700">
                                    {{ number_format(floatval($echeance->capital_restant ?? 0), 2, ',', ' ') }} USD
                                </td>
                                <td class="border border-gray-300 p-3 text-center">
                                    @php
                                        $statutClass = [
                                            'a_venir' => 'bg-blue-100 text-blue-800 border border-blue-300',
                                            'echeance' => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                                            'paye' => 'bg-green-100 text-green-800 border border-green-300',
                                            'en_retard' => 'bg-red-100 text-red-800 border border-red-300'
                                        ][$echeance->statut] ?? 'bg-gray-100 text-gray-800 border border-gray-300';
                                        
                                        $statutText = [
                                            'a_venir' => 'À VENIR',
                                            'echeance' => 'ÉCHÉANCE',
                                            'paye' => 'PAYÉ',
                                            'en_retard' => 'EN RETARD'
                                        ][$echeance->statut] ?? 'INCONNU';
                                    @endphp
                                    <span class="px-3 py-1 text-xs font-bold rounded-full {{ $statutClass }}">
                                        {{ $statutText }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Résumé -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-100 p-4 rounded-lg text-center">
                    <p class="text-sm text-blue-800">Total à rembourser</p>
                    <p class="text-xl font-bold text-blue-900">
                        {{ number_format(floatval($creditIndividuel->montant_total ?? 0), 2, ',', ' ') }} USD
                    </p>
                </div>
                <div class="bg-green-100 p-4 rounded-lg text-center">
                    <p class="text-sm text-green-800">Remboursement/semaine</p>
                    <p class="text-xl font-bold text-green-900">
                        {{ number_format(floatval($creditIndividuel->remboursement_hebdo ?? 0), 2, ',', ' ') }} USD
                    </p>
                </div>
                <div class="bg-purple-100 p-4 rounded-lg text-center">
                    <p class="text-sm text-purple-800">Durée</p>
                    <p class="text-xl font-bold text-purple-900">16 semaines</p>
                </div>
            </div>

            <!-- Notes importantes -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h4 class="font-bold text-yellow-800 mb-2">📋 NOTES IMPORTANTES</h4>
                <ul class="text-sm text-yellow-700 list-disc list-inside space-y-1">
                    <li>Le remboursement commence 2 semaines après l'approbation du crédit</li>
                    <li>Les paiements doivent être effectués chaque semaine à la date d'échéance</li>
                    <li>En cas de retard, des pénalités de 10% peuvent s'appliquer</li>
                    <li>La caution sera débloquée après remboursement complet du crédit</li>
                </ul>
            </div>

            <!-- Signature -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-300">
                <div class="text-center">
                    <p class="font-semibold">Signature du Membre</p>
                    <div class="mt-8 border-t border-gray-400 w-48 mx-auto"></div>
                </div>
                <div class="text-center">
                    <p class="font-semibold">Signature Tumaini Letu</p>
                    <div class="mt-8 border-t border-gray-400 w-48 mx-auto"></div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="mt-8 flex gap-4 justify-center">
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                    🖨️ Imprimer cet Échéancier
                </button>
                <a href="{{ route('credits.echeanciers-groupe', $credit->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                    ← Retour aux Échéanciers
                </a>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; }
            .container { max-width: none; }
            button, .no-print { display: none; }
            .bg-blue-50, .bg-green-50, .bg-yellow-50 { background-color: #f0f9ff !important; }
        }
    </style>
</body>
</html>