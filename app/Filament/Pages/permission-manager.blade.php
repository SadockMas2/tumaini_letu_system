<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50/30 py-8 px-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-6 border border-blue-100">
                <div class="text-3xl">🛡️</div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                Gestion des Rôles & Permissions
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Créez et géz les rôles avec leurs permissions associées
            </p>
        </div>

        <!-- Formulaire de création de rôle -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                <span>➕</span>
                Créer un Nouveau Rôle
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Nom du rôle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nom du Rôle
                    </label>
                    <input 
                        type="text" 
                        wire:model="newRoleName"
                        placeholder="Ex: Gestionnaire, Superviseur, etc."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <!-- Barre de recherche des permissions -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        🔍 Rechercher des permissions
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Tapez pour filtrer les permissions..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sélection des permissions par catégories -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <span>📋</span>
                    Sélection des Permissions
                </h2>
                <p class="text-gray-600 text-sm mt-1">Cochez les permissions à attribuer au nouveau rôle</p>
            </div>

            <div class="p-6">
                <!-- Permissions groupées par catégorie -->
                <div class="space-y-6">
                    @forelse($this->getFilteredCategories() as $category => $permissions)
                        <!-- Catégorie -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <!-- En-tête de catégorie -->
                            <div class="bg-blue-50 px-4 py-3 border-b border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-blue-600">📁</span>
                                        <h3 class="font-semibold text-blue-900 text-lg">{{ $category }}</h3>
                                    </div>
                                    <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                        {{ $permissions->count() }} permission(s)
                                    </span>
                                </div>
                            </div>

                            <!-- Permissions de la catégorie -->
                            <div class="bg-white p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                            <input 
                                                type="checkbox" 
                                                wire:model="selectedPermissions"
                                                value="{{ $permission->id }}"
                                                class="mt-1 text-blue-600 focus:ring-blue-500 rounded"
                                            >
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 text-sm">
                                                    {{ $this->formatPermissionName($permission->name) }}
                                                </div>
                                                @if($permission->description)
                                                    <div class="text-gray-500 text-xs mt-1">
                                                        {{ $permission->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <!-- État vide -->
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">🔍</div>
                            <p class="text-lg font-semibold text-gray-500 mb-2">Aucune permission trouvée</p>
                            <p class="text-gray-400">Essayez de modifier vos critères de recherche</p>
                        </div>
                    @endforelse
                </div>

                <!-- Bouton de création -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button 
                        wire:click="createRole"
                        class="w-full bg-gradient-to-r from-blue-500 to-purple-500 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:from-blue-600 hover:to-purple-600 transition-all duration-200 shadow-lg hover:shadow-xl"
                    >
                        🚀 Créer le Rôle avec les Permissions Sélectionnées
                    </button>
                </div>
            </div>
        </div>

        <!-- Rôles existants -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mt-8">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <span>👥</span>
                    Rôles Existants
                </h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($roles as $role)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-gray-900 text-lg">{{ $role->name }}</h3>
                                @if($role->name !== 'Admin')
                                    <button 
                                        wire:click="deleteRole({{ $role->id }})"
                                        wire:confirm="Supprimer le rôle '{{ $role->name }}' ?"
                                        class="text-red-500 hover:text-red-700 text-sm"
                                    >
                                        🗑️
                                    </button>
                                @else
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                        👑 Admin
                                    </span>
                                @endif
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                {{ $role->permissions->count() }} permission(s)
                            </div>
                            
                            <!-- Permissions du rôle -->
                            <div class="mt-3 space-y-1 max-h-32 overflow-y-auto">
                                @foreach($role->permissions->take(5) as $permission)
                                    <div class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        {{ $this->formatPermissionName($permission->name) }}
                                    </div>
                                @endforeach
                                @if($role->permissions->count() > 5)
                                    <div class="text-xs text-gray-400 text-center">
                                        +{{ $role->permissions->count() - 5 }} autres...
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>