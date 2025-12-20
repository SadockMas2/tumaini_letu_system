<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéancier de Remboursement - <?php echo e($credit->compte->numero_compte); ?></title>
    <style>
        @page {
            size: A4;
            margin: 0.7cm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 11px;
            line-height: 1.2;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .header img {
            height: 60px;
            max-width: 110px;
            object-fit: contain;
        }
        
        .header-info {
            text-align: right;
            font-size: 10px;
            color: #2c3e50;
        }
        
        .header-info strong {
            font-size: 12px;
            color: #2c3e50;
        }
        
        .separator {
            border-top: 1px solid #ddd;
            margin: 8px 0;
        }
        
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 11px;
            background-color: #f8f9fa;
            padding: 6px 8px;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        
        .client-info {
            margin-bottom: 12px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            margin-bottom: 5px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 90px;
        }
        
        .info-value {
            font-weight: normal;
            color: #34495e;
        }
        
        .echeancier-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 11px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .echeancier-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            padding: 5px 5px;
            text-align: center;
            border: 1px solid #2c3e50;
        }
        
        .echeancier-table td {
            padding: 4px 5px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .echeancier-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .echeancier-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .echeancier-table .semaine {
            width: 6%;
            font-weight: bold;
        }
        
        .echeancier-table .date {
            width: 15%;
        }
        
        .echeancier-table .interet-hebdo {
            width: 15%;
        }
        
        .echeancier-table .capital-hebdo {
            width: 15%;
        }
        
        .echeancier-table .montant {
            width: 15%;
            font-weight: bold;
        }
        
        .echeancier-table .capital {
            width: 15%;
        }
        
        .total-section {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .total-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            text-align: center;
        }
        
        .total-item {
            padding: 5px;
            border-right: 1px solid #ddd;
        }
        
        .total-item:last-child {
            border-right: none;
        }
        
        .total-item div:first-child {
            color: #2c3e50;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .total-value {
            font-weight: bold;
            font-size: 12px;
            color: #27ae60;
        }
        
        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .signature {
            text-align: center;
            width: 180px;
            font-size: 10px;
            color: #666;
        }
        
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
            padding-top: 8px;
            border-top: 1px solid #eee;
        }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0.7cm; }
            .echeancier-table { box-shadow: none; }
            .client-info { box-shadow: none; }
            .total-section { box-shadow: none; }
        }
        
        .notes {
            margin-top: 12px;
            padding: 8px 10px;
            background-color: #fff8e1;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            font-size: 10px;
            color: #5d4037;
        }
        
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0 15px 0;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .table-container {
            overflow: hidden;
        }
        
        .montant-cell {
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }
        
        .totals-row {
            background-color: #ecf0f1 !important;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .print-btn {
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 11px;
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        .close-btn {
            padding: 8px 16px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 11px;
            transition: background 0.3s;
        }
        
        .close-btn:hover {
            background: #c0392b;
        }
        
        .btn-container {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php
        $creditActif = $compte->credits->where('statut_demande', 'approuve')->first();
        $dateDebut = $creditActif->date_octroi->copy()->addWeeks(2);
        $montantHebdo = $creditActif->remboursement_hebdo;
        
        // Pourcentages pour les intérêts hebdomadaires (16 semaines)
        $pourcentageInterets = [
            14.4154589019438, 12.5668588386971, 11.5077233695784, 10.4164781434722,
            9.292636648909, 9.13522586294972, 8.94327276265538, 6.71531781361745,
            4.45038799289693, 3.14751027755479, 2.80571164465202, 1.80571164465202,
            1.80571164465202, 1.40571164465202, 1.30571164465202, 0.280571164465202
        ];
        
        $totalInterets = round($creditActif->montant_total - $creditActif->montant_accorde, 2);
        $capitalRestant = round($creditActif->montant_total, 2);
        $capitalPrincipalRestant = round($creditActif->montant_accorde, 2);
        $montantHebdo = round($montantHebdo, 2);
        $echeances = [];
        $totalCapitalPaye = 0;
        $totalInteretsPayes = 0;
        
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
            
            // Calcul de l'intérêt hebdomadaire basé sur le pourcentage
            $interetHebdomadaire = round(($totalInterets * $pourcentageInterets[$semaine - 1]) / 100, 2);
            
            // Calcul du capital hebdomadaire (remboursement hebdo - intérêt)
            $capitalHebdomadaire = round($montantHebdo - $interetHebdomadaire, 2);
            
            // Pour la dernière échéance, ajuster pour équilibrer
            if ($semaine == 16) {
                $capitalHebdomadaire = $capitalPrincipalRestant;
                $interetHebdomadaire = round($montantHebdo - $capitalHebdomadaire, 2);
            }
            
            // Limiter le capital au capital principal restant
            if ($capitalHebdomadaire > $capitalPrincipalRestant) {
                $capitalHebdomadaire = $capitalPrincipalRestant;
                $interetHebdomadaire = round($montantHebdo - $capitalHebdomadaire, 2);
            }
            
            // Mettre à jour les totaux
            $totalCapitalPaye += $capitalHebdomadaire;
            $totalInteretsPayes += $interetHebdomadaire;
            
            // Mettre à jour les soldes avec arrondi
            $capitalPrincipalRestant = round($capitalPrincipalRestant - $capitalHebdomadaire, 2);
            $capitalRestant = round($capitalRestant - $montantHebdo, 2);
            
            if ($capitalPrincipalRestant < 0) $capitalPrincipalRestant = 0;
            if ($capitalRestant < 0) $capitalRestant = 0;
            
            $echeances[] = [
                'semaine' => $semaine,
                'date' => $dateEcheance,
                'capital_hebdo' => $capitalHebdomadaire,
                'interet_hebdo' => $interetHebdomadaire,
                'montant_total' => $montantHebdo,
                'capital_restant' => $capitalRestant,
            ];
        }
        
        // Ajustement final pour équilibrer les totaux
        $ajustementCapital = round($creditActif->montant_accorde - $totalCapitalPaye, 2);
        
        if (abs($ajustementCapital) > 0.01) {
            $echeances[15]['capital_hebdo'] = round($echeances[15]['capital_hebdo'] + $ajustementCapital, 2);
            $echeances[15]['interet_hebdo'] = round($montantHebdo - $echeances[15]['capital_hebdo'], 2);
            $echeances[15]['capital_restant'] = 0;
            
            // Recalculer les totaux après ajustement
            $totalCapitalPaye = array_sum(array_column($echeances, 'capital_hebdo'));
            $totalInteretsPayes = array_sum(array_column($echeances, 'interet_hebdo'));
        }
        
        // Vérification des totaux avec arrondi
        $totalCapitalFinal = round(array_sum(array_column($echeances, 'capital_hebdo')), 2);
        $totalInteretsFinal = round(array_sum(array_column($echeances, 'interet_hebdo')), 2);
        $totalGeneralFinal = round(array_sum(array_column($echeances, 'montant_total')), 2);
        
        // Ajustement final pour s'assurer que les totaux correspondent
        $differenceCapital = round($creditActif->montant_accorde - $totalCapitalFinal, 2);
        if (abs($differenceCapital) > 0.01) {
            $echeances[15]['capital_hebdo'] = round($echeances[15]['capital_hebdo'] + $differenceCapital, 2);
            $echeances[15]['interet_hebdo'] = round($montantHebdo - $echeances[15]['capital_hebdo'], 2);
        }
    ?>

    <div class="header">
        <div class="logo">
            <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
            <?php else: ?>
                <div style="height: 60px; width: 110px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 4px; font-size: 10px; color: #666;">
                    <div style="text-align: center; padding: 5px;">
                        <strong>TUMAINI LETU</strong><br>
                        <span style="font-size: 8px;">asbl</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="header-info">
            <div><strong>Tumaini Letu asbl</strong></div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumainiletu@gmail.com</div>
        </div>
    </div>

    <div class="ref-date">
        <div>RÉF : ÉCH-<?php echo e($creditActif->id); ?>-<?php echo e(date('Ymd')); ?></div>
        <div>DATE : <?php echo e(now()->format('d/m/Y')); ?></div>
        <div>PÉRIODE : 16 SEMAINES</div>
    </div>

    <div class="title">ÉCHÉANCIER DE REMBOURSEMENT</div>

    <div class="client-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Compte :</span>
                <span class="info-value"><?php echo e($compte->numero_compte); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Membre :</span>
                <span class="info-value"><?php echo e($compte->nom); ?> <?php echo e($compte->postnom); ?> <?php echo e($compte->prenom); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Type :</span>
                <span class="info-value"><?php echo e(ucfirst($creditActif->type_credit)); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Octroi :</span>
                <span class="info-value"><?php echo e($creditActif->date_octroi->format('d/m/Y')); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant Accordé :</span>
                <span class="info-value montant-cell"><?php echo e(number_format($creditActif->montant_accorde, 2, ',', ' ')); ?> $</span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant Total :</span>
                <span class="info-value montant-cell"><?php echo e(number_format($creditActif->montant_total, 2, ',', ' ')); ?> $</span>
            </div>
            <div class="info-item">
                <span class="info-label">Début Remb. :</span>
                <span class="info-value"><?php echo e($dateDebut->format('d/m/Y')); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Fin :</span>
                <span class="info-value"><?php echo e($creditActif->date_echeance->format('d/m/Y')); ?></span>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="echeancier-table">
            <thead>
                <tr>
                    <th class="semaine">Sem</th>
                    <th class="date">Date</th>
                    <th class="interet-hebdo">Intérêt</th>
                    <th class="capital-hebdo">Capital</th>
                    <th class="montant">À Payer</th>
                    <th class="capital">Reste</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $echeances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $echeance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="semaine"><?php echo e($echeance['semaine']); ?></td>
                    <td class="date"><?php echo e($echeance['date']->format('d/m/Y')); ?></td>
                    <td class="montant-cell"><?php echo e(number_format($echeance['interet_hebdo'], 2, ',', ' ')); ?> $</td>
                    <td class="montant-cell"><?php echo e(number_format($echeance['capital_hebdo'], 2, ',', ' ')); ?> $</td>
                    <td class="montant montant-cell"><?php echo e(number_format($echeance['montant_total'], 2, ',', ' ')); ?> $</td>
                    <td class="montant-cell"><?php echo e(number_format($echeance['capital_restant'], 2, ',', ' ')); ?> $</td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                
            </tbody>
        </table>
    </div>

    <div class="total-section">
        <div class="total-grid">
            <div class="total-item">
                <div>Montant Accordé</div>
                <div class="total-value montant-cell"><?php echo e(number_format($creditActif->montant_accorde, 2, ',', ' ')); ?> $</div>
            </div>
            <div class="total-item">
                <div>Total Intérêts</div>
                <div class="total-value montant-cell"><?php echo e(number_format($totalInterets, 2, ',', ' ')); ?> $</div>
            </div>
            <div class="total-item">
                <div>Montant Total</div>
                <div class="total-value montant-cell"><?php echo e(number_format($creditActif->montant_total, 2, ',', ' ')); ?> $</div>
            </div>
            <div class="total-item">
                <div>Remb. Hebdo</div>
                <div class="total-value montant-cell"><?php echo e(number_format($montantHebdo, 2, ',', ' ')); ?> $</div>
            </div>
        </div>
    </div>

    <div class="notes">
        <strong>Notes importantes :</strong><br>
        • Remboursement hebdomadaire fixe : <strong><?php echo e(number_format($montantHebdo, 2, ',', ' ')); ?> USD</strong><br>
        • Jour de paiement : chaque <strong><?php echo e($dateDebut->locale('fr')->translatedFormat('l')); ?></strong><br>
        • Début : 2 semaines après l'octroi (le <?php echo e($dateDebut->format('d/m/Y')); ?>)<br>
        • Pénalité de retard : 5% du montant dû<br>
        • La caution sera débloquée après remboursement complet du crédit
    </div>

    <div class="signature-section">
        <div class="signature">
            <div style="margin-bottom: 5px;"></div>
            <div style="margin-bottom: 20px; font-weight: bold;">Le Membre</div>
            <div class="signature-line">
                <?php echo e($compte->nom); ?> <?php echo e($compte->postnom); ?> <?php echo e($compte->prenom); ?>

            </div>
        </div>
        <div class="signature">
            <div style="margin-bottom: 5px;"></div>
            <div style="margin-bottom: 20px; font-weight: bold;">Tumaini Letu asbl</div>
            <div class="signature-line">
                Le Responsable
            </div>
        </div>
    </div>

    <div class="footer">
        Document généré le <?php echo e(now()->format('d/m/Y à H:i')); ?> | Tumaini Letu asbl - Goma, RDC
    </div>

    <div class="btn-container no-print">
        <button onclick="window.print()" class="print-btn">
            📄 Imprimer l'échéancier
        </button>
        <button onclick="window.close()" class="close-btn">
            ❌ Fermer la fenêtre
        </button>
    </div>

    <script>
        // Ajustement pour l'impression
        document.addEventListener('DOMContentLoaded', function() {
            const printBtn = document.querySelector('.print-btn');
            printBtn.addEventListener('click', function() {
                window.print();
            });
        });
    </script>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/credits/echeancier.blade.php ENDPATH**/ ?>