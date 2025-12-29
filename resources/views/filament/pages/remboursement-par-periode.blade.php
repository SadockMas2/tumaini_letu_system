<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remboursement par Période</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            margin-bottom: 30px;
            animation: slideIn 0.5s ease-out;
        }

        .title {
            color: #2d3748;
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-align: center;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .control-group {
            background: #f8fafc;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
        }

        .control-group label {
            display: block;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .control-group select,
        .control-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }

        .control-group select:focus,
        .control-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .period-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .period-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: white;
            color: #4a5568;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .period-btn.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .period-btn:hover:not(.active) {
            background: #edf2f7;
            transform: translateY(-1px);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #718096;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-card .subvalue {
            color: #a0aec0;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: fadeIn 0.8s ease-out;
        }

        .table-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
        }

        .table-header h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
            border-bottom: 3px solid #e2e8f0;
        }

        th {
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f7fafc;
        }

        td {
            padding: 20px 15px;
            color: #2d3748;
        }

        .progress-cell {
            min-width: 150px;
        }

        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
        }

        .capital-progress {
            background: linear-gradient(90deg, #4c51bf, #667eea);
        }

        .interet-progress {
            background: linear-gradient(90deg, #ed8936, #f56565);
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.8rem;
            color: #718096;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: #c6f6d5;
            color: #276749;
        }

        .badge-warning {
            background: #feebc8;
            color: #9c4221;
        }

        .badge-danger {
            background: #fed7d7;
            color: #c53030;
        }

        .export-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .export-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            background: white;
            color: #4a5568;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .export-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .export-btn.pdf {
            background: linear-gradient(45deg, #f56565, #ed8936);
            color: white;
        }

        .export-btn.excel {
            background: linear-gradient(45deg, #48bb78, #38a169);
            color: white;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .controls {
                grid-template-columns: 1fr;
            }
            
            .period-buttons {
                flex-direction: column;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">📊 Tableau de Remboursement par Période</h1>
            <p class="subtitle" style="text-align: center; color: #718096; margin-bottom: 20px;">
                Visualisez la répartition capital/intérêts du remboursement total par période choisie
            </p>
            
            <div class="controls">
                <div class="control-group">
                    <label for="periodType">Type de Période</label>
                    <select id="periodType">
                        <option value="jour">Journalier</option>
                        <option value="semaine" selected>Hebdomadaire</option>
                        <option value="mois">Mensuel</option>
                    </select>
                </div>
                
                <div class="control-group">
                    <label for="dateDebut">Date de Début</label>
                    <input type="date" id="dateDebut" value="{{ date('Y-m-d') }}">
                </div>
                
                <div class="control-group">
                    <label for="dateFin">Date de Fin</label>
                    <input type="date" id="dateFin" value="{{ date('Y-m-d', strtotime('+3 months')) }}">
                </div>
                
                <div class="control-group">
                    <label for="creditType">Type de Crédit</label>
                    <select id="creditType">
                        <option value="all">Tous les crédits</option>
                        <option value="individuel">Individuels</option>
                        <option value="groupe">Groupes</option>
                    </select>
                </div>
            </div>
            
            <div class="period-buttons">
                <button class="period-btn active" data-period="semaine">
                    📅 Hebdomadaire
                </button>
                <button class="period-btn" data-period="jour">
                    📊 Journalier
                </button>
                <button class="period-btn" data-period="mois">
                    📈 Mensuel
                </button>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card">
                <h3>Total à Rembourser</h3>
                <div class="value" id="totalRemboursement">0 USD</div>
                <div class="subvalue">Montant total du remboursement</div>
            </div>
            
            <div class="stat-card">
                <h3>Capital Total</h3>
                <div class="value" id="totalCapital">0 USD</div>
                <div class="subvalue">Part du capital</div>
            </div>
            
            <div class="stat-card">
                <h3>Intérêts Totaux</h3>
                <div class="value" id="totalInterets">0 USD</div>
                <div class="subvalue">Part des intérêts</div>
            </div>
            
            <div class="stat-card">
                <h3>Périodes</h3>
                <div class="value" id="nombrePeriodes">0</div>
                <div class="subvalue">Nombre de périodes</div>
            </div>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>
                    <span id="tableTitle">Détail des Remboursements Hebdomadaires</span>
                    <span class="badge badge-success" id="badgeCount">0 crédits</span>
                </h2>
            </div>
            
            <div class="table-wrapper">
                <table id="remboursementTable">
                    <thead>
                        <tr>
                            <th>Période</th>
                            <th>Date</th>
                            <th>Crédit</th>
                            <th>Type</th>
                            <th>Client/Groupe</th>
                            <th>Montant Total</th>
                            <th>Capital</th>
                            <th>Intérêts</th>
                            <th>Répartition</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Les données seront insérées ici par JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="export-buttons">
            <button class="export-btn pdf" onclick="exporterPDF()">
                📄 Exporter PDF
            </button>
            <button class="export-btn excel" onclick="exporterExcel()">
                📊 Exporter Excel
            </button>
        </div>
    </div>

    <script>
        // Données simulées (à remplacer par vos données réelles)
        const creditsData = [
            {
                id: 1,
                numero_compte: 'C-001',
                type: 'individuel',
                nom: 'Jean Dupont',
                montant_accorde: 1500,
                montant_total: 1875,
                remboursement_hebdo: 117.19,
                date_octroi: '2024-01-15',
                paiements: [
                    { date: '2024-01-29', capital: 93.75, interets: 23.44 },
                    { date: '2024-02-05', capital: 93.75, interets: 23.44 },
                    { date: '2024-02-12', capital: 93.75, interets: 23.44 }
                ]
            },
            {
                id: 2,
                numero_compte: 'G-001',
                type: 'groupe',
                nom: 'Groupe Artisans',
                montant_accorde: 5000,
                montant_total: 6125,
                remboursement_hebdo: 382.81,
                date_octroi: '2024-01-10',
                paiements: [
                    { date: '2024-01-24', capital: 312.5, interets: 70.31 },
                    { date: '2024-01-31', capital: 312.5, interets: 70.31 },
                    { date: '2024-02-07', capital: 312.5, interets: 70.31 }
                ]
            }
            // Ajoutez plus de crédits ici...
        ];

        let currentPeriod = 'semaine';

        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentPeriod = this.dataset.period;
                mettreAJourTitre();
                genererTableau();
            });
        });

        document.getElementById('periodType').addEventListener('change', genererTableau);
        document.getElementById('dateDebut').addEventListener('change', genererTableau);
        document.getElementById('dateFin').addEventListener('change', genererTableau);
        document.getElementById('creditType').addEventListener('change', genererTableau);

        function mettreAJourTitre() {
            const titres = {
                'jour': 'Détail des Remboursements Journaliers',
                'semaine': 'Détail des Remboursements Hebdomadaires',
                'mois': 'Détail des Remboursements Mensuels'
            };
            document.getElementById('tableTitle').textContent = titres[currentPeriod];
        }

        function calculerRemboursementParPeriode(credit, periode) {
            let remboursements = [];
            const dateDebut = new Date(document.getElementById('dateDebut').value);
            const dateFin = new Date(document.getElementById('dateFin').value);
            
            let dateActuelle = new Date(dateDebut);
            let periodeCount = 0;
            
            while (dateActuelle <= dateFin) {
                const paiementsPeriode = credit.paiements.filter(p => {
                    const datePaiement = new Date(p.date);
                    if (periode === 'jour') {
                        return datePaiement.toDateString() === dateActuelle.toDateString();
                    } else if (periode === 'semaine') {
                        const semainePaiement = getWeekNumber(datePaiement);
                        const semaineActuelle = getWeekNumber(dateActuelle);
                        return semainePaiement === semaineActuelle && 
                               datePaiement.getFullYear() === dateActuelle.getFullYear();
                    } else { // mois
                        return datePaiement.getMonth() === dateActuelle.getMonth() &&
                               datePaiement.getFullYear() === dateActuelle.getFullYear();
                    }
                });

                if (paiementsPeriode.length > 0) {
                    const totalCapital = paiementsPeriode.reduce((sum, p) => sum + p.capital, 0);
                    const totalInterets = paiementsPeriode.reduce((sum, p) => sum + p.interets, 0);
                    
                    remboursements.push({
                        date: new Date(dateActuelle),
                        dateStr: formatDate(dateActuelle, periode),
                        capital: totalCapital,
                        interets: totalInterets,
                        total: totalCapital + totalInterets,
                        periodeNum: periodeCount + 1
                    });
                }

                // Incrémenter la date selon la période
                if (periode === 'jour') {
                    dateActuelle.setDate(dateActuelle.getDate() + 1);
                } else if (periode === 'semaine') {
                    dateActuelle.setDate(dateActuelle.getDate() + 7);
                } else {
                    dateActuelle.setMonth(dateActuelle.getMonth() + 1);
                }
                periodeCount++;
            }

            return remboursements;
        }

        function getWeekNumber(date) {
            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        }

        function formatDate(date, periode) {
            if (periode === 'jour') {
                return date.toLocaleDateString('fr-FR', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } else if (periode === 'semaine') {
                const semaine = getWeekNumber(date);
                return `Semaine ${semaine} - ${date.getFullYear()}`;
            } else {
                return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
            }
        }

async function genererTableau() {
    const tableBody = document.getElementById('tableBody');
    const creditType = document.getElementById('creditType').value;
    const periodeType = document.getElementById('periodType').value;
    const dateDebut = document.getElementById('dateDebut').value;
    const dateFin = document.getElementById('dateFin').value;
    
    // Afficher un indicateur de chargement
    tableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px;">Chargement des données...</td></tr>';
    
    try {
        // Récupérer les données depuis l'API
        const response = await fetch(`/api/remboursements-periode?periode=${periodeType}&date_debut=${dateDebut}&date_fin=${dateFin}&type_credit=${creditType}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error('Erreur lors de la récupération des données');
        }
        
        const remboursements = result.data;
        const totaux = result.totaux;
        
        // Vider le tableau
        tableBody.innerHTML = '';
        
        if (remboursements.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px; color: #718096;">Aucun remboursement trouvé pour les critères sélectionnés</td></tr>';
            return;
        }
        
        // Remplir le tableau avec les données réelles
        remboursements.forEach(item => {
            const pourcentageCapital = item.pourcentage_capital;
            const pourcentageInterets = item.pourcentage_interets;
            
            const badgeClass = item.type_credit === 'individuel' ? 'badge-success' : 'badge-warning';
            const statutClass = item.statut === 'Payé' ? 'badge-success' : 
                               item.statut === 'En retard' ? 'badge-danger' : 'badge-warning';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.periode}</td>
                <td>${new Date(item.date_periode).toLocaleDateString('fr-FR')}</td>
                <td>${item.numero_compte}</td>
                <td><span class="badge ${badgeClass}">${item.type_credit}</span></td>
                <td>${item.nom_complet}</td>
                <td><strong>${formatMontant(item.montant_total)} USD</strong></td>
                <td style="color: #4c51bf; font-weight: 600;">${formatMontant(item.capital)} USD</td>
                <td style="color: #ed8936; font-weight: 600;">${formatMontant(item.interets)} USD</td>
                <td class="progress-cell">
                    <div class="progress-bar">
                        <div class="progress-fill capital-progress" style="width: ${pourcentageCapital}%"></div>
                        <div class="progress-fill interet-progress" style="width: ${pourcentageInterets}%; left: ${pourcentageCapital}%"></div>
                    </div>
                    <div class="progress-labels">
                        <span>${pourcentageCapital.toFixed(1)}% Capital</span>
                        <span>${pourcentageInterets.toFixed(1)}% Intérêts</span>
                    </div>
                </td>
                <td>
                    <span class="badge ${statutClass}">${item.statut}</span>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Mettre à jour les statistiques avec les totaux réels
        document.getElementById('totalRemboursement').textContent = formatMontant(totaux.total_remboursement) + ' USD';
        document.getElementById('totalCapital').textContent = formatMontant(totaux.total_capital) + ' USD';
        document.getElementById('totalInterets').textContent = formatMontant(totaux.total_interets) + ' USD';
        document.getElementById('nombrePeriodes').textContent = totaux.nombre_periodes;
        document.getElementById('badgeCount').textContent = totaux.nombre_credits + ' crédits';
        
    } catch (error) {
        console.error('Erreur:', error);
        tableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px; color: #f56565;">Erreur lors du chargement des données</td></tr>';
    }
}
            const tableBody = document.getElementById('tableBody');
            const creditType = document.getElementById('creditType').value;
            
            // Filtrer les crédits
            let creditsFiltres = creditsData;
            if (creditType !== 'all') {
                creditsFiltres = creditsData.filter(c => c.type === creditType);
            }

            let totalRemboursement = 0;
            let totalCapital = 0;
            let totalInterets = 0;
            let periodeSet = new Set();
            
            tableBody.innerHTML = '';

            creditsFiltres.forEach(credit => {
                const remboursements = calculerRemboursementParPeriode(credit, currentPeriod);
                
                remboursements.forEach(remb => {
                    const pourcentageCapital = (remb.capital / remb.total * 100).toFixed(1);
                    const pourcentageInterets = (remb.interets / remb.total * 100).toFixed(1);
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>Période ${remb.periodeNum}</td>
                        <td>${remb.dateStr}</td>
                        <td>${credit.numero_compte}</td>
                        <td><span class="badge ${credit.type === 'individuel' ? 'badge-success' : 'badge-warning'}">${credit.type}</span></td>
                        <td>${credit.nom}</td>
                        <td><strong>${formatMontant(remb.total)} USD</strong></td>
                        <td style="color: #4c51bf; font-weight: 600;">${formatMontant(remb.capital)} USD</td>
                        <td style="color: #ed8936; font-weight: 600;">${formatMontant(remb.interets)} USD</td>
                        <td class="progress-cell">
                            <div class="progress-bar">
                                <div class="progress-fill capital-progress" style="width: ${pourcentageCapital}%"></div>
                                <div class="progress-fill interet-progress" style="width: ${pourcentageInterets}%; left: ${pourcentageCapital}%"></div>
                            </div>
                            <div class="progress-labels">
                                <span>${pourcentageCapital}% Capital</span>
                                <span>${pourcentageInterets}% Intérêts</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-success">À venir</span>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                    
                    totalRemboursement += remb.total;
                    totalCapital += remb.capital;
                    totalInterets += remb.interets;
                    periodeSet.add(remb.dateStr);
                });
            });

            // Mettre à jour les statistiques
            document.getElementById('totalRemboursement').textContent = formatMontant(totalRemboursement) + ' USD';
            document.getElementById('totalCapital').textContent = formatMontant(totalCapital) + ' USD';
            document.getElementById('totalInterets').textContent = formatMontant(totalInterets) + ' USD';
            document.getElementById('nombrePeriodes').textContent = periodeSet.size;
            document.getElementById('badgeCount').textContent = creditsFiltres.length + ' crédits';
        }

        function formatMontant(montant) {
            return new Intl.NumberFormat('fr-FR', { 
                minimumFractionDigits: 2,
                maximumFractionDigits: 2 
            }).format(montant);
        }

        function exporterPDF() {
            alert('Fonctionnalité PDF en développement...');
            // Implémentez l'export PDF ici
        }

        function exporterExcel() {
            alert('Fonctionnalité Excel en développement...');
            // Implémentez l'export Excel ici
        }

        // Initialiser le tableau au chargement
        document.addEventListener('DOMContentLoaded', function() {
            genererTableau();
        });
    </script>
</body>
</html>