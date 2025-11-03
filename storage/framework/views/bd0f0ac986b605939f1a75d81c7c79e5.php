<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéanciers du Crédit Groupe - <?php echo e($credit->compte->nom); ?></title>
    <style>
        /* Styles pour l'impression A4 */
        @page {
            size: A4;
            margin: 1cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            align-items: flex-start;
        }
        
        .header img {
            height: 65px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .header-info {
            text-align: right;
            font-size: 9px;
            flex: 1;
            margin-left: 12px;
        }
        
        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }
        
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .client-info {
            margin-bottom: 12px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 110px;
        }
        
        .echeancier-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9px;
        }
        
        .echeancier-table th,
        .echeancier-table td {
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: center;
        }
        
        .echeancier-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .echeancier-table .semaine {
            width: 6%;
        }
        
        .echeancier-table .date {
            width: 12%;
        }
        
        .echeancier-table .capital-hebdo {
            width: 12%;
        }
        
        .echeancier-table .interet-hebdo {
            width: 12%;
        }
        
        .echeancier-table .montant {
            width: 12%;
        }
        
        .echeancier-table .capital {
            width: 12%;
        }
        
        .total-section {
            margin-top: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #000;
        }
        
        .total-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            text-align: center;
        }
        
        .total-item {
            padding: 4px;
        }
        
        .total-value {
            font-weight: bold;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .signature-section {
            margin-top: 25px;
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
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        
        /* Styles pour l'impression */
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            .page-break { page-break-after: always; }
        }
        
        /* Styles pour l'écran */
        .screen-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        
        .screen-actions {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <!-- En-tête pour l'écran seulement -->
    <div class="screen-header no-print">
        <h1 style="margin: 0; font-size: 28px;">
            <i class="fas fa-users"></i> Échéanciers du Crédit Groupe
        </h1>
        <p style="margin: 5px 0; font-size: 16px; opacity: 0.9;">
            Groupe: <?php echo e($credit->compte->nom); ?> - <?php echo e($credit->compte->numero_compte); ?>

        </p>
    </div>

    <!-- Actions pour l'écran seulement -->
    <div class="screen-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer Tous les Échéanciers
        </button>
        <a href="<?php echo e(route('credits.details-groupe', $credit->id)); ?>" class="btn btn-success">
            <i class="fas fa-chart-pie"></i> Retour aux Détails
        </a>
        <a href="<?php echo e(route('comptes.details', $credit->compte_id)); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour au Compte
        </a>
    </div>

    <!-- Résumé du crédit groupe (écran seulement) -->
    <div class="no-print" style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #2c5530; border-bottom: 2px solid #28a745; padding-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Résumé du Crédit Groupe
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
            <div style="text-align: center; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                <div style="font-size: 12px; color: #1976d2; margin-bottom: 5px;">Montant Total Groupe</div>
                <div style="font-size: 18px; font-weight: bold; color: #1976d2;">
                    <?php echo e(number_format($credit->montant_total, 2, ',', ' ')); ?> USD
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px;">
                <div style="font-size: 12px; color: #388e3c; margin-bottom: 5px;">Remb. Hebdo Total</div>
                <div style="font-size: 18px; font-weight: bold; color: #388e3c;">
                    <?php echo e(number_format($credit->remboursement_hebdo_total, 2, ',', ' ')); ?> USD
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f3e5f5; border-radius: 8px;">
                <div style="font-size: 12px; color: #7b1fa2; margin-bottom: 5px;">Date Début</div>
                <div style="font-size: 18px; font-weight: bold; color: #7b1fa2;">
                    <?php echo e($credit->date_octroi->copy()->addWeeks(2)->format('d/m/Y')); ?>

                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fff3e0; border-radius: 8px;">
                <div style="font-size: 12px; color: #f57c00; margin-bottom: 5px;">Date Fin</div>
                <div style="font-size: 18px; font-weight: bold; color: #f57c00;">
                    <?php echo e($credit->date_echeance->format('d/m/Y')); ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Échéanciers par membre -->
    <?php $__currentLoopData = $echeanciers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $compteId => $echeancierMembre): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $premierEcheance = $echeancierMembre->first();
            $membreNom = $premierEcheance->nom . ' ' . $premierEcheance->prenom;
            $compteNumero = $premierEcheance->numero_compte;
            
            // Récupérer les informations exactes du crédit membre
            $creditMembre = \App\Models\Credit::where('credit_groupe_id', $credit->id)
                ->where('compte_id', $premierEcheance->compte_id)
                ->first();
            
            // Utiliser les montants réels du crédit membre
            $montantAccordeMembre = $creditMembre->montant_accorde ?? 0;
            $montantTotalMembre = $creditMembre->montant_total ?? 0;
            $montantHebdo = $creditMembre->remboursement_hebdo ?? 0;
            
            // Si les données du crédit membre ne sont pas disponibles, utiliser les données de l'échéancier
            if ($montantAccordeMembre == 0) {
                $montantTotalMembre = $echeancierMembre->sum('montant_a_payer');
                $montantAccordeMembre = $montantTotalMembre * 0.816; // 81.6% pour le capital
                $montantHebdo = $premierEcheance->montant_a_payer;
            }
            
            // Calculs précis sans arrondis cumulatifs
            $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
            $totalInterets = $montantTotalMembre - $montantAccordeMembre;
            $interetHebdomadaire = $totalInterets / 16;
            
            // Calcul du capital hebdomadaire exact
            $capitalHebdomadaire = $montantAccordeMembre / 16;
            
            $echeances = [];
            $capitalRestant = $montantTotalMembre;
            $capitalPrincipalRestant = $montantAccordeMembre;
            
            for ($semaine = 1; $semaine <= 16; $semaine++) {
                $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
                
                // Pour les 15 premières semaines, utiliser les montants calculés
                if ($semaine < 16) {
                    $capitalPrincipalRestant -= $capitalHebdomadaire;
                    $capitalRestant -= $montantHebdo;
                    
                    $echeances[] = [
                        'semaine' => $semaine,
                        'date' => $dateEcheance,
                        'capital_hebdo' => $capitalHebdomadaire,
                        'interet_hebdo' => $interetHebdomadaire,
                        'montant_total' => $montantHebdo,
                        'capital_restant' => $capitalRestant,
                        'capital_principal_restant' => $capitalPrincipalRestant
                    ];
                } else {
                    // Dernière semaine : ajustement précis pour équilibrer
                    $capitalDerniereSemaine = $montantAccordeMembre - ($capitalHebdomadaire * 15);
                    $montantDerniereSemaine = $capitalDerniereSemaine + $interetHebdomadaire;
                    
                    $echeances[] = [
                        'semaine' => $semaine,
                        'date' => $dateEcheance,
                        'capital_hebdo' => $capitalDerniereSemaine,
                        'interet_hebdo' => $interetHebdomadaire,
                        'montant_total' => $montantDerniereSemaine,
                        'capital_restant' => 0,
                        'capital_principal_restant' => 0
                    ];
                }
            }
            
            // Vérification et correction des totaux
            $totalCapitalCalcule = array_sum(array_column($echeances, 'capital_hebdo'));
            $totalInteretsCalcule = array_sum(array_column($echeances, 'interet_hebdo'));
            $totalGeneralCalcule = array_sum(array_column($echeances, 'montant_total'));
            
            // Ajustement final pour garantir l'exactitude
            if (abs($totalCapitalCalcule - $montantAccordeMembre) > 0.01) {
                $ajustement = $montantAccordeMembre - $totalCapitalCalcule;
                $echeances[15]['capital_hebdo'] += $ajustement;
                $echeances[15]['montant_total'] += $ajustement;
            }
        ?>

        <!-- Début d'un échéancier membre -->
        <div class="page-break">
            <!-- En-tête Tumaini Letu -->
            <div class="header">
                <div class="logo">
                    <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                        <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
                    <?php elseif(file_exists(public_path('images/logo-tumaini1.jpg'))): ?>
                        <img src="<?php echo e(asset('images/logo-tumaini1.jpg')); ?>" alt="TUMAINI LETU asbl">
                    <?php elseif(file_exists(public_path('images/logo-tumaini1.jpeg'))): ?>
                        <img src="<?php echo e(asset('images/logo-tumaini1.jpeg')); ?>" alt="TUMAINI LETU asbl">
                    <?php elseif(file_exists(public_path('images/logo-tumaini1.svg'))): ?>
                        <img src="<?php echo e(asset('images/logo-tumaini1.svg')); ?>" alt="TUMAINI LETU asbl">
                    <?php else: ?>
                        <div style="height: 65px; width: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc; font-size: 9px;">
                            TUMAINI LETU
                        </div>
                    <?php endif; ?>
                </div>
                <div class="header-info">
                    <div><strong>Tumaini Letu asbl</strong></div>
                    <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
                    <div>NUM BED : 14453756111</div>
                    <div>Tel : +243982618321</div>
                    <div>Email : tumainiletu@gmail.com</div>
                </div>
            </div>

            <div class="separator"></div>

            <div class="ref-date">
                <div>RÉFÉRENCE : ÉCH-G<?php echo e($credit->id); ?>-M<?php echo e($compteId); ?>-<?php echo e(date('Ymd')); ?></div>
                <div>DATE : <?php echo e(now()->format('d/m/Y')); ?></div>
                <div>PÉRIODE : 16 SEMAINES</div>
            </div>

            <div class="separator"></div>

            <!-- Informations du membre et du crédit -->
            <div class="client-info">
                <div style="text-align: center; font-weight: bold; margin-bottom: 8px; font-size: 13px;">
                    ÉCHÉANCIER DE REMBOURSEMENT - CRÉDIT GROUPE
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Numéro Compte :</span>
                        <span><?php echo e($compteNumero); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Membre :</span>
                        <span><?php echo e($membreNom); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Type Crédit :</span>
                        <span>Groupe Solidaire</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Groupe :</span>
                        <span><?php echo e($credit->compte->nom); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Montant Accordé :</span>
                        <span><?php echo e(number_format($montantAccordeMembre, 2, ',', ' ')); ?> USD</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Montant Total :</span>
                        <span><?php echo e(number_format($montantTotalMembre, 2, ',', ' ')); ?> USD</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Remb. Hebdo :</span>
                        <span><?php echo e(number_format($montantHebdo, 2, ',', ' ')); ?> USD</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date Début Remb. :</span>
                        <span><?php echo e($dateDebut->format('d/m/Y')); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date Fin :</span>
                        <span><?php echo e($credit->date_echeance->format('d/m/Y')); ?></span>
                    </div>
                </div>
            </div>

            <!-- Tableau des échéances -->
            <table class="echeancier-table">
                <thead>
                    <tr>
                        <th class="semaine">Semaine</th>
                        <th class="date">Date Échéance</th>
                        <th class="capital-hebdo">Capital Hebdo</th>
                        <th class="interet-hebdo">Intérêt Hebdo</th>
                        <th class="montant">Montant à Payer</th>
                        <th class="capital">Capital Restant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $echeances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $echeance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="semaine"><?php echo e($echeance['semaine']); ?></td>
                        <td class="date"><?php echo e($echeance['date']->format('d/m/Y')); ?></td>
                        <td class="capital-hebdo"><?php echo e(number_format($echeance['capital_hebdo'], 2, ',', ' ')); ?> USD</td>
                        <td class="interet-hebdo"><?php echo e(number_format($echeance['interet_hebdo'], 2, ',', ' ')); ?> USD</td>
                        <td class="montant"><?php echo e(number_format($echeance['montant_total'], 2, ',', ' ')); ?> USD</td>
                        <td class="capital"><?php echo e(number_format($echeance['capital_restant'], 2, ',', ' ')); ?> USD</td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>

            <!-- Section des totaux -->
            <div class="total-section">
                <div class="total-grid">
                    <div class="total-item">
                        <div>Montant Accordé</div>
                        <div class="total-value"><?php echo e(number_format($montantAccordeMembre, 2, ',', ' ')); ?> USD</div>
                    </div>
                    <div class="total-item">
                        <div>Total Intérêts</div>
                        <div class="total-value"><?php echo e(number_format($montantTotalMembre - $montantAccordeMembre, 2, ',', ' ')); ?> USD</div>
                    </div>
                    <div class="total-item">
                        <div>Montant Total</div>
                        <div class="total-value"><?php echo e(number_format($montantTotalMembre, 2, ',', ' ')); ?> USD</div>
                    </div>
                    <div class="total-item">
                        <div>Durée Totale</div>
                        <div class="total-value">16 Semaines</div>
                    </div>
                </div>
            </div>

            <!-- Notes importantes -->
            <div style="margin-top: 12px; padding: 8px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-size: 9px;">
                <strong>Notes importantes :</strong><br>
                • Le remboursement commence 2 semaines après la date d'octroi du crédit<br>
                • Paiement hebdomadaire obligatoire chaque <?php echo e($dateDebut->locale('fr')->translatedFormat('l')); ?><br>
                • En cas de retard, des pénalités de 5% seront appliquées<br>
                • La caution de <?php echo e(number_format($montantAccordeMembre * 0.20, 2, ',', ' ')); ?> USD sera débloquée après remboursement complet<br>
                • Solidarité groupe : tout retard affecte l'ensemble des membres
            </div>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="signature">
                    Signature du Membre<br>
                    <div style="margin-top: 35px;"><?php echo e($membreNom); ?></div>
                </div>
                <div class="signature">
                    Signature du Responsable<br>
                    <div style="margin-top: 35px;">Tumaini Letu asbl</div>
                </div>
            </div>

            <!-- Pied de page -->
            <div class="footer">
                <div class="separator"></div>
                Document généré le <?php echo e(now()->format('d/m/Y à H:i')); ?> | Page <?php echo e($loop->iteration); ?>/<?php echo e($loop->count); ?> | Tumaini Letu asbl
            </div>
        </div>
        <!-- Fin d'un échéancier membre -->

        <?php if(!$loop->last): ?>
            <!-- Saut de page pour impression -->
            <div style="page-break-after: always;" class="no-print"></div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <!-- Boutons d'action finaux (non imprimés) -->
    <div class="no-print" style="margin-top: 25px; text-align: center; padding: 20px;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer Tous les Échéanciers
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Fermer
        </button>
        <a href="<?php echo e(route('credits.details-groupe', $credit->id)); ?>" class="btn btn-success">
            <i class="fas fa-arrow-left"></i> Retour aux Détails
        </a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        function downloadPDF() {
            window.print();
        }

        // Option d'impression automatique
        // window.addEventListener('load', function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 1000);
        // });
    </script>
</body>
</html><?php /**PATH C:\STORAGE\TUMAINI LETU\System\tumainiletusystem\tumainiletusystem2.0\resources\views/credits/echeanciers-groupe.blade.php ENDPATH**/ ?>