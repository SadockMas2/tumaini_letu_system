<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relevé de Compte - {{ $compte->numero_compte }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            align-items: flex-start;
        }
        .header img {
            height: 80px;
            max-width: 150px;
            object-fit: contain;
        }
        .header-info {
            text-align: right;
            font-size: 12px;
            flex: 1;
            margin-left: 20px;
        }
        .separator {
            border-top: 2px solid #000;
            margin: 20px 0;
        }
        .title-section {
            text-align: center;
            margin: 20px 0;
        }
        .title-section h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .title-section h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        .info-client {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
        }
        .table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th, .table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
            font-size: 12px;
        }
        .table th { 
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .depot { 
            color: green; 
            font-weight: bold;
        }
        .retrait { 
            color: red; 
            font-weight: bold;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            text-align: center;
        }
        .summary-item {
            padding: 10px;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        .summary-label {
            font-size: 12px;
            color: #666;
        }
        .devise-usd { color: #059669; }
        .devise-cdf { color: #ea580c; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- En-tête identique au bordereau -->
    <div class="header">
        <div class="logo">
            @if(file_exists(public_path('images/logo-tumaini1.png')))
                <img src="{{ asset('images/logo-tumaini1.png') }}" alt="TUMAINI LETU asbl">
            @elseif(file_exists(public_path('images/logo-tumaini1.jpg')))
                <img src="{{ asset('images/logo-tumaini1.jpg') }}" alt="TUMAINI LETU asbl">
            @elseif(file_exists(public_path('images/logo-tumaini1.jpeg')))
                <img src="{{ asset('images/logo-tumaini1.jpeg') }}" alt="TUMAINI LETU asbl">
            @elseif(file_exists(public_path('images/logo-tumaini1.svg')))
                <img src="{{ asset('images/logo-tumaini1.svg') }}" alt="TUMAINI LETU asbl">
            @else
                <div style="height: 80px; width: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc;">
                    Logo non trouvé
                </div>
            @endif
        </div>
        <div class="header-info">
            <div><strong>Tumaini Letu asbl</strong></div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumailetu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Titre du relevé -->
    <div class="title-section">
        <h1>RELEVÉ DE COMPTE</h1>
        <h2>Historique des transactions</h2>
    </div>

    <!-- Informations du client -->
    <div class="info-client">
        <div class="info-item">
            <span class="info-label">Numéro de compte:</span>
            <span>{{ $compte->numero_compte }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Client:</span>
            <span>{{ $compte->nom }} {{ $compte->prenom }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Numéro membre:</span>
            <span>{{ $compte->numero_membre }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Type de compte:</span>
            <span>{{ $compte->type_compte === 'individuel' ? 'Individuel' : 'Groupe Solidaire' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Solde actuel:</span>
            <span class="{{ $compte->devise === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                {{ number_format($compte->solde, 2, ',', ' ') }} {{ $compte->devise }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Devise:</span>
            <span class="{{ $compte->devise === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                {{ $compte->devise }}
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Période du relevé:</span>
            <span>{{ now()->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Agence:</span>
            <span>Goma</span>
        </div>
    </div>

    <!-- Résumé statistique -->
    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value text-green-600">
                    {{ number_format($statsMouvements['total_depots'] ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                </div>
                <div class="summary-label">Total Dépôts</div>
                <div class="summary-count">{{ $statsMouvements['nombre_depots'] ?? 0 }} opérations</div>
            </div>
            <div class="summary-item">
                <div class="summary-value text-red-600">
                    {{ number_format($statsMouvements['total_retraits'] ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                </div>
                <div class="summary-label">Total Retraits</div>
                <div class="summary-count">{{ $statsMouvements['nombre_retraits'] ?? 0 }} opérations</div>
            </div>
            <div class="summary-item">
                <div class="summary-value text-blue-600">
                    {{ number_format(($statsMouvements['total_depots'] ?? 0) - ($statsMouvements['total_retraits'] ?? 0), 2, ',', ' ') }} {{ $compte->devise }}
                </div>
                <div class="summary-label">Solde Net</div>
                <div class="summary-count">Dépôts - Retraits</div>
            </div>
            <div class="summary-item">
                <div class="summary-value text-purple-600">
                    {{ $mouvements->count() }}
                </div>
                <div class="summary-label">Total Opérations</div>
                <div class="summary-count">Toutes transactions</div>
            </div>
        </div>
    </div>

    <!-- Tableau des mouvements -->
    <table class="table">
        <thead>
            <tr>
                <th>Date & Heure</th>
                <th>Type</th>
                <th>Référence</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Solde Avant</th>
                <th>Solde Après</th>
                <th>Opérateur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mouvements as $mouvement)
            <tr>
                <td>{{ $mouvement->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if($mouvement->type === 'depot')
                        <span style="color: green; font-weight: bold;">DÉPÔT</span>
                    @else
                        <span style="color: red; font-weight: bold;">RETRAIT</span>
                    @endif
                </td>
                <td>{{ $mouvement->reference ?? 'N/A' }}</td>
                <td>
                    {{ $mouvement->description ?? 'Transaction' }}
                    @if($mouvement->nom_deposant)
                        <br><small>Par: {{ $mouvement->nom_deposant }}</small>
                    @endif
                </td>
                <td class="{{ $mouvement->type === 'depot' ? 'depot' : 'retrait' }}">
                    {{ $mouvement->type === 'depot' ? '+' : '-' }}
                    {{ number_format($mouvement->montant, 2, ',', ' ') }} {{ $mouvement->devise ?? $compte->devise }}
                </td>
                <td>{{ number_format($mouvement->solde_avant, 2, ',', ' ') }} {{ $mouvement->devise ?? $compte->devise }}</td>
                <td>{{ number_format($mouvement->solde_apres, 2, ',', ' ') }} {{ $mouvement->devise ?? $compte->devise }}</td>
                <td>{{ $mouvement->operateur->name ?? 'Système' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pied de page -->
    <div class="footer">
        <div>Relevé généré le {{ now()->format('d/m/Y à H:i') }}</div>
        <div>TUMAINI LETU asbl - Système de Gestion Financière</div>
        <div>Ce document est généré automatiquement et ne nécessite pas de signature</div>
    </div>

    <!-- Boutons d'action (non imprimés) -->
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
            📄 Imprimer le relevé
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
            ❌ Fermer
        </button>
        <button onclick="exportToPDF()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
            📥 Télécharger PDF
        </button>
    </div>

    <script>
        function exportToPDF() {
            alert('Fonction d\'export PDF à implémenter');
            // Vous pouvez utiliser une librairie comme jsPDF ou faire un appel à une route backend
        }

        // Impression automatique optionnelle
        window.onload = function() {
            // Décommentez la ligne suivante pour l'impression automatique
            // window.print();
        }
    </script>
</body>
</html>