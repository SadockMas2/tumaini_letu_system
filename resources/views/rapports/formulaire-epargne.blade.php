<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtre Rapport Épargne - Tumaini Letu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .note {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .info-box {
            background-color: #e8f4fc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📊 Rapport des Comptes Épargne</h2>
        
        <div class="info-box">
            <strong>ℹ️ Instructions :</strong><br>
            Sélectionnez une période pour filtrer les dépôts et retraits.<br>
            Laissez vide pour voir toutes les transactions depuis la création.
        </div>
        
        <form action="{{ route('rapport.epargne') }}" method="GET">
            <div class="form-group">
                <label for="date_debut">Date de début :</label>
                <input type="date" id="date_debut" name="date_debut" 
                       value="{{ request('date_debut') }}">
                <div class="note">Date à partir de laquelle les transactions seront incluses</div>
            </div>
            
            <div class="form-group">
                <label for="date_fin">Date de fin :</label>
                <input type="date" id="date_fin" name="date_fin" 
                       value="{{ request('date_fin') }}">
                <div class="note">Date jusqu'à laquelle les transactions seront incluses</div>
            </div>
            
            <button type="submit" class="btn">
                📋 Générer le Rapport
            </button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="{{ route('rapport.epargne') }}" style="color: #3498db; text-decoration: none;">
                    🔄 Voir toutes les transactions (sans filtre)
                </a>
            </div>
        </form>
    </div>
    
    <script>
        // Définir la date d'aujourd'hui comme date maximale
        document.getElementById('date_fin').max = new Date().toISOString().split('T')[0];
        document.getElementById('date_debut').max = new Date().toISOString().split('T')[0];
        
        // Validation : date de début ne peut pas être après date de fin
        document.querySelector('form').addEventListener('submit', function(e) {
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;
            
            if (dateDebut && dateFin && dateDebut > dateFin) {
                alert('La date de début ne peut pas être après la date de fin.');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>