<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Nom'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('profilType.name')
                ->label('Type de Profil'),
            ExportColumn::make('email_verified_at')
                ->label('Email Vérifié le')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y H:i') : 'Non vérifié'),
            ExportColumn::make('created_at')
                ->label('Créé le')
                ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
            ExportColumn::make('updated_at')
                ->label('Modifié le')
                ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
        ];
    }

    public function map($record): array
    {
        // Cette méthode sera utilisée automatiquement avec les colonnes définies
        // Mais nous ajoutons un mapping personnalisé pour nettoyer les données
        return [
            'id' => $record->id,
            'name' => $this->cleanForXml($record->name),
            'email' => $this->cleanForXml($record->email),
            'profil_type' => $this->cleanForXml($record->profilType->name ?? ''),
            'email_verified_at' => $record->email_verified_at?->format('d/m/Y H:i') ?: 'Non vérifié',
            'created_at' => $record->created_at->format('d/m/Y H:i'),
            'updated_at' => $record->updated_at->format('d/m/Y H:i'),
        ];
    }

    protected function cleanForXml($string)
    {
        if ($string === null) {
            return '';
        }
        
        // Nettoyer les caractères problématiques pour XML
        $string = htmlspecialchars((string) $string, ENT_XML1, 'UTF-8');
        
        // Supprimer les caractères de contrôle (sauf tab, newline, return)
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        
        return $string;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre exportation des utilisateurs a été complétée et ' . Number::format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . ' exportées.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' ont échoué.';
        }

        return $body;
    }
}