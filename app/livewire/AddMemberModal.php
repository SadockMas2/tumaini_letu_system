<div>
    <x-filament::modal heading="Ajouter un membre au groupe" size="md">
        <form wire:submit="addMember">
            {{ $this->form }}

            <div class="flex justify-end gap-4 pt-6">
                <x-filament::button color="gray" wire:click="$dispatch('closeModal')">
                    Annuler
                </x-filament::button>
                <x-filament::button type="submit">
                    Ajouter
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</div>