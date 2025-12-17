<?php

namespace App\Filament\Pages;

use App\Services\SmsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use UnitEnum;

class SmsSettings extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string |BackedEnum|null $navigationIcon = 'heroicon-o-cog';
    protected static string |UnitEnum|null $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Configuration SMS';
    protected static ?int $navigationSort = 30;
    protected  string $view = 'filament.pages.sms-settings-simple';
    
    public $api_url;
    public $api_id;
    public $api_password;
    public $sender_id;
    public $sms_type = 'T';
    public $encoding = 'T';
    public $validity_period = 3600;
    public $default_phone_prefix = '243';
    
    public function mount(): void
    {
        $this->form->fill([
            'api_url' => config('services.asmsc.url'),
            'api_id' => config('services.asmsc.api_id'),
            'api_password' => config('services.asmsc.api_password'),
            'sender_id' => config('services.asmsc.sender_id'),
        ]);
    }

         public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        // $user = Auth::user();
        return $user && $user->can('view_rapportmembres');
    }
    
    public function getFormSchema(): array
    {
        return [
            Section::make('Configuration API Dream Digital')
                ->schema([
                    Components\TextInput::make('api_url')
                        ->label('URL de l\'API')
                        ->required()
                        ->url(),
                        
                    Components\TextInput::make('api_id')
                        ->label('ID API')
                        ->required(),
                        
                    Components\TextInput::make('api_password')
                        ->label('Mot de passe API')
                        ->password()
                        ->required(),
                        
                    Components\TextInput::make('sender_id')
                        ->label('Sender ID')
                        ->required()
                        ->maxLength(11),
                ])
                ->columns(2),
                
            Section::make('Actions')
                ->schema([
                    Actions::make([
                        Action::make('save')
                            ->label('Sauvegarder')
                            ->submit('save')
                            ->color('primary'),
                            
                        Action::make('test_connection')
                            ->label('Tester la connexion')
                            ->action('testConnection'),
                    ]),
                ]),
        ];
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Mettre à jour le fichier .env
        $this->updateEnv($data);
        
        Notification::make()
            ->title('Configuration sauvegardée')
            ->success()
            ->send();
    }
    
    public function testConnection(): void
    {
        try {
            $smsService = app(SmsService::class);
            $result = $smsService->testConnection();
            
            if ($result['success']) {
                Notification::make()
                    ->title('Connexion réussie')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Connexion échouée')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    
    
    private function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return;
        }
        
        $content = File::get($envPath);
        
        // Mettre à jour chaque variable
        foreach ($data as $key => $value) {
            $envKey = strtoupper('asmsc_' . $key);
            $pattern = "/^{$envKey}=.*/m";
            $replacement = "{$envKey}={$value}";
            
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$replacement}";
            }
        }
        
        File::put($envPath, $content);
    }

    

     
}

