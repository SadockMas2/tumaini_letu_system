<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-swatch" compact>
        <x-slot name="heading">
            Sélecteur de thème
        </x-slot>

        <div class="flex justify-center">
            {{ $this->getThemeAction }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>