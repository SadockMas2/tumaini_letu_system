<?php
    $logoPath = $logoPath ?? asset('images/logo-tumaini1.png'); // chemin du logo
    $logoHeight = $logoHeight ?? 'h-16'; // hauteur du logo
    $text = $text ?? ''; // texte affiché
    $textGradient = $textGradient ?? 'from-emerald-500 via-green-400 to-teal-500'; // dégradé du texte
    $marginRightPx = $marginRightPx ?? '-5px'; // marge droite personnalisée
?>

<div class="w-full flex flex-col items-center justify-center text-center">
    <!-- Logo -->
    <img 
        src="<?php echo e($logoPath); ?>" 
        alt="Logo" 
        class="<?php echo e($logoHeight); ?> w-auto mb-1 object-contain"
        style="margin-right: <?php echo e($marginRightPx); ?>;"
    >

    <!-- Texte -->
    <span class="text-[16px] font-semibold bg-gradient-to-r <?php echo e($textGradient); ?> bg-clip-text text-transparent uppercase tracking-wide">
        <?php echo e($text); ?>

    </span>
</div>
<?php /**PATH D:\APP\TUMAINI LETU\tumainiletusystem2.0\resources\views/vendor/filament-panels/components/logo.blade.php ENDPATH**/ ?>