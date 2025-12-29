<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Comptes Épargne - Tumaini Letu</title>
    <style>
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

        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }

        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 11px;
        }

        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 13px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .table th {
            background-color: #f5f5f5;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
        }
        .table td {
            padding: 4px 3px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        .total-card {
            padding: 6px;
            border: 1px solid #000;
            border-radius: 3px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .total-label {
            font-size: 9px;
            color: #000;
            margin-bottom: 2px;
        }
        .total-value {
            font-size: 11px;
            font-weight: bold;
            color: #000;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 4px;
            width: 180px;
            font-size: 10px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 9px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .montant { font-family: 'Courier New', monospace; }
        
        /* Couleurs spécifiques */
        .devise-usd { 
            color: #28a745; 
            font-weight: bold; 
        }
        .devise-cdf { 
            color: #007bff; 
            font-weight: bold; 
        }
        .type-individuel {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .type-groupe {
            background-color: #e8f5e9;
            color: #388e3c;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .statut-actif { 
            color: #28a745; 
            font-weight: bold; 
        }
        .statut-inactif { 
            color: #dc3545; 
        }
        .depot-positive { color: #28a745; }
        .retrait-negative { color: #dc3545; }
        .solde-positif { font-weight: bold; }
    </style>
</head>
<body>
    <!-- En-tête Tumaini Letu -->
    <div class="header">
        <div class="logo">
            <img src="<?php echo e($rapport['logo_base64']); ?>" alt="TUMAINI LETU asbl">
        </div>
        <div class="header-info">
            <div class="institution-name">Tumaini Letu asbl </div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumainiletu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Informations du rapport -->
    <div class="ref-date">
        <div>N/REF : RAPP-EPARGNE-<?php echo e(\Carbon\Carbon::now()->format('Ymd-His')); ?></div>
        <div>Date : <?php echo e($rapport['date_rapport']); ?></div>
        <div>Heure : <?php echo e($rapport['heure_generation']); ?></div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT DES COMPTES ÉPARGNE</h2>
            <p style="font-size: 12px; color: #000;">État instantané des comptes d'épargne - Totaux depuis création</p>
        </div>
    </div>

    <!-- Synthèse générale -->
    <div class="section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL COMPTES</div>
                <div class="total-value"><?php echo e($rapport['nombre_total_comptes']); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">COMPTES ACTIFS</div>
                <div class="total-value">
                    <?php echo e(($rapport['totaux']['usd']['comptes_actifs'] + $rapport['totaux']['cdf']['comptes_actifs'])); ?>

                </div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE TOTAL USD</div>
                <div class="total-value devise-usd montant"><?php echo e(number_format($rapport['totaux']['usd']['solde_total'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE TOTAL CDF</div>
                <div class="total-value devise-cdf montant"><?php echo e(number_format($rapport['totaux']['cdf']['solde_total'], 2)); ?> FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">COMPTES USD</div>
                <div class="total-value"><?php echo e($rapport['totaux']['usd']['nombre_comptes']); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">COMPTES CDF</div>
                <div class="total-value"><?php echo e($rapport['totaux']['cdf']['nombre_comptes']); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">DÉPÔTS TOT. USD</div>
                <div class="total-value devise-usd montant depot-positive"><?php echo e(number_format($rapport['totaux']['usd']['depots_total'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">DÉPÔTS TOT. CDF</div>
                <div class="total-value devise-cdf montant depot-positive"><?php echo e(number_format($rapport['totaux']['cdf']['depots_total'], 2)); ?> FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">RETRAITS TOT. USD</div>
                <div class="total-value devise-usd montant retrait-negative"><?php echo e(number_format($rapport['totaux']['usd']['retraits_total'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">RETRAITS TOT. CDF</div>
                <div class="total-value devise-cdf montant retrait-negative"><?php echo e(number_format($rapport['totaux']['cdf']['retraits_total'], 2)); ?> FC</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Liste détaillée des comptes épargne -->
    <div class="section">
        <div class="section-title">DÉTAIL DES COMPTES ÉPARGNE (<?php echo e($rapport['nombre_total_comptes']); ?>)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%">N° Compte</th>
                    <th style="width: 20%">Titulaire</th>
                    <th style="width: 10%">Type</th>
                    <th style="width: 8%">Devise</th>
                    <th style="width: 12%" class="text-right">Solde Actuel</th>
                    <th style="width: 12%" class="text-right">Dépôts Total</th>
                    <th style="width: 12%" class="text-right">Retraits Total</th>
                    <th style="width: 8%" class="text-center">Statut</th>
                    <th style="width: 16%">Date Ouverture</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $rapport['comptes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $compte): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $deviseClass = $compte->devise === 'USD' ? 'devise-usd' : 'devise-cdf';
                ?>
                <tr>
                    <td><strong><?php echo e($compte->numero_compte); ?></strong></td>
                    <td>
                        <?php if($compte->type_compte === 'individuel' && $compte->client): ?>
                             <?php echo e($compte->client->nom); ?>  <?php echo e($compte->client->postnom); ?> <?php echo e($compte->client->prenom); ?>

                        <?php elseif($compte->type_compte === 'groupe_solidaire' && $compte->groupeSolidaire): ?>
                            <?php echo e($compte->groupeSolidaire->nom_groupe); ?>

                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if($compte->type_compte === 'individuel'): ?>
                            <span class="type-individuel">INDIVIDUEL</span>
                        <?php else: ?>
                            <span class="type-groupe">GROUPE</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center <?php echo e($deviseClass); ?>">
                        <?php echo e($compte->devise); ?>

                    </td>
                    <td class="text-right montant <?php echo e($deviseClass); ?> <?php echo e($compte->solde > 0 ? 'solde-positif' : ''); ?>">
                        <?php echo e(number_format($compte->solde, 2)); ?>

                    </td>
                    <td class="text-right montant depot-positive <?php echo e($deviseClass); ?>">
                        <?php echo e(number_format($compte->depots_total, 2)); ?>

                    </td>
                    <td class="text-right montant retrait-negative <?php echo e($deviseClass); ?>">
                        <?php echo e(number_format($compte->retraits_total, 2)); ?>

                    </td>
                    <td class="text-center">
                        <?php if($compte->statut === 'actif'): ?>
                            <span class="statut-actif">ACTIF</span>
                        <?php else: ?>
                            <span class="statut-inactif">INACTIF</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php echo e($compte->date_ouverture ? $compte->date_ouverture->format('d/m/Y') : 'N/A'); ?>

                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4"><strong>TOTAUX GÉNÉRAUX</strong></td>
                    <td class="text-right montant">
                        <div class="devise-usd"><?php echo e(number_format($rapport['totaux']['usd']['solde_total'], 2)); ?> $</div>
                        <div class="devise-cdf"><?php echo e(number_format($rapport['totaux']['cdf']['solde_total'], 2)); ?> FC</div>
                    </td>
                    <td class="text-right montant">
                        <div class="devise-usd"><?php echo e(number_format($rapport['totaux']['usd']['depots_total'], 2)); ?> $</div>
                        <div class="devise-cdf"><?php echo e(number_format($rapport['totaux']['cdf']['depots_total'], 2)); ?> FC</div>
                    </td>
                    <td class="text-right montant">
                        <div class="devise-usd"><?php echo e(number_format($rapport['totaux']['usd']['retraits_total'], 2)); ?> $</div>
                        <div class="devise-cdf"><?php echo e(number_format($rapport['totaux']['cdf']['retraits_total'], 2)); ?> FC</div>
                    </td>
                    <td class="text-center">
                        <div>USD: <?php echo e($rapport['totaux']['usd']['comptes_actifs']); ?>/<?php echo e($rapport['totaux']['usd']['nombre_comptes']); ?></div>
                        <div>CDF: <?php echo e($rapport['totaux']['cdf']['comptes_actifs']); ?>/<?php echo e($rapport['totaux']['cdf']['nombre_comptes']); ?></div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Résumé par devise -->
    <div class="section">
        <div class="section-title">RÉSUMÉ PAR DEVISE</div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <!-- Section USD -->
            <div style="border: 1px solid #000; padding: 10px; border-radius: 5px;">
                <h4 style="text-align: center; margin-bottom: 10px; color: #28a745;">DEVISE USD</h4>
                <div style="font-size: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Nombre de comptes:</span>
                        <strong><?php echo e($rapport['totaux']['usd']['nombre_comptes']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Comptes actifs:</span>
                        <strong><?php echo e($rapport['totaux']['usd']['comptes_actifs']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Solde total:</span>
                        <strong class="montant devise-usd"><?php echo e(number_format($rapport['totaux']['usd']['solde_total'], 2)); ?> $</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Dépôts totaux:</span>
                        <strong class="montant devise-usd depot-positive"><?php echo e(number_format($rapport['totaux']['usd']['depots_total'], 2)); ?> $</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Retraits totaux:</span>
                        <strong class="montant devise-usd retrait-negative"><?php echo e(number_format($rapport['totaux']['usd']['retraits_total'], 2)); ?> $</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; padding-top: 4px; border-top: 1px solid #ddd;">
                        <span>Mouvement net:</span>
                        <strong class="montant devise-usd <?php echo e(($rapport['totaux']['usd']['depots_total'] - $rapport['totaux']['usd']['retraits_total']) >= 0 ? 'depot-positive' : 'retrait-negative'); ?>">
                            <?php echo e(number_format($rapport['totaux']['usd']['depots_total'] - $rapport['totaux']['usd']['retraits_total'], 2)); ?> $
                        </strong>
                    </div>
                </div>
            </div>
            
            <!-- Section CDF -->
            <div style="border: 1px solid #000; padding: 10px; border-radius: 5px;">
                <h4 style="text-align: center; margin-bottom: 10px; color: #007bff;">DEVISE CDF</h4>
                <div style="font-size: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Nombre de comptes:</span>
                        <strong><?php echo e($rapport['totaux']['cdf']['nombre_comptes']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Comptes actifs:</span>
                        <strong><?php echo e($rapport['totaux']['cdf']['comptes_actifs']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Solde total:</span>
                        <strong class="montant devise-cdf"><?php echo e(number_format($rapport['totaux']['cdf']['solde_total'], 2)); ?> FC</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Dépôts totaux:</span>
                        <strong class="montant devise-cdf depot-positive"><?php echo e(number_format($rapport['totaux']['cdf']['depots_total'], 2)); ?> FC</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Retraits totaux:</span>
                        <strong class="montant devise-cdf retrait-negative"><?php echo e(number_format($rapport['totaux']['cdf']['retraits_total'], 2)); ?> FC</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; padding-top: 4px; border-top: 1px solid #ddd;">
                        <span>Mouvement net:</span>
                        <strong class="montant devise-cdf <?php echo e(($rapport['totaux']['cdf']['depots_total'] - $rapport['totaux']['cdf']['retraits_total']) >= 0 ? 'depot-positive' : 'retrait-negative'); ?>">
                            <?php echo e(number_format($rapport['totaux']['cdf']['depots_total'] - $rapport['totaux']['cdf']['retraits_total'], 2)); ?> FC
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
            Le Gérant
        </div>
        
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion Épargne Tumaini Letu</div>
        <div>Document confidentiel - <?php echo e(\Carbon\Carbon::now()->format('d/m/Y H:i:s')); ?></div>
        <div><em>Totaux calculés depuis la création des comptes</em></div>
    </div>

    <!-- Script pour impression -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                if(confirm("Voulez-vous imprimer ce rapport des comptes épargne ?")) {
                    window.print();
                }
            }, 1000);
        }
    </script>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/rapports/epargne.blade.php ENDPATH**/ ?>