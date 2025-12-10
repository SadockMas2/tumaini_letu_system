<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie des Membres - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .gallery-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .client-photo {
            transition: transform 0.3s ease;
        }
        .client-photo:hover {
            transform: scale(1.05);
        }
        .search-input {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .modal-overlay {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/admin" class="flex items-center text-gray-700 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour à l'administration
                    </a>
                </div>
                <div class="text-sm text-gray-500">
                    Connecté en tant que {{ Auth::user()->name }}
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen py-8 px-4">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-6">
                <i class="fas fa-users text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">Galerie des Membres</h1>
            <p class="text-white/80 text-lg max-w-2xl mx-auto">
                Visualisez et gérez les profils de tous vos membres avec une interface moderne et intuitive
            </p>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto">
            <!-- Search Bar -->
            <form method="GET" action="{{ route('galerie.clients') }}" class="flex justify-center mb-8">
                <div class="relative w-full max-w-2xl">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input 
                        type="text" 
                        name="search"
                        value="{{ $search }}"
                        placeholder="Rechercher un membre par nom, prénom, email, téléphone ou numéro membre..."
                        class="search-input w-full pl-10 pr-4 py-4 text-lg border-0 rounded-2xl shadow-xl focus:ring-2 focus:ring-purple-500 focus:outline-none transition-all duration-300"
                    >
                    @if($search)
                    <a href="{{ route('galerie.clients') }}" 
                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </form>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="stats-card rounded-xl p-4 text-center shadow-lg">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $stats['total'] }}
                    </div>
                    <div class="text-sm text-gray-600">Membres Total</div>
                    <div class="text-xs text-gray-500">Tous actifs</div>
                </div>
                <div class="stats-card rounded-xl p-4 text-center shadow-lg">
                    <div class="text-2xl font-bold text-green-600">
                        {{ $stats['avec_email'] }}
                    </div>
                    <div class="text-sm text-gray-600">Avec Email</div>
                    <div class="text-xs text-gray-500">
                        @if($stats['total'] > 0)
                            {{ number_format(($stats['avec_email'] / $stats['total']) * 100, 0) }}%
                        @else
                            0%
                        @endif
                    </div>
                </div>
                <div class="stats-card rounded-xl p-4 text-center shadow-lg">
                    <div class="text-2xl font-bold text-blue-600">
                        {{ $stats['avec_telephone'] }}
                    </div>
                    <div class="text-sm text-gray-600">Avec Téléphone</div>
                    <div class="text-xs text-gray-500">
                        @if($stats['total'] > 0)
                            {{ number_format(($stats['avec_telephone'] / $stats['total']) * 100, 0) }}%
                        @else
                            0%
                        @endif
                    </div>
                </div>
                <div class="stats-card rounded-xl p-4 text-center shadow-lg">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $stats['avec_signature'] }}
                    </div>
                    <div class="text-sm text-gray-600">Avec Signature</div>
                    <div class="text-xs text-gray-500">
                        @if($stats['total'] > 0)
                            {{ number_format(($stats['avec_signature'] / $stats['total']) * 100, 0) }}%
                        @else
                            0%
                        @endif
                    </div>
                </div>
            </div>

            <!-- Clients Grid -->
            @if($clients->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                    @foreach($clients as $client)
                        <div 
                            class="gallery-card rounded-2xl overflow-hidden cursor-pointer fade-in"
                            onclick="showClientDetails({{ $client->id }})"
                        >
                            <div class="relative overflow-hidden">
                                <img 
                                    src="{{ $client->getImageUrl() }}" 
                                    alt="{{ $client->nom }} {{ $client->prenom }}"
                                    class="client-photo w-full h-64 object-cover"
                                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($client->nom . '+' . $client->prenom) }}&background=667eea&color=fff&size=300'"
                                >
                                <div class="absolute top-4 right-4">
                                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                        <i class="fas fa-check-circle mr-1"></i>Actif
                                    </span>
                                </div>
                                <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                                    <div class="text-white opacity-0 hover:opacity-100 transition-opacity duration-300">
                                        <i class="fas fa-eye text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-800 text-center mb-2">
                                    {{ $client->nom }} {{ $client->prenom }}
                                </h3>
                                @if($client->postnom)
                                    <p class="text-sm text-gray-600 text-center">
                                        {{ $client->postnom }}
                                    </p>
                                @endif
                                @if($client->email)
                                    <p class="text-sm text-gray-600 text-center truncate" title="{{ $client->email }}">
                                        <i class="fas fa-envelope mr-1"></i>{{ $client->email }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500 text-center mt-2">
                                    <i class="fas fa-id-card mr-1"></i>#{{ $client->numero_membre }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($clients->hasPages())
                    <div class="flex justify-center mb-8">
                        <div class="stats-card rounded-xl p-4 shadow-lg">
                            <div class="flex items-center space-x-2">
                                @if($clients->onFirstPage())
                                    <span class="px-3 py-1 bg-gray-200 text-gray-500 rounded-md text-sm">Précédent</span>
                                @else
                                    <a href="{{ $clients->previousPageUrl() }}" class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 transition-colors">
                                        Précédent
                                    </a>
                                @endif

                                <span class="text-sm text-gray-600">
                                    Page {{ $clients->currentPage() }} sur {{ $clients->lastPage() }}
                                </span>

                                @if($clients->hasMorePages())
                                    <a href="{{ $clients->nextPageUrl() }}" class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 transition-colors">
                                        Suivant
                                    </a>
                                @else
                                    <span class="px-3 py-1 bg-gray-200 text-gray-500 rounded-md text-sm">Suivant</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-gray-300">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-600 mb-3">Aucun membre trouvé</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        {{ $search ? 'Aucun membre ne correspond à votre recherche.' : 'Commencez par ajouter vos premiers membres à la galerie.' }}
                    </p>
                    @if(!$search)
                        <a href="{{ route('filament.admin.resources.clients.create') }}" 
                           class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Ajouter un Membre
                        </a>
                    @else
                        <a href="{{ route('galerie.clients') }}" 
                           class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-times mr-2"></i>
                            Effacer la recherche
                        </a>
                    @endif
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="text-center mt-12">
            <p class="text-white/60 text-sm">
                &copy; 2025 Tumaini Letu System. Galerie Membres Professionnelle.
            </p>
        </div>
    </div>

    <!-- Client Details Modal -->
    <div id="clientModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Le contenu sera chargé via JavaScript -->
        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" class="fixed inset-0 modal-overlay hidden items-center justify-center z-[60] p-4">
        <div class="relative max-w-6xl max-h-[90vh]">
            <img id="lightbox-img" src="" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain">
            <button onclick="closeLightbox()"
                    class="absolute top-4 right-4 text-white text-2xl font-bold bg-black/50 hover:bg-black/70 rounded-full w-12 h-12 flex items-center justify-center transition-all duration-200 transform hover:scale-110">
                ×
            </button>
        </div>
    </div>

    <script>
        // Afficher les détails du client
 // Afficher les détails du client
async function showClientDetails(clientId) {
    try {
        const response = await fetch(`/galerie-clients/${clientId}`);
        const client = await response.json();
        
        // Utiliser les URLs fournies par l'API
        const imageUrl = client.image_url;
        const signatureUrl = client.signature_url;
        
        const modalContent = `
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Profil Membre</h2>
                        <p class="text-white/80 text-sm mt-1">#${client.numero_membre}</p>
                    </div>
                    <button 
                        onclick="closeClientModal()"
                        class="text-white hover:text-gray-200 text-2xl font-bold bg-white/20 rounded-full w-10 h-10 flex items-center justify-center hover:bg-white/30 transition-all duration-200"
                    >
                        ×
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Photo Section -->
                    <div class="space-y-6">
                        <!-- Main Photo -->
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Photo du Membre</h3>
                            <div class="relative inline-block">
                                <img 
                                    src="${imageUrl}" 
                                    alt="Photo de ${client.nom} ${client.prenom}"
                                    class="w-80 h-80 object-cover rounded-2xl shadow-lg cursor-pointer border-4 border-white"
                                    onclick="openLightbox('${imageUrl}')"
                                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(client.nom + '+' + client.prenom)}&background=667eea&color=fff&size=320'"
                                >
                                <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 rounded-2xl transition-all duration-300 flex items-center justify-center">
                                    <div class="text-white opacity-0 hover:opacity-100 transition-opacity duration-300">
                                        <i class="fas fa-expand text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Cliquez pour agrandir</p>
                        </div>

                        <!-- Signature -->
                        ${signatureUrl ? `
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Signature</h3>
                            <div class="relative inline-block">
                                <img 
                                    src="${signatureUrl}" 
                                    alt="Signature de ${client.nom} ${client.prenom}"
                                    class="w-80 h-32 object-contain bg-white rounded-xl shadow-lg cursor-pointer border-2 border-gray-200"
                                    onclick="openLightbox('${signatureUrl}')"
                                    onerror="this.onerror=null; this.style.display='none';"
                                >
                                <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 rounded-xl transition-all duration-300 flex items-center justify-center">
                                    <div class="text-white opacity-0 hover:opacity-100 transition-opacity duration-300">
                                        <i class="fas fa-expand text-2xl"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Cliquez pour agrandir</p>
                        </div>
                        ` : `
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Signature</h3>
                            <div class="w-80 h-32 bg-gray-100 rounded-xl flex items-center justify-center border-2 border-dashed border-gray-300">
                                <div class="text-gray-400 text-center">
                                    <i class="fas fa-signature text-2xl mb-2"></i>
                                    <p class="text-sm">Signature non disponible</p>
                                </div>
                            </div>
                        </div>
                        `}
                    </div>

                    <!-- Information Section -->
                    <div class="space-y-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-purple-500"></i>
                                Informations Personnelles
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Nom Complet:</span>
                                    <span class="text-gray-900 font-medium text-right">
                                        ${client.nom}<br>
                                        ${client.postnom || ''}<br>
                                        ${client.prenom}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Numéro Membre:</span>
                                    <span class="text-gray-900 font-mono">#${client.numero_membre}</span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Identifiant National:</span>
                                    <span class="text-gray-900">${client.identifiant_national || 'Non renseigné'}</span>
                                </div>
                                
                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Email:</span>
                                    <span class="text-gray-900">
                                        ${client.email ? `
                                        <a href="mailto:${client.email}" class="text-blue-600 hover:text-blue-700 flex items-center">
                                            <i class="fas fa-envelope mr-2"></i>${client.email}
                                        </a>
                                        ` : `<span class="text-gray-400">Non renseigné</span>`}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Téléphone:</span>
                                    <span class="text-gray-900">
                                        ${client.telephone ? `
                                        <a href="tel:${client.telephone}" class="text-blue-600 hover:text-blue-700 flex items-center">
                                            <i class="fas fa-phone mr-2"></i>${client.telephone}
                                        </a>
                                        ` : `<span class="text-gray-400">Non renseigné</span>`}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Adresse:</span>
                                    <span class="text-gray-900 text-right">
                                        ${client.adresse || 'Non renseigné'}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Date de Naissance:</span>
                                    <span class="text-gray-900">
                                        ${client.date_naissance ? new Date(client.date_naissance).toLocaleDateString('fr-FR') : 'Non renseigné'}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">État Civil:</span>
                                    <span class="text-gray-900 capitalize">${client.etat_civil || 'Non renseigné'}</span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Activités:</span>
                                    <span class="text-gray-900">${client.activites || 'Non renseigné'}</span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                    <span class="font-semibold text-gray-700">Ville:</span>
                                    <span class="text-gray-900">${client.ville || 'Non renseigné'}</span>
                                </div>
                                
                                <div class="flex items-center justify-between py-3">
                                    <span class="font-semibold text-gray-700">Statut:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Actif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 rounded-b-2xl border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        Membre créé le: ${new Date(client.created_at).toLocaleDateString('fr-FR')} à ${new Date(client.created_at).toLocaleTimeString('fr-FR')}
                    </div>
                    <div class="flex space-x-3">
                        <button 
                            onclick="closeClientModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200 flex items-center"
                        >
                            <i class="fas fa-times mr-2"></i>Fermer
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('clientModal').innerHTML = modalContent;
        document.getElementById('clientModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement des détails du membre');
    }
}

        function closeClientModal() {
            document.getElementById('clientModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openLightbox(src) {
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            lightbox.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeClientModal();
                closeLightbox();
            }
        });

        // Close modals when clicking on background
        document.getElementById('clientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeClientModal();
            }
        });

        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
    </script>

    {{-- <!-- DEBUG TEMPORAIRE - À enlever après -->
<div class="max-w-7xl mx-auto mb-8 bg-yellow-100 border border-yellow-400 rounded-lg p-4">
    <h3 class="font-bold text-yellow-800">Debug Images</h3>
    @foreach($clients as $client)
        @if($client->image)
            <div class="text-sm text-yellow-700">
                <strong>{{ $client->nom }} {{ $client->prenom }}:</strong>
                {{ $client->image }} 
                - <a href="{{ asset('storage/' . $client->image) }}" target="_blank" class="underline">Tester le lien</a>
            </div>
        @endif
    @endforeach
</div> --}}
</body>
</html>