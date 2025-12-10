@php
    $this->mount();
@endphp

<x-filament-widgets :widgets="[
    \App\Filament\Resources\Clients\Widgets\GalerieClientsTable::class
]" />
