<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\RelationManagers;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Services\ConfirmationMailer;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscribersRelationManager extends RelationManager
{
    protected static string $relationship = 'subscribers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->label(__('filament-email-campaigns::translations.fields.email'))
                ->email()
                ->required(),
            TextInput::make('first_name')
                ->label(__('filament-email-campaigns::translations.fields.first_name')),
            TextInput::make('last_name')
                ->label(__('filament-email-campaigns::translations.fields.last_name')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')
                    ->label(__('filament-email-campaigns::translations.fields.email'))
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label(__('filament-email-campaigns::translations.fields.first_name')),
                TextColumn::make('last_name')
                    ->label(__('filament-email-campaigns::translations.fields.last_name')),
                SelectColumn::make('pivot.status')
                    ->label(__('filament-email-campaigns::translations.fields.status'))
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                        fn (SubscriptionStatus $status) => [$status->value => $status->getLabel()],
                    )),
            ])
            ->headerActions([
                AttachAction::make()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('status')
                            ->label(__('filament-email-campaigns::translations.fields.status'))
                            ->options(collect(SubscriptionStatus::cases())->mapWithKeys(
                                fn (SubscriptionStatus $status) => [$status->value => $status->getLabel()],
                            ))
                            ->default(SubscriptionStatus::Subscribed->value)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                Action::make('resendConfirmation')
                    ->label(__('filament-email-campaigns::translations.actions.resend_confirmation'))
                    ->visible(fn ($record): bool => $record->pivot->status === SubscriptionStatus::Unconfirmed)
                    ->action(function ($record): void {
                        app(ConfirmationMailer::class)->send($this->getOwnerRecord(), $record);

                        Notification::make()
                            ->title(__('filament-email-campaigns::translations.notifications.confirmation_resent'))
                            ->success()
                            ->send();
                    }),
                DetachAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
