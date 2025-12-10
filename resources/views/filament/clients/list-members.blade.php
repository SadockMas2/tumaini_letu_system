<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 p-4">
    @foreach($clients as $client)
        <div class="bg-white rounded-xl shadow p-4 border">
            
            <!-- Photo -->
            <div class="flex justify-center mb-3">
                @if($client->image)
                    <img src="{{ asset('storage/' . $client->image) }}" 
                         class="w-28 h-28 rounded-full object-cover border shadow">
                @else
                    <div class="w-28 h-28 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500 text-sm">Aucune photo</span>
                    </div>
                @endif
            </div>

            <!-- Nom -->
            <h3 class="text-center font-semibold text-lg mb-2">
                {{ $client->nom }} {{ $client->postnom }} {{ $client->prenom }}
            </h3>

            <p class="text-center text-sm text-gray-600 mb-4">
                Membre N° : {{ $client->numero_membre }}
            </p>

            <!-- Signature -->
            <div class="flex justify-center mb-3">
                @if($client->signature)
                    <img src="{{ asset('storage/' . $client->signature) }}"
                         class="w-32 h-20 object-contain bg-white border rounded-md p-1 shadow">
                @else
                    <div class="w-32 h-20 bg-gray-100 border rounded-md flex items-center justify-center">
                        <span class="text-gray-500 text-xs">Aucune signature</span>
                    </div>
                @endif
            </div>

            <div class="text-center text-sm text-gray-700">
                <p><strong>Téléphone : </strong>{{ $client->telephone }}</p>
                <p><strong>Type : </strong>{{ $client->type_client }}</p>
            </div>
        </div>
    @endforeach
</div>
