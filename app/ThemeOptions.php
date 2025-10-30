<?php

namespace App;

class ThemeOptions
{
    const LIGHT = 'light';
    const DARK = 'dark';
    const BLUE = 'blue';
    const GREEN = 'green';

    public static function all(): array
    {
        return [
            self::LIGHT => 'Clair',
            self::DARK => 'Sombre',
            self::BLUE => 'Bleu',
            self::GREEN => 'Vert',
        ];
    }

    public static function getLabel(string $theme): string
    {
        return self::all()[$theme] ?? 'Clair';
    }

    public static function getColor(string $theme): string
    {
        return match($theme) {
            self::DARK => 'gray',
            self::BLUE => 'blue',
            self::GREEN => 'green',
            default => 'gray',
        };
    }

    public static function getIcon(string $theme): string
    {
        return match($theme) {
            self::DARK => 'heroicon-o-moon',
            self::BLUE => 'heroicon-o-sparkles',
            self::GREEN => 'heroicon-o-leaf',
            default => 'heroicon-o-sun',
        };
    }
}