<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if ($epargne_id && count($repartitions) > 0)
            <!-- Indicateur d'étape -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full {{ $est_credite ? 'bg-green-500' : 'bg-gray-300' }} flex items-center justify-center text-white font-bold">
                                1
                            </div>
                            <span class="text-sm mt-1 {{ $est_credite ? 'text-green-600 font-medium' : 'text-gray-500' }}">Crédit Compte</span>
                        </div>
                        <div class="w-12 h-1 {{ $est_credite ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full {{ $est_credite ? 'bg-blue-500' : 'bg-gray-300' }} flex items-center justify-center text-white font-bold">
                                2
                            </div>
                            <span class="text-sm mt-1 {{ $est_credite ? 'text-blue-600 font-medium' : 'text-gray-500' }}">Dispatch</span>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        {{ $this->crediter }}
                        {{ $this->dispatcher }}
                    </div>
                </div>
            </div>

            <!-- Tableau de répartition (visible seulement après crédit) -->
            @if($est_credite)
                <!-- Tableau de répartition -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Répartition entre les membres</h3>
                        <p class="text-sm text-gray-600 mt-1">Attribuez les montants aux membres - Seul le total doit correspondre</p>
                    </div>

                    <!-- En-tête du tableau -->
                    <div class="grid grid-cols-12 gap-4 px-6 py-3 border-b border-gray-200 bg-primary-50 text-sm font-medium text-primary-900">
                        <div class="col-span-5">Membre</div>
                        <div class="col-span-4">Montant attribué</div>
                        <div class="col-span-2 text-right">Pourcentage</div>
                        <div class="col-span-1"></div>
                    </div>

                    <!-- Corps du tableau -->
                    <div class="divide-y divide-gray-200">
                        @foreach ($repartitions as $index => $repartition)
                            <div class="grid grid-cols-12 gap-4 items-center px-6 py-4 hover:bg-gray-50 transition-colors">
                                <!-- Nom du membre -->
                                <div class="col-span-5">
                                    <div class="font-medium text-gray-900">{{ $repartition['membre_nom'] }}</div>
                                </div>
                                
                                <!-- Champ montant -->
                                <div class="col-span-4">
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            min="0"
                                            max="{{ $montant_total }}"
                                            wire:model.live="repartitions.{{ $index }}.montant" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 filament-forms-text-input-component"
                                            placeholder="0.00"
                                        >
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 text-sm">{{ $devise }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pourcentage -->
                                <div class="col-span-2 text-right">
                                    @php
                                        $montant = $repartition['montant'] === '' ? 0 : (float) $repartition['montant'];
                                        $pourcentage = $montant_total > 0 ? ($montant / $montant_total) * 100 : 0;
                                    @endphp
                                    <span class="text-sm font-medium text-gray-600 {{ $pourcentage > 0 ? 'text-primary-600' : '' }}">
                                        {{ number_format($pourcentage, 1) }}%
                                    </span>
                                </div>
                                
                                <!-- Indicateur -->
                                <div class="col-span-1 text-center">
                                    @if($montant > 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-800 rounded-full text-xs">
                                            ✓
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-400 rounded-full text-xs">
                                            •
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Résumé et actions -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <!-- Résumé -->
                            <div class="space-y-2">
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">Total à répartir :</span> 
                                    <span class="font-bold">{{ number_format($montant_total, 2) }} {{ $devise }}</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">Total réparti :</span> 
                                    <span class="font-bold">{{ number_format($totalReparti, 2) }} {{ $devise }}</span>
                                </div>
                                <div class="text-sm {{ $resteARepartir != 0 ? 'text-red-600' : 'text-green-600' }}">
                                    <span class="font-medium">Reste à répartir :</span> 
                                    <span class="font-bold">{{ number_format($resteARepartir, 2) }} {{ $devise }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Message d'information -->
                        @if(!$estRepartitionValide)
                            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span class="text-sm text-yellow-700">
                                        @if($resteARepartir > 0)
                                            Il reste {{ number_format($resteARepartir, 2) }} {{ $devise }} à répartir
                                        @else
                                            Vous avez dépassé le montant total de {{ number_format(abs($resteARepartir), 2) }} {{ $devise }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-sm text-green-700">
                                        Le montant est correctement réparti
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <div class="text-blue-600">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-blue-800">Étape 1 requise</h3>
                        <p class="mt-1 text-sm text-blue-700">Veuillez d'abord créditer le compte transitoire avant de pouvoir dispatcher.</p>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament::page>