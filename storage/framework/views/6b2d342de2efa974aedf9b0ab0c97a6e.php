<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Crédits Microfinance - Tumaini Letu</title>
    <style>
        /* Reset et base */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #000;
            font-size: 10px;
            line-height: 1.2;
            background: white;
        }

        /* En-tête Tumaini Letu */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        .header img {
            height: 60px;
            max-width: 120px;
            object-fit: contain;
        }
        .header-info {
            text-align: right;
            font-size: 9px;
            flex: 1;
            margin-left: 10px;
        }
        .institution-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 2px;
        }

        /* Séparateurs */
        .separator {
            border-top: 2px solid #000;
            margin: 8px 0;
        }

        /* Informations référence et date */
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 9px;
        }

        /* Titre du rapport */
        .report-title {
            text-align: center;
            margin-bottom: 15px;
        }
        .report-title h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title .subtitle {
            font-size: 12px;
            color: #666;
        }

        /* Synthèse générale */
        .summary-section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 12px;
        }

        /* Grille de totaux */
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        .total-card {
            padding: 5px;
            border: 1px solid #000;
            border-radius: 3px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .total-label {
            font-size: 8px;
            color: #000;
            margin-bottom: 2px;
        }
        .total-value {
            font-size: 10px;
            font-weight: bold;
            color: #000;
        }

        /* Table principale */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8px;
            page-break-inside: avoid;
        }
        .main-table th {
            background-color: #e8e8e8;
            padding: 4px 3px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
            vertical-align: top;
        }
        .main-table td {
            padding: 3px 2px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .main-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Lignes de total */
        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        /* Classes utilitaires */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .montant { font-family: 'Courier New', monospace; }
        .nowrap { white-space: nowrap; }

        /* Couleurs pour statuts */
        .statut-termine { color: #28a745; font-weight: bold; }
        .statut-en-cours { color: #007bff; font-weight: bold; }
        .statut-en-retard { color: #dc3545; font-weight: bold; }

        /* Pied de page */
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 8px;
        }

        /* Signatures */
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 4px;
            width: 150px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="logo">
            <img src="<?php echo e($logo_base64); ?>" alt="TUMAINI LETU asbl">
        </div>
        <div class="header-info">
            <div class="institution-name">Tumaini Letu asbl</div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumainiletu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Informations du rapport -->
    <div class="ref-date">
        <div>N/REF : CREDIT-RAPP-<?php echo e(now()->format('Ymd-His')); ?></div>
        <div>Période : <?php echo e($periode); ?></div>
        <div>Généré le : <?php echo e($date_generation); ?></div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="report-title">
        <h1>RAPPORT DES CRÉDITS MICROFINANCE</h1>
        <div class="subtitle">Synthèse détaillée des crédits actifs - Portefeuille crédits</div>
    </div>

    <!-- Synthèse générale -->
    <div class="summary-section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL CRÉDITS</div>
                <div class="total-value"><?php echo e(number_format($totaux['total_credits'], 0)); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">CAPITAL ACCORDÉ</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_montant_accorde'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">MONTANT TOTAL</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_montant_total'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">INTÉRÊTS ATTENDUS</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_interets_attendus'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TOTAL PAYÉ</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_paiements'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">MONTANT RESTANT</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_montant_restant'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">CAPITAL REMBOURSÉ</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_capital_rembourse'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">INTÉRÊTS PAYÉS</div>
                <div class="total-value montant"><?php echo e(number_format($totaux['total_interets_payes'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TAUX REMBOURSEMENT</div>
                <div class="total-value"><?php echo e(number_format($totaux['taux_remboursement_global'], 2)); ?> %</div>
            </div>
            <div class="total-card">
                <div class="total-label">CRÉDITS INDIVIDUELS</div>
                <div class="total-value"><?php echo e(number_format($totaux['credits_individuels'], 0)); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">CRÉDITS GROUPE</div>
                <div class="total-value"><?php echo e(number_format($totaux['credits_groupe'], 0)); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">EN COURS</div>
                <div class="total-value"><?php echo e(number_format($totaux['credits_en_cours'], 0)); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">TERMINÉS</div>
                <div class="total-value"><?php echo e(number_format($totaux['credits_termines'], 0)); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">EN RETARD</div>
                <div class="total-value statut-en-retard"><?php echo e(number_format($totaux['credits_en_retard'], 0)); ?></div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Table détaillée des crédits -->
    <div class="section-title">DÉTAIL DES CRÉDITS (<?php echo e(count($credits)); ?> crédits)</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%">N°</th>
                <th style="width: 8%">Compte</th>
                <th style="width: 12%">Client/Groupe</th>
                <th style="width: 5%">Type</th>
                <th style="width: 8%">Agent</th>
                <th style="width: 10%">Date Octroi</th>
                <th style="width: 10%">Date Échéance</th>
                <th style="width: 8%" class="text-right">Capital Accordé</th>
                <th style="width: 8%" class="text-right">Montant Total</th>
                <th style="width: 8%" class="text-right">Intérêts Attendus</th>
                <th style="width: 8%" class="text-right">Capital Remboursé</th>
                <th style="width: 8%" class="text-right">Intérêts Payés</th>
                <th style="width: 8%" class="text-right">Total Payé</th>
                <th style="width: 8%" class="text-right">Montant Restant</th>
                <th style="width: 6%" class="text-center">Statut</th>
                <th style="width: 6%" class="text-center">Taux %</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $credits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $credit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td class="text-center"><?php echo e($index + 1); ?></td>
                <td><?php echo e($credit['numero_compte']); ?></td>
                <td><?php echo e($credit['nom_complet']); ?></td>
                <td class="text-center"><?php echo e($credit['type_credit']); ?></td>
                <td><?php echo e(\Illuminate\Support\Str::limit($credit['agent'], 12)); ?></td>
                <td class="text-center"><?php echo e($credit['date_octroi']); ?></td>
                <td class="text-center"><?php echo e($credit['date_echeance']); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['montant_accorde'], 2)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['montant_total'], 2)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['interets_attendus'], 2)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['capital_deja_rembourse'], 2)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['interets_deja_payes'], 2)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['total_paiements'], 2)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($credit['montant_restant'], 2)); ?></td>
                <td class="text-center 
                    <?php if($credit['statut'] === 'Terminé'): ?> statut-termine
                    <?php elseif($credit['statut'] === 'En retard'): ?> statut-en-retard
                    <?php else: ?> statut-en-cours
                    <?php endif; ?>">
                    <?php echo e($credit['statut']); ?>

                </td>
                <td class="text-center"><?php echo e(number_format($credit['taux_remboursement'], 2)); ?>%</td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            
            <!-- Ligne de totaux -->
            <tr class="total-row">
                <td colspan="7"><strong>TOTAUX GÉNÉRAUX</strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_montant_accorde'], 2)); ?></strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_montant_total'], 2)); ?></strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_interets_attendus'], 2)); ?></strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_capital_rembourse'], 2)); ?></strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_interets_payes'], 2)); ?></strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_paiements'], 2)); ?></strong></td>
                <td class="text-right montant"><strong><?php echo e(number_format($totaux['total_montant_restant'], 2)); ?></strong></td>
                <td class="text-center">
                    <strong><?php echo e($totaux['credits_termines']); ?> / <?php echo e($totaux['total_credits']); ?></strong>
                </td>
                <td class="text-center">
                    <strong><?php echo e(number_format($totaux['taux_remboursement_global'], 2)); ?>%</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="separator"></div>

    <!-- Notes et informations complémentaires -->
    <div style="margin-top: 15px; font-size: 9px;">
        <div><strong>Notes :</strong></div>
        <div>1. Tous les montants sont en dollars américains (USD)</div>
        <div>2. La durée standard des crédits est de 16 semaines (4 mois)</div>
        <div>3. Le remboursement est hebdomadaire (16 paiements égaux)</div>
        <div>4. Les intérêts sont calculés sur la base de 22.5% pour les groupes et 30% pour les individuels</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature" style="text-align: left;">
            Le Responsable Microfinance
        </div>
        <div class="signature" style="text-align: center;">
            Le Chef de Service
        </div>
        <div class="signature" style="text-align: right;">
            Le Comptable
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion Microfinance Tumaini Letu</div>
        <div>Document confidentiel - Page 1/1 - <?php echo e(now()->format('d/m/Y H:i:s')); ?></div>
    </div>
</body>
</html><?php /**PATH D:\APP\TUMAINI LETU\tumainiletusystem2.0\resources\views/filament/exports/rapport-credits.blade.php ENDPATH**/ ?>