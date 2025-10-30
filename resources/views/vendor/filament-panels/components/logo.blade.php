@php
    $logoPath = $logoPath ?? asset('images/logo-tumaini1.png'); // chemin du logo
    $logoHeight = $logoHeight ?? 'h-16'; // hauteur du logo
    $text = $text ?? ''; // texte affiché
    $textGradient = $textGradient ?? 'from-emerald-500 via-green-400 to-teal-500'; // dégradé du texte
    $marginRightPx = $marginRightPx ?? '-5px'; // marge droite personnalisée
@endphp

<div class="w-full flex flex-col items-center justify-center text-center">
    <!-- Logo -->
    <img 
        src="{{ $logoPath }}" 
        alt="Logo" 
        class="{{ $logoHeight }} w-auto mb-1 object-contain"
        style="margin-right: {{ $marginRightPx }};"
    >

    <!-- Texte -->
    <span class="text-[16px] font-semibold bg-gradient-to-r {{ $textGradient }} bg-clip-text text-transparent uppercase tracking-wide">
        {{ $text }}
    </span>
</div>
