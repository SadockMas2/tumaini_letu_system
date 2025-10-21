<x-filament::page>
    <x-filament::card>
        <h2 class="text-lg font-bold mb-4">Test Débit Compte Transitoire</h2>
        
        <form wire:submit.prevent="testerDebit" class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <x-filament::input.wrapper>
                    <x-filament::input 
                        type="number" 
                        wire:model="userId" 
                        label="User ID" 
                        required 
                    />
                </x-filament::input.wrapper>
                
                <x-filament::input.wrapper>
                    <x-filament::input 
                        type="text" 
                        wire:model="devise" 
                        label="Devise" 
                        required 
                    />
                </x-filament::input.wrapper>
                
                <x-filament::input.wrapper>
                    <x-filament::input 
                        type="number" 
                        wire:model="montant" 
                        label="Montant" 
                        required 
                    />
                </x-filament::input.wrapper>
            </div>
            
            <x-filament::button type="submit" color="primary">
                Tester le Débit
            </x-filament::button>
        </form>
        
        @if($resultat)
            <div class="mt-6 p-4 bg-gray-100 rounded">
                <h3 class="font-bold mb-2">Résultat :</h3>
                <pre>{{ json_encode($resultat, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    </x-filament::card>
</x-filament::page>