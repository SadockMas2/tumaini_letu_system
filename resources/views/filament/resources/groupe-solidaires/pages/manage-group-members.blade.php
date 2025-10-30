<x-filament-panels::page>
    <x-filament-panels::header
        :actions="$this->getCachedHeaderActions()"
    >
        <x-slot name="heading">
            Gestion des membres : {{ $this->record->nom_groupe }}
        </x-slot>

        <x-slot name="description">
            Groupe #{{ $this->record->numero_groupe }} - {{ $this->record->membres_count }} membre(s)
        </x-slot>
    </x-filament-panels::header>

    {{ $this->table }}

</x-filament-panels::page>