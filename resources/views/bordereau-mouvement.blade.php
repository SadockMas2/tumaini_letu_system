<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bordereau - {{ $mouvement->type }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
            transform-origin: top left;
            transition: transform 0.3s ease;
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
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .content {
            margin-bottom: 20px;
        }
        .content-line {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        .content-line strong {
            width: 200px;
        }
        .signature {
            margin-top: 100px;
            text-align: right;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 200px;
            margin-left: auto;
        }

          .signature1 {
            margin-top: -8px;
            text-align: left;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 200px;
            margin-right: auto;
        }
        .montant-section {
            background-color: #f5f5f5;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        /* Contrôles de zoom */
        .zoom-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .zoom-controls button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }
        .zoom-controls button:hover {
            background: #0056b3;
        }
        .zoom-controls span {
            margin: 0 10px;
            font-weight: bold;
        }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .zoom-controls { display: none; }
        }
        
        /* Classes pour les différents niveaux de zoom */
        .zoom-80 { transform: scale(0.8); }
        .zoom-90 { transform: scale(0.9); }
        .zoom-100 { transform: scale(1); }
        .zoom-110 { transform: scale(1.1); }
        .zoom-120 { transform: scale(1.2); }
    </style>
</head>
<body class="zoom-100">
    <!-- Contrôles de zoom -->
    {{-- <div class="zoom-controls no-print">
        <button onclick="zoomOut()">-</button>
        <span id="zoom-level">100%</span>
        <button onclick="zoomIn()">+</button>
        <button onclick="resetZoom()" style="margin-left: 10px;">Reset</button>
    </div> --}}

    <div class ="header">
        <div class style="height: 80px; ="logo">
            <!-- Correction du chemin du logo -->
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
            <div> Tel : +243982618321</div>
            <div>Email : tumailetu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <div class="ref-date">
        <div>N/REF : {{ $mouvement->numero_reference ?? str_pad($mouvement->id, 7, '0', STR_PAD_LEFT) }}</div>
        <div>Date : {{ $mouvement->created_at->format('d/m/Y') }}</div>
        <div>Opérateur : {{ $mouvement->operateur_abrege ?? 'N/A' }}</div>
    </div>

    <div class="separator"></div>

    <div class="content">
        <div class="content-line">
            <strong>Type de mouvement :</strong>
            <span>{{ ucfirst($mouvement->type) }}</span>
        </div>
        
        <div class="montant-section">
            <div class="content-line">
                <strong>{{ $mouvement->type === 'depot' ? 'Entrée' : 'Sortie' }} :</strong>
                <span>{{ number_format($mouvement->montant, 2, ',', ' ') }} USD</span>
            </div>
        </div>

        <div class="content-line">
            <strong>Numéro du compte :</strong>
            <span>{{ $mouvement->numero_compte }}</span>
        </div>
        
        <div class="content-line">
            <strong>Agence :</strong>
            <span>Goma</span>
        </div>
        
        <div class="content-line">
            <strong>Intitulé du compte :</strong>
            <span>{{ $mouvement->client_nom }}</span>
        </div>
        
        <div class="content-line">
            <strong>Solde après opération :</strong>
            <span>{{ number_format($mouvement->solde_apres, 2, ',', ' ') }} USD</span>
        </div>
    </div>

    <div class="separator"></div>

    <div class="content">
        <div class="content-line">
            <strong>Libellé :</strong>
            <span>Bordereau d'{{ $mouvement->type === 'depot' ? 'entrée' : 'sortie' }} / {{ $mouvement->client_nom }}</span>
        </div>
        
        <div class="content-line">
            <strong>ID du Membre :</strong>
            <span>{{ $mouvement->compte->id_client ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="separator"></div>

    <div class="content">
        <div class="content-line">
            <strong>Nom du {{ $mouvement->type === 'depot' ? 'déposant' : 'retirant' }} :</strong>
            <span>{{ $mouvement->nom_deposant }}</span>
        </div>
    </div>

    <div class="signature">
        Signature de l'agent
    </div>

    <div class="signature1">
        Signature du déposant
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
            Imprimer le bordereau
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">
            Fermer
        </button>
    </div>

    <script>
        // let currentZoom = 100;
        
        // function zoomIn() {
        //     if (currentZoom < 120) {
        //         currentZoom += 10;
        //         updateZoom();
        //     }
        // }
        
        // function zoomOut() {
        //     if (currentZoom > 80) {
        //         currentZoom -= 10;
        //         updateZoom();
        //     }
        // }
        
        // // function resetZoom() {
        // //     currentZoom = 100;
        // //     updateZoom();
        // // }
        
        // function updateZoom() {
        //     document.body.className = 'zoom-' + currentZoom;
        //     document.getElementById('zoom-level').textContent = currentZoom + '%';
        // }
        
        // Impression automatique au chargement
        window.onload = function() {
            // Optionnel: impression automatique
            // window.print();
        }
    </script>
</body>
</html>