@if(!isset($type) || !$type)
<div class="p-8 text-center">
    <div class="inline-flex items-center justify-center space-x-4">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        <div>
            <p class="text-lg font-medium text-gray-700">Préparation du récapitulatif...</p>
            <p class="text-sm text-gray-500 mt-2">Veuillez remplir le formulaire ci-dessus</p>
        </div>
    </div>
</div>
@else
<div class="space-y-6">
    <!-- Alerte importante -->
    <div class="bg-red-50 border-l-4 border-red-500 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">DOUBLE VÉRIFICATION REQUISE</h3>
                <p class="text-sm text-red-700 mt-1">
                    Cette action est irréversible. Vérifiez attentivement toutes les informations avant de continuer.
                </p>
            </div>
        </div>
    </div>

    <!-- Carte principale de récapitulatif -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
        <!-- En-tête -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">📋 RÉCAPITULATIF DÉTAILLÉ</h2>
                    <p class="text-primary-100 text-sm">Vérifiez toutes les informations ci-dessous</p>
                </div>
                <div class="bg-white/20 px-3 py-1 rounded-full">
                    <span class="text-xs font-semibold text-white uppercase">
                        {{ now()->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Corps de la carte -->
        <div class="p-6 space-y-6">
            <!-- Section Type et Montant -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Type d'Opération</p>
                            <p class="text-lg font-bold text-gray-900">{{ $type }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Montant</p>
                            <p class="text-2xl font-bold text-green-600">{{ $montant }} {{ $devise }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Client et Compte -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Client</p>
                            <p class="text-base font-semibold text-gray-900">{{ $clientNom }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Numéro de Compte</p>
                            <p class="text-base font-semibold text-gray-900">{{ $compteNumero }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Description -->
            @if($description && $description !== 'Aucune description')
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                <div class="flex items-center space-x-3 mb-3">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-xs font-medium text-blue-800 uppercase tracking-wide">Description</p>
                        <p class="text-sm text-blue-900 mt-1">{{ $description }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Section Informations Système -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Informations Système</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Opérateur</p>
                                <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                            </div>
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Date et Heure</p>
                                <p class="text-sm font-semibold text-gray-900">{{ now()->format('d/m/Y H:i:s') }}</p>
                            </div>
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message d'avertissement final -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">CONFIRMATION REQUISE</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Cliquez sur <strong class="text-green-600">"Vérifier et continuer"</strong> pour afficher la confirmation finale.</p>
                            <p class="mt-1 font-semibold">⚠️ Cette action ne peut pas être annulée une fois exécutée.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif