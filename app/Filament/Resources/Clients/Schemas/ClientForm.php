<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\TypeCompte;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Tables\Columns\SelectColumn;
use Symfony\Contracts\Service\Attribute\Required;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            
            \Filament\Schemas\Components\Section::make('Informations personnelles')
                ->description('Renseignez les informations de base du client.')
                ->schema([
                    \Filament\Schemas\Components\Grid::make(3)->schema([
                        TextInput::make('nom')->required(),
                        TextInput::make('postnom')->required(),
                        TextInput::make('prenom')->required(),
                        DatePicker::make('date_naissance')->label('Date de naissance'),
                        TextInput::make('email')->label('Email')->email()->required(),
                        FileUpload::make('image')
                                ->label('Image')
                                ->image()
                                ->directory('clients')
                                ->disk('public')  // ← Ajouter cette ligne
                                ->visibility('public')
                                ->avatar()
                                ->maxSize(5120),
                    
                        TextInput::make('telephone')->label('Téléphone')->tel()->required(),

                           Select::make('activites')
                            ->label('Activité (s)')
                            ->options([
                                'Com' => 'commercant',
                                'phar'=> 'Pharmacien',
                                'hum'=> 'Humanitaire',

                            ])
                            ->searchable()
                            ->required(),

                            Select::make('etat_civil')
                                ->label('État civil')
                                ->options([
                                    'Célibataire' => 'Célibataire',
                                    'Marié' => 'Marié(e)',
                                    'Divorcé' => 'Divorcé(e)',
                                    'Veuf' => 'Veuf(ve)',
                                ])
                                ->searchable()
                                ->required(),
                            

                    ]),
                ]),

            \Filament\Schemas\Components\Section::make('Adresse & Localisation')
                ->schema([
                    \Filament\Schemas\Components\Grid::make(3)->schema([
                        TextInput::make('adresse')
                           ->required(),
                        
                        TextInput::make('ville'),
                        Select::make('pays')
                            ->label('Pays')
                            ->searchable()
                            ->required()
                            ->options([
                        'AF' => 'Afghanistan',
                        'AL' => 'Albanie',
                        'DZ' => 'Algérie',
                        'AO' => 'Angola',
                        'AR' => 'Argentine',
                        'AM' => 'Arménie',
                        'AU' => 'Australie',
                        'AT' => 'Autriche',
                        'AZ' => 'Azerbaïdjan',
                        'BE' => 'Belgique',
                        'BJ' => 'Bénin',
                        'BF' => 'Burkina Faso',
                        'BI' => 'Burundi',
                        'BR' => 'Brésil',
                        'CA' => 'Canada',
                        'CF' => 'Centrafrique',
                        'TD' => 'Tchad',
                        'CL' => 'Chili',
                        'CN' => 'Chine',
                        'CD' => 'République Démocratique du Congo',
                        'CG' => 'Congo',
                        'CI' => "Côte d'Ivoire",
                        'DK' => 'Danemark',
                        'DJ' => 'Djibouti',
                        'EG' => 'Égypte',
                        'ET' => 'Éthiopie',
                        'FR' => 'France',
                        'DE' => 'Allemagne',
                        'GA' => 'Gabon',
                        'GH' => 'Ghana',
                        'GR' => 'Grèce',
                        'IN' => 'Inde',
                        'IT' => 'Italie',
                        'JP' => 'Japon',
                        'KE' => 'Kenya',
                        'MG' => 'Madagascar',
                        'ML' => 'Mali',
                        'MA' => 'Maroc',
                        'MU' => 'Maurice',
                        'NE' => 'Niger',
                        'NG' => 'Nigeria',
                        'RW' => 'Rwanda',
                        'SN' => 'Sénégal',
                        'ZA' => 'Afrique du Sud',
                        'ES' => 'Espagne',
                        'SD' => 'Soudan',
                        'SE' => 'Suède',
                        'CH' => 'Suisse',
                        'TZ' => 'Tanzanie',
                        'TG' => 'Togo',
                        'TN' => 'Tunisie',
                        'UG' => 'Ouganda',
                        'GB' => 'Royaume-Uni',
                        'US' => 'États-Unis',
                        'ZM' => 'Zambie',
                        'ZW' => 'Zimbabwe',
                                // 👉 tu peux réduire ta liste ou la charger dynamiquement plus tard
                            ]),
                        TextInput::make('code_postal')->label('Code postal'),
                    ]),
                ]),

            \Filament\Schemas\Components\Section::make('Compte membre')
                ->description('Paramètres liés au compte du membre.')
                ->schema([
                    \Filament\Schemas\Components\Grid::make(2)->schema([
                        Select::make('type_compte')
                            ->label('Type de compte')
                            ->options(TypeCompte::pluck('designation', 'designation'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type_client')
                            ->label('Type de client')
                            ->options([
                                'Prt' => 'Particulier',
                                'Etrp'=> 'Entreprise',
                            ])
                            ->searchable()
                            ->required(),
                        FileUpload::make('signature')
                            ->label('Signature')
                            ->image()
                            ->directory('clients')
                            ->disk('public')  // ← Ajouter cette ligne
                            ->visibility('public')
                            ->avatar()
                            ->maxSize(5120),
                    
                        TextInput::make('identifiant_national')
                            ->label('Identifiant national')
                            ->columnSpan(2)
                            ->required(),                            
                    ])
                 
            
                ]),

            Hidden::make('id_createur')->default(Filament::auth()->id()),
            TextInput::make('status')->required()->default('actif')->hidden(),
        ]);
    }
}
