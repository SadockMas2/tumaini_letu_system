<?php

namespace App\Filament\Resources\SmsCampaigns\Tables;

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

class SmsCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('telephone')
                    ->label('Destinataire')
                    ->searchable(),
                    
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(40)
                    ->tooltip(fn (SmsLog $record) => $record->message),
                    
                TextColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'sent',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'sent' => 'Envoyé',
                        'pending' => 'En attente',
                        'failed' => 'Échoué',
                        default => $state,
                    }),
                    
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'transaction',
                        'success' => 'campaign',
                    ]),
                    
                TextColumn::make('cost')
                    ->label('Coût')
                    ->money('USD'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'transaction' => 'Transaction',
                        'campaign' => 'Campagne',
                    ]),
                    
                Filter::make('created_at')
                    ->label('Date d\'envoi'),
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
                            
                            Notification::make()
                                ->title('SMS renvoyé')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (SmsLog $record) => $record->status === 'failed'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),

                
           ])
            ->defaultSort('created_at', 'desc');
    }
}
