<div class="p-6 space-y-6">
    <div class="text-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Système de Paiement des Remboursements</h3>
        <p class="text-sm text-gray-600">Choisissez le type de crédit que vous souhaitez traiter</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Crédits Individuels -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Crédits Individuels</h4>
            <p class="text-sm text-gray-600 mb-4">Paiement automatique de tous les crédits individuels actifs</p>
            <x-filament::button 
                wire:click="$dispatch('open-modal', { id: 'paiement-individuels' })"
                color="primary"
                icon="heroicon-m-user"
                class="w-full">
                Traiter les Crédits Individuels
            </x-filament::button>
        </div>

        <!-- Crédits Groupe -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Crédits Groupe</h4>
            <p class="text-sm text-gray-600 mb-4">Gestion des remboursements par groupe avec répartition détaillée</p>
            <x-filament::button 
                wire:click="$dispatch('open-modal', { id: 'paiement-groupes' })"
                color="success"
                icon="heroicon-m-users"
                class="w-full">
                Gérer les Crédits Groupe
            </x-filament::button>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="bg-gray-50 rounded-lg p-4 mt-6">
        <h4 class="font-semibold text-gray-900 mb-3">Aperçu des Crédits Actifs</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center">
                <div class="text-blue-600 font-semibold">{{ $this->getCombinedCredits()->where('type_credit', 'individuel')->count() }}</div>
                <div class="text-gray-600">Crédits Individuels</div>
            </div>
            <div class="text-center">
                <div class="text-green-600 font-semibold">{{ $this->getCombinedCredits()->where('type_credit', 'groupe')->count() }}</div>
                <div class="text-gray-600">Crédits Groupe</div>
            </div>
        </div>
    </div>
</div>