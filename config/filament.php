<?php

return [

    'path' => env('FILAMENT_PATH', 'admin'),

    // Définir le panel par défaut
    'default_panel' => 'admin',

    'panels' => [
        'admin' => [
            'path' => '/admin',   // URL du panel
            'resources' => [],    // On ajoutera nos ressources plus tard
            'widgets' => [],
        ],
    ],

    'broadcasting' => [

        // 'echo' => [
        //     'broadcaster' => 'pusher',
        //     'key' => env('VITE_PUSHER_APP_KEY'),
        //     'cluster' => env('VITE_PUSHER_APP_CLUSTER'),
        //     'wsHost' => env('VITE_PUSHER_HOST'),
        //     'wsPort' => env('VITE_PUSHER_PORT'),
        //     'wssPort' => env('VITE_PUSHER_PORT'),
        //     'authEndpoint' => '/broadcasting/auth',
        //     'disableStats' => true,
        //     'encrypted' => true,
        //     'forceTLS' => true,
        // ],

    ],

    'default_filesystem_disk' => env('FILESYSTEM_DISK', 'local'),

    'assets_path' => null,

    'cache_path' => base_path('bootstrap/cache/filament'),

    'livewire_loading_delay' => 'default',

    'file_generation' => [
        'flags' => [],
    ],

    'system_route_prefix' => 'filament',

];
