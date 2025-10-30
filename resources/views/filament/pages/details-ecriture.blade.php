{{-- resources/views/filament/pages/details-ecriture.blade.php --}}
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500">Date Écriture</p>
            <p class="text-lg">{{ $ecriture->date_ecriture->format('d/m/Y') }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Référence</p>
            <p class="text-lg font-mono">{{ $ecriture->reference_operation }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500">Journal</p>
            <p class="text-lg">{{ $ecriture->journal->libelle_journal }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Type Opération</p>
            <p class="text-lg">{{ $ecriture->type_operation }}</p>
        </div>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-500">Libellé</p>
        <p class="text-lg">{{ $ecriture->libelle }}</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="p-4 bg-red-50 rounded-lg">
            <p class="text-sm font-medium text-red-500">Débit</p>
            <p class="text-xl font-bold text-red-700">{{ number_format($ecriture->montant_debit, 2) }} USD</p>
        </div>
        <div class="p-4 bg-green-50 rounded-lg">
            <p class="text-sm font-medium text-green-500">Crédit</p>
            <p class="text-xl font-bold text-green-700">{{ number_format($ecriture->montant_credit, 2) }} USD</p>
        </div>
    </div>

    @if($ecriture->piece_justificative)
    <div>
        <p class="text-sm font-medium text-gray-500">Pièce Justificative</p>
        <a href="{{ asset('storage/' . $ecriture->piece_justificative) }}" 
           target="_blank" 
           class="text-blue-600 hover:text-blue-800">
            Voir la pièce
        </a>
    </div>
    @endif
</div>