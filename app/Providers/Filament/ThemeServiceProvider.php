<?php

namespace App\Providers\Filament;

use App\ThemeOptions;
use Filament\Panel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Panel::configureUsing(function (Panel $panel) {
            $theme = Session::get('user_theme', ThemeOptions::LIGHT);
            
            $panel->theme(match($theme) {
                ThemeOptions::DARK => 'filament-themes::dark',
                ThemeOptions::BLUE => 'filament-themes::blue',
                ThemeOptions::GREEN => 'filament-themes::green',
                default => 'filament-themes::light',
            });
        });
    }
}