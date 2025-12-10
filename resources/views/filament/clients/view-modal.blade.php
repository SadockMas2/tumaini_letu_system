<div class="space-y-6">
    <!-- Informations de base -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Informations personnelles</h3>
            <dl class="mt-2 space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Numéro membre</dt>
                    <dd class="text-sm text-gray-900">{{ $client->numero_membre }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nom complet</dt>
                    <dd class="text-sm text-gray-900">{{ $client->nom }} {{ $client->postnom }} {{ $client->prenom }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date de naissance</dt>
                    <dd class="text-sm text-gray-900">{{ $client->date_naissance?->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ $client->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                    <dd class="text-sm text-gray-900">{{ $client->telephone }}</dd>
                </div>
            </dl>
        </div>
        
        <div>
            <h3 class="text-lg font-medium text-gray-900">Adresse</h3>
            <dl class="mt-2 space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                    <dd class="text-sm text-gray-900">{{ $client->adresse }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Ville</dt>
                    <dd class="text-sm text-gray-900">{{ $client->ville }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Pays</dt>
                    <dd class="text-sm text-gray-900">{{ $client->pays }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Code postal</dt>
                    <dd class="text-sm text-gray-900">{{ $client->code_postal }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Photo et Signature en grand -->
    <div class="grid grid-cols-2 gap-6 border-t pt-6">
        <!-- Photo du membre -->
        <div class="text-center">
            <h4 class="text-md font-medium text-gray-900 mb-4">Photo du membre</h4>
            @if($client->image)
                <img class="zoomable-image"
                    src="{{ asset('storage/' . $client->image) }}" 
                    alt="Photo de {{ $client->nom }} {{ $client->prenom }}"
                    class="mx-auto w-48 h-48 object-cover rounded-lg shadow-md"
                >
            @else
                <div class="mx-auto w-48 h-48 bg-gray-200 rounded-lg flex items-center justify-center">
                    <span class="text-gray-500">Aucune photo</span>
                </div>
            @endif
        </div>

        <!-- Signature -->
        <div class="text-center">
            <h4 class="text-md font-medium text-gray-900 mb-4">Signature</h4>
            @if($client->signature)
                <img class="zoomable-image"
                    src="{{ asset('storage/' . $client->signature) }}" 
                    alt="Signature de {{ $client->nom }} {{ $client->prenom }}"
                    class="mx-auto w-48 h-32 object-contain bg-white border rounded-lg p-2"
                >
            @else
                <div class="mx-auto w-48 h-32 bg-gray-100 border rounded-lg flex items-center justify-center">
                    <span class="text-gray-500">Aucune signature</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Informations supplémentaires -->
    <div class="border-t pt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations supplémentaires</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Activités</dt>
                <dd class="text-sm text-gray-900">{{ $client->activites ?? 'Non spécifié' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Type de client</dt>
                <dd class="text-sm text-gray-900">{{ $client->type_client ?? 'Non spécifié' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Type de compte</dt>
                <dd class="text-sm text-gray-900">{{ $client->type_compte ?? 'Non spécifié' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">État civil</dt>
                <dd class="text-sm text-gray-900">{{ $client->etat_civil ?? 'Non spécifié' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Statut</dt>
                <dd class="text-sm text-gray-900">{{ $client->status ?? 'Non spécifié' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Identifiant national</dt>
                <dd class="text-sm text-gray-900">{{ $client->identifiant_national ?? 'Non spécifié' }}</dd>
            </div>
        </div>
    </div>
</div>

<style>
    .zoomable-image {
        cursor: zoom-in;
        transition: transform 0.3s ease;
    }
    .zoomable-image:hover {
        transform: scale(1.05);
    }
</style>