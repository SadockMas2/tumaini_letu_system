<x-filament-panels::page>
    <x-filament-panels::header
        :actions="[
            \Filament\Actions\Action::make('aide')
                ->label('Aide')
                ->color('gray')
                ->action('toggleInfo'),
        ]"
    >
        <x-slot name="heading">
            Gestion des Permissions
        </x-slot>
        
        <x-slot name="description">
            Gérez les autorisations d'accès pour chaque rôle
        </x-slot>
    </x-filament-panels::header>

    <!-- Content -->
    <div class="space-y-6">
        <!-- Search -->
        <x-filament::section>
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Rechercher une permission..."
                    icon="heroicon-o-magnifying-glass"
                />
            </x-filament::input.wrapper>
        </x-filament::section>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $roles->count() }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Rôles</div>
            </x-filament::section>
            
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold text-success-600">{{ $permissions->count() }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Permissions</div>
            </x-filament::section>
            
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold text-warning-600">{{ count($permissionCategories) }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Catégories</div>
            </x-filament::section>
        </div>

        <!-- Table -->
        <x-filament::section>
            <x-slot name="heading">
                Tableau des Permissions
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/10">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Permission</th>
                            @foreach($roles as $role)
                                <th class="px-4 py-3 text-center font-medium text-gray-900 dark:text-gray-200">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="font-semibold">{{ $role->name }}</span>
                                        @if($role->name !== 'Admin')
                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                wire:click="deleteRole({{ $role->id }})"
                                                wire:confirm="Êtes-vous sûr de vouloir supprimer le rôle '{{ $role->name }}' ?"
                                            >
                                                Supprimer
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($filteredPermissionCategories as $category => $permissions)
                            <tr class="bg-gray-50/50 dark:bg-gray-800">
                                <td colspan="{{ $roles->count() + 1 }}" class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $category }}</span>
                                        <span class="text-xs text-gray-500 bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded-full">
                                            {{ $permissions->count() }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @foreach($permissions as $permission)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $permission->name }}</div>
                                        @if($permission->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $permission->description }}</div>
                                        @endif
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="px-4 py-3 text-center">
                                            @if($role->name === 'Admin')
                                                <x-filament::badge color="success" size="sm">
                                                    Tous accès
                                                </x-filament::badge>
                                            @else
                                                <input 
                                                    type="checkbox" 
                                                    wire:change="updateRolePermissions({{ $role->id }}, {{ $permission->id }}, $event.target.checked)"
                                                    @checked(in_array($permission->id, $rolePermissions[$role->id] ?? []))
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:checked:border-primary-500 dark:checked:bg-primary-500"
                                                >
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ $roles->count() + 1 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-magnifying-glass class="h-12 w-12 mb-4" />
                                        <p>Aucune permission trouvée</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <!-- Modal -->
    <x-filament::modal wire:model="showInfo">
        <x-slot name="heading">
            Guide d'utilisation
        </x-slot>

        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-start gap-2">
                <x-heroicon-o-check-circle class="h-5 w-5 text-success-500 mt-0.5 flex-shrink-0" />
                <span>Cochez/décochez les cases pour modifier les permissions</span>
            </div>
            <div class="flex items-start gap-2">
                <x-heroicon-o-magnifying-glass class="h-5 w-5 text-primary-500 mt-0.5 flex-shrink-0" />
                <span>Utilisez la recherche pour filtrer les permissions</span>
            </div>
            <div class="flex items-start gap-2">
                <x-heroicon-o-shield-check class="h-5 w-5 text-success-500 mt-0.5 flex-shrink-0" />
                <span>Le rôle Admin dispose automatiquement de tous les accès</span>
            </div>
            <div class="flex items-start gap-2">
                <x-heroicon-o-trash class="h-5 w-5 text-danger-500 mt-0.5 flex-shrink-0" />
                <span>Supprimez les rôles inutiles pour maintenir la sécurité</span>
            </div>
        </div>

        <x-slot name="footer">
            <x-filament::button wire:click="$set('showInfo', false)">
                Fermer
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>