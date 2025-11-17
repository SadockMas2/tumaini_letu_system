{{-- resources/views/filament/resources/rapport-tresorerie-resource/pages/view-rapport-tresorerie.blade.php --}}

<x-filament-panels::page>
    <x-filament-panels::header :actions="$this->getHeaderActions()">
        <x-slot name="heading">
            Rapport de Trésorerie - {{ $this->record->numero_rapport }}
        </x-slot>

        <x-slot name="description">
            Date du rapport: {{ $this->record->date_rapport->format('d/m/Y') }}
        </x-slot>
    </x-filament-panels::header>

    {{-- Infolist principal --}}
    {{ $this->infolist }}

    {{-- Section supplémentaire pour la synthèse par devise --}}
    <x-filament::section>
        <x-slot name="heading">
            Synthèse par Devise
        </x-slot>

        <x-slot name="description">
            Répartition des soldes et mouvements par devise
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- USD --}}
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">USD - Dollars Américains</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solde Total:</span>
                        <span class="font-semibold text-green-600">${{ number_format($this->viewData['synthese']['usd']['solde_total'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Dépôts:</span>
                        <span class="font-semibold text-green-600">${{ number_format($this->viewData['synthese']['usd']['depots'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Retraits:</span>
                        <span class="font-semibold text-red-600">${{ number_format($this->viewData['synthese']['usd']['retraits'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Opérations:</span>
                        <span class="font-semibold text-blue-600">{{ $this->viewData['synthese']['usd']['operations'] }}</span>
                    </div>
                </div>
            </div>

            {{-- CDF --}}
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">CDF - Francs Congolais</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solde Total:</span>
                        <span class="font-semibold text-green-600">{{ number_format($this->viewData['synthese']['cdf']['solde_total'], 2) }} CDF</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Dépôts:</span>
                        <span class="font-semibold text-green-600">{{ number_format($this->viewData['synthese']['cdf']['depots'], 2) }} CDF</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Retraits:</span>
                        <span class="font-semibold text-red-600">{{ number_format($this->viewData['synthese']['cdf']['retraits'], 2) }} CDF</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Opérations:</span>
                        <span class="font-semibold text-blue-600">{{ $this->viewData['synthese']['cdf']['operations'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>