{{-- resources/views/filament/pages/sms-settings.blade.php --}}
<x-filament-panels::page>
    {{-- Le formulaire est géré automatiquement par SettingsPage --}}
    @if($balance = Cache::get('sms_balance'))
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Solde SMS Actuel
            </x-slot>
            
            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Solde disponible</p>
                        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ $balance['BalanceAmount'] ?? 0 }} {{ $balance['CurrencyCode'] ?? 'USD' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dernière vérification</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ Cache::get('sms_balance_checked_at', 'Jamais') }}
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>