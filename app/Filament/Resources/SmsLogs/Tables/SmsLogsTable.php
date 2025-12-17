<?php

namespace App\Filament\Resources\SmsLogs\Tables;

use App\Models\SmsLog;
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SmsLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phone_number')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('recipient_name')
                    ->label('Destinataire')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (SmsLog $record) => $record->recipient?->numero_compte ?? 'N/A'),
                    
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn (SmsLog $record) => $record->message),
                    
                TextColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'delivered',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'gray' => 'undeliverable',
                        'primary' => 'sent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sent' => 'Envoyé',
                        'delivered' => 'Livré',
                        'failed' => 'Échoué',
                        'pending' => 'En attente',
                        'undeliverable' => 'Non livrable',
                        default => $state,
                    }),
                    
                TextColumn::make('delivery_status')
                    ->label('Livraison')
                    ->colors([
                        'success' => 'Delivered',
                        'warning' => 'Pending',
                        'danger' => 'Failed',
                        'gray' => 'Undeliverable',
                    ])
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                    
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'transaction',
                        'success' => 'alert',
                        'warning' => 'marketing',
                        'info' => 'otp',
                        'gray' => 'reminder',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'transaction' => 'Transaction',
                        'alert' => 'Alerte',
                        'marketing' => 'Marketing',
                        'otp' => 'OTP',
                        'reminder' => 'Rappel',
                        default => $state,
                    }),
                    
                TextColumn::make('cost')
                    ->label('Coût')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('sent_at')
                    ->label('Date d\'envoi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'sent' => 'Envoyé',
                        'delivered' => 'Livré',
                        'failed' => 'Échoué',
                        'pending' => 'En attente',
                        'undeliverable' => 'Non livrable',
                    ]),
                    
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'transaction' => 'Transaction',
                        'alert' => 'Alerte',
                        'marketing' => 'Marketing',
                        'otp' => 'OTP',
                        'reminder' => 'Rappel',
                    ]),
                    
                Filter::make('sent_at')
                    ->label('Date d\'envoi'),
                    
                SelectFilter::make('compte_id')
                    ->label('Compte')
                    ->relationship('compte', 'numero_compte')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('resend')
                    ->label('Renvoyer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (SmsLog $record) {
                        try {
                            $smsService = app(SmsService::class);
                            $result = $smsService->sendTransactionSMS(
                                $record->phone_number,
                                $record->message,
                                $record->uid . '_resend'
                            );
                            
                            if ($result['status'] === 'S') {
                                // Créer un nouveau log
                                SmsLog::create([
                                    'phone_number' => $record->phone_number,
                                    'message' => $record->message,
                                    'message_id' => $result['message_id'],
                                    'status' => SmsLog::STATUS_SENT,
                                    'type' => $record->type,
                                    'uid' => $record->uid . '_resend',
                                    'response_data' => $result,
                                    'user_id' => Auth::id(),
                                    'compte_id' => $record->compte_id,
                                    'mouvement_id' => $record->mouvement_id,
                                    'sent_at' => now(),
                                ]);
                                
                                Notification::make()
                                    ->title('SMS renvoyé avec succès')
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception($result['remarks'] ?? 'Échec de l\'envoi');
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors du renvoi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (SmsLog $record) => $record->status !== 'pending'),
                    
                Action::make('check_status')
                    ->label('Vérifier statut')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->action(function (SmsLog $record) {
                        try {
                            $smsService = app(SmsService::class);
                            $status = $smsService->checkDeliveryStatus(
                                $record->message_id,
                                $record->uid
                            );
                            
                            $record->update([
                                'delivery_status' => $status['DLRStatus'] ?? null,
                                'status' => $status['DLRStatus'] === 'Delivered' ? 
                                    SmsLog::STATUS_DELIVERED : $record->status,
                                'response_data' => array_merge($record->response_data ?? [], $status),
                            ]);
                            
                            Notification::make()
                                ->title('Statut vérifié')
                                ->body('Statut: ' . ($status['DLRStatus'] ?? 'Inconnu'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur de vérification')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (SmsLog $record) => !empty($record->message_id)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}