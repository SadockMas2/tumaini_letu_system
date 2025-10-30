<?php

namespace App\Filament\Widgets;

use App\ThemeOptions;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Session;

class HeaderThemeSwitcher extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static bool $isLazy = false;
    
    protected string $view = 'filament.widgets.theme-switcher';

    public function getThemeAction(): Action
    {
        $currentTheme = Session::get('user_theme', ThemeOptions::LIGHT);
        
        return Action::make('theme')
            ->label('Changer le thème')
            ->icon(ThemeOptions::getIcon($currentTheme))
            ->color('gray')
            ->modalHeading('Choisir un thème')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(view('filament.widgets.theme-switcher-modal', [
                'actions' => $this->getThemeActions()
            
            ]));

            
    }

    protected function getThemeActions(): array
    {
        $actions = [];
        
        foreach (ThemeOptions::all() as $themeValue => $themeLabel) {
            $actions[] = Action::make($themeValue)
                ->label($themeLabel)
                ->icon(ThemeOptions::getIcon($themeValue))
                ->color(ThemeOptions::getColor($themeValue))
                ->action(function () use ($themeValue) {
                    Session::put('user_theme', $themeValue);
                    // ✅ CORRECTION : Utilisez redirect()->back() au lieu de redirect()
                    return redirect()->back();
                })
                ->extraAttributes([
                    'wire:navigate' => true // ✅ Ajoutez cette ligne pour Livewire
                ]);
        }
        
        return $actions;
    }
}