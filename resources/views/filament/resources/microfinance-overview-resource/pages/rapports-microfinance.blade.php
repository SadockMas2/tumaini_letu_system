<x-filament-panels::page>
    <x-filament-panels::header
        :actions="$this->getHeaderActions()"
    >
        <x-slot name="heading">
            {{ $this->getTitle() }}
        </x-slot>
        
        <x-slot name="description">
            Rapport détaillé des crédits et performances
        </x-slot>
    </x-filament-panels::header>

    {{-- Widgets de statistiques --}}
    @if(count($this->getHeaderWidgets()))
        <x-filament-widgets::widgets
            :columns="2"
            :widgets="$this->getHeaderWidgets()"
            class="mb-6"
        />
    @endif

    {{-- Table des crédits --}}
    <div class="p-6 bg-white rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Liste des Crédits Actifs
        </h3>
        {{ $this->table }}
    </div>
</x-filament-panels::page>