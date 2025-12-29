<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Remboursement par Période - Tumaini Letu</title>
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
            font-size: 12px;
            line-height: 1.3;
            background: white;
        }

        /* En-tête Tumaini Letu */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        .header img {
            height: 70px;
            max-width: 140px;
            object-fit: contain;
        }
        .header-info {
            text-align: right;
            font-size: 11px;
            flex: 1;
            margin-left: 15px;
        }
        .institution-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        /* Séparateurs */
        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }

        /* Informations référence et date */
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 11px;
        }

        /* Titre principal */
        .main-title {
            text-align: center;
            margin: 20px 0;
        }
        .main-title h1 {
            font-size: 18px;
            color: #000;
            margin-bottom: 5px;
        }
        .main-title .subtitle {
            font-size: 13px;
            color: #666;
        }

        /* Grille de totaux */
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .total-card {
            padding: 10px;
            border: 1px solid #000;
            border-radius: 3px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .total-label {
            font-size: 9px;
            color: #000;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .total-value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .table th {
            background-color: #f5f5f5;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
        }
        .table td {
            padding: 6px 5px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Barre de progression */
        .progress-container {
            width: 100px;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin: 0 5px;
        }
        .progress-bar {
            height: 100%;
            float: left;
        }
        .progress-capital {
            background-color: #4c51bf;
        }
        .progress-interets {
            background-color: #ed8936;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #c6f6d5;
            color: #276749;
        }
        .badge-warning {
            background-color: #feebc8;
            color: #9c4221;
        }
        .badge-danger {
            background-color: #fed7d7;
            color: #c53030;
        }
        .badge-info {
            background-color: #bee3f8;
            color: #2c5282;
        }

        /* Résumé */
        .summary {
            background-color: #f0f4f8;
            border: 1px solid #cbd5e0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .summary-label {
            font-weight: bold;
            color: #4a5568;
        }
        .summary-value {
            font-weight: bold;
            color: #2d3748;
        }

        /* Pied de page */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 9px;
        }

        /* Utilitaires */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .montant { font-family: 'Courier New', monospace; }
        .nowrap { white-space: nowrap; }
        .agent-name {
            font-size: 9px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- En-tête Tumaini Letu -->
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
        <div>N/REF : REMB-<?php echo e(strtoupper($periode)); ?>-<?php echo e(\Carbon\Carbon::now()->format('Ymd-His')); ?></div>
        <div>Période : <?php echo e($date_debut); ?> au <?php echo e($date_fin); ?></div>
        <div>Généré le : <?php echo e($date_rapport); ?></div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="main-title">
        <h1>RAPPORT DE REMBOURSEMENT PAR PÉRIODE <?php echo e(strtoupper($titre_periode)); ?></h1>
        <div class="subtitle">
            <?php echo e($type_credit); ?> | <?php echo e(count($remboursements)); ?> remboursements planifiés
        </div>
    </div>

    <!-- Synthèse des totaux -->
    <div class="totals-grid">
        <div class="total-card">
            <div class="total-label">Montant Total</div>
            <div class="total-value montant"><?php echo e(number_format($totaux['total_remboursement'], 2)); ?> $</div>
        </div>
        <div class="total-card">
            <div class="total-label">Capital Total</div>
            <div class="total-value montant"><?php echo e(number_format($totaux['total_capital'], 2)); ?> $</div>
        </div>
        <div class="total-card">
            <div class="total-label">Intérêts Totaux</div>
            <div class="total-value montant"><?php echo e(number_format($totaux['total_interets'], 2)); ?> $</div>
        </div>
        <div class="total-card">
            <div class="total-label">Nombre de Périodes</div>
            <div class="total-value"><?php echo e($totaux['nombre_periodes']); ?></div>
        </div>
        <div class="total-card">
            <div class="total-label">Crédits Concernés</div>
            <div class="total-value"><?php echo e($totaux['nombre_credits']); ?></div>
        </div>
        <div class="total-card">
            <div class="total-label">% Capital Moyen</div>
            <div class="total-value"><?php echo e(number_format($totaux['moyenne_capital'], 1)); ?>%</div>
        </div>
        <div class="total-card">
            <div class="total-label">% Intérêts Moyen</div>
            <div class="total-value"><?php echo e(number_format($totaux['moyenne_interets'], 1)); ?>%</div>
        </div>
        <div class="total-card">
            <div class="total-label">Période Type</div>
            <div class="total-value"><?php echo e(ucfirst($titre_periode)); ?></div>
        </div>
    </div>

    <!-- Détail des remboursements -->
    <div style="margin: 20px 0;">
        <h3 style="font-size: 14px; margin-bottom: 10px; color: #2d3748;">
            Détail des Remboursements par Période
        </h3>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 10%">Période</th>
                <th style="width: 10%">Date</th>
                <th style="width: 10%">Compte</th>
                <th style="width: 10%">Type</th>
                <th style="width: 20%">Client/Groupe</th>
                <th style="width: 12%" class="text-right">Montant Total</th>
                <th style="width: 10%" class="text-right">Capital</th>
                <th style="width: 10%" class="text-right">Intérêts</th>
                <th style="width: 10%">Agent</th>
                <th style="width: 8%">Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $remboursements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($item['periode']); ?></td>
                <td class="nowrap"><?php echo e($item['date_periode']->format('d/m/Y')); ?></td>
                <td><?php echo e($item['numero_compte']); ?></td>
                <td>
                    <?php if($item['type_credit'] === 'individuel'): ?>
                        <span class="badge badge-success">IND</span>
                    <?php else: ?>
                        <span class="badge badge-warning">GRP</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 9px;"><?php echo e(Str::limit($item['nom_complet'], 25)); ?></td>
                <td class="text-right montant"><?php echo e(number_format($item['montant_total'], 2)); ?> $</td>
                <td class="text-right montant"><?php echo e(number_format($item['capital'], 2)); ?> $</td>
                <td class="text-right montant"><?php echo e(number_format($item['interets'], 2)); ?> $</td>
                <td>
                    <?php if(isset($item['agent_nom']) && $item['agent_nom']): ?>
                        <span class="agent-name"><?php echo e(Str::limit($item['agent_nom'], 20)); ?></span>
                    <?php elseif(isset($item['agent_id'])): ?>
                        <span class="badge badge-info">Agent #<?php echo e($item['agent_id']); ?></span>
                    <?php else: ?>
                        <span style="font-size: 8px; color: #999;">N/A</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if($item['statut'] === 'Payé'): ?>
                        <span class="badge badge-success">Payé</span>
                    <?php elseif($item['statut'] === 'En retard'): ?>
                        <span class="badge badge-danger">Retard</span>
                    <?php elseif($item['statut'] === 'En cours'): ?>
                        <span class="badge badge-warning">En cours</span>
                    <?php else: ?>
                        <span class="badge badge-info">À venir</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <!-- Résumé final -->
    <div class="summary">
        <div class="summary-item">
            <span class="summary-label">Total du rapport :</span>
            <span class="summary-value montant"><?php echo e(number_format($totaux['total_remboursement'], 2)); ?> USD</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Dont capital :</span>
            <span class="summary-value montant"><?php echo e(number_format($totaux['total_capital'], 2)); ?> USD</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Dont intérêts :</span>
            <span class="summary-value montant"><?php echo e(number_format($totaux['total_interets'], 2)); ?> USD</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Nombre de crédits :</span>
            <span class="summary-value"><?php echo e($totaux['nombre_credits']); ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Nombre de périodes :</span>
            <span class="summary-value"><?php echo e($totaux['nombre_periodes']); ?></span>
        </div>
    </div>

    <!-- Signatures -->
    <div style="display: flex; justify-content: space-between; margin-top: 40px;">
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <div style="font-size: 10px;">Le Caissier</div>
        </div>
        <div style="text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            <div style="font-size: 10px;">Le Comptable</div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion de Crédits Tumaini Letu</div>
        <div>Document confidentiel - <?php echo e(\Carbon\Carbon::now()->format('d/m/Y H:i:s')); ?></div>
    </div>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/filament/exports/rapport-remboursement-periode.blade.php ENDPATH**/ ?>