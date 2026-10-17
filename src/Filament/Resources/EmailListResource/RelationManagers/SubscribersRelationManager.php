<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\RelationManagers;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                DetachAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
