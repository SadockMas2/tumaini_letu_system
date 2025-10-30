<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\HeaderThemeSwitcher;
use Filament\Navigation\MenuItem;
use Filament\Actions\Action;
// use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Navigation\UserMenuItem; // ✅ IMPORT CORRECT
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Filament\Http\Middleware\DispatchServingFilamentEvent;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->profile()
            ->path('admin')
            ->brandLogo(asset('images/logo-tumaini1.png'))
            ->brandName('TUMAINI LETU SYSTEM')
            ->brandLogo(fn () => view('vendor.filament-panels.components.logo'))
            ->login()
            ->registration()
            ->passwordReset()
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Gestion du système')
                    ->icon('heroicon-o-cog'),
                NavigationGroup::make()
                    ->label('Comptes et utilisateurs')
                    ->icon('heroicon-o-user-group'),
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                HeaderThemeSwitcher::class,
])
            // ✅ APPROCHE SIMPLIFIÉE - SUPPRIMEZ LE USER MENU ITEM POUR L'INSTANT
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                HandlePrecognitiveRequests::class,
                DispatchServingFilamentEvent::class,
            ])
            ->userMenuItems([
            'settings' => Action::make('settings')
                ->label('Paramètres')
                ->url(fn (): string => '/admin/settings')
                ->icon('heroicon-o-cog-6-tooth'),
            ])
            
            ->authMiddleware([
                Authenticate::class,
            ])

            ->plugins([
            //    FilamentShieldPlugin::make()
            ]); // ← Ajouter cette ligne
    }
}