<div class="p-6 bg-white rounded-lg shadow">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Résultats des Paiements</h2>
        <p class="text-gray-600">Exécutés le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
            <div class="text-green-800 font-semibold">Crédits Traités</div>
            <div class="text-2xl font-bold text-green-600">{{ $results['credits_traites'] }}</div>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <div class="text-blue-800 font-semibold">Total Prélevé</div>
            <div class="text-2xl font-bold text-blue-600">{{ number_format($results['total_preleve'], 2) }} USD</div>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
            <div class="text-orange-800 font-semibold">En Retard</div>
            <div class="text-2xl font-bold text-orange-600">{{ $results['credits_en_retard'] }}</div>
        </div>
    </div>

    <!-- Détails des crédits individuels -->
    @if(count($results['individuels']) > 0)
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">📊 Crédits Individuels</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Compte</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Montant Prélevé</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Montant Dû</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Capital</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Intérêts</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['individuels'] as $individuel)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ $individuel['compte'] }}</td>
                        <td class="px-4 py-2 text-sm font-medium">{{ number_format($individuel['montant_preleve'], 2) }} USD</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($individuel['montant_du'], 2) }} USD</td>
                        <td class="px-4 py-2 text-sm text-green-600">{{ number_format($individuel['capital'], 2) }} USD</td>
                        <td class="px-4 py-2 text-sm text-blue-600">{{ number_format($individuel['interets'], 2) }} USD</td>
                        <td class="px-4 py-2 text-sm">
                            @if($individuel['statut'] == 'succes')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✅ Complet
                                </span>
                            @elseif($individuel['statut'] == 'partiel')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    ⚠️ Partiel
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ❌ Échec
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Détails des crédits groupe -->
    @if(count($results['groupes']) > 0)
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">👥 Crédits Groupe</h3>
        @foreach($results['groupes'] as $groupe)
        <div class="mb-4 p-4 border border-gray-200 rounded-lg">
            <div class="flex justify-between items-center mb-2">
                <h4 class="font-semibold">{{ $groupe['compte_groupe'] }}</h4>
                <div class="text-sm">
                    Total: <span class="font-bold">{{ number_format($groupe['total_preleve'], 2) }} USD</span>
                    @if($groupe['en_retard'])
                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        En retard
                    </span>
                    @endif
                </div>
            </div>
            <div class="text-sm text-gray-600">
                @foreach($groupe['membres'] as $membre)
                <div class="flex justify-between py-1 border-b border-gray-100">
                    <span>{{ $membre['compte'] }}</span>
                    <span>
                        {{ number_format($membre['montant_preleve'], 2) }} USD / 
                        {{ number_format($membre['montant_du'], 2) }} USD
                        @if($membre['statut'] == 'partiel')
                        <span class="text-yellow-600 ml-1">⚠️</span>
                        @elseif($membre['statut'] == 'echec')
                        <span class="text-red-600 ml-1">❌</span>
                        @else
                        <span class="text-green-600 ml-1">✅</span>
                        @endif
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="flex justify-end space-x-3 mt-6">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            📄 Imprimer le Rapport
        </button>
        <button onclick="window.close()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Fermer
        </button>
    </div>
</div>