<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Actions;

use DanPascu\FilamentEmailCampaigns\Actions\ImportSubscribersActionHandler;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\SubscriberSources\CsvSubscriberSource;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberSourceRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ImportSubscribersAction
{
    public static function make(): Action
    {
        return Action::make('importSubscribers')
            ->label(__('filament-email-campaigns::translations.actions.import_subscribers'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->schema(fn (EmailList $record): array => [
                Select::make('source')
                    ->label(__('filament-email-campaigns::translations.fields.source'))
                    ->options(fn (): array => collect(SubscriberSourceRegistry::all())
                        ->mapWithKeys(fn ($source) => [$source->key() => $source->label()])
                        ->all())
                    ->default('csv')
                    ->live()
                    ->required(),
                Select::make('option')
                    ->label(__('filament-email-campaigns::translations.fields.source_option'))
                    ->options(function (callable $get) use ($record): array {
                        $source = SubscriberSourceRegistry::get($get('source') ?? '');

                        if (! $source) {
                            return [];
                        }

                        $tenantId = $record->tenant_id;

                        return collect($source->options($tenantId))
                            ->mapWithKeys(fn (string $label, string $value) => [
                                $value => "{$label} ({$source->count($tenantId, $value)})",
                            ])
                            ->all();
                    })
                    ->visible(fn (callable $get): bool => $get('source') !== 'csv' && $get('source') !== null)
                    ->required(fn (callable $get): bool => $get('source') !== 'csv' && $get('source') !== null),
                FileUpload::make('csv_file')
                    ->label(__('filament-email-campaigns::translations.fields.csv_file'))
                    ->visible(fn (callable $get): bool => $get('source') === 'csv')
                    ->required(fn (callable $get): bool => $get('source') === 'csv')
                    ->disk(config('filament-email-campaigns.storage.disk', 'local'))
                    ->directory('email-campaigns-imports'),
                TextInput::make('csv_email_column')
                    ->label(__('filament-email-campaigns::translations.fields.csv_email_column'))
                    ->default('email')
                    ->visible(fn (callable $get): bool => $get('source') === 'csv'),
                TextInput::make('csv_first_name_column')
                    ->label(__('filament-email-campaigns::translations.fields.csv_first_name_column'))
                    ->default('first_name')
                    ->visible(fn (callable $get): bool => $get('source') === 'csv'),
                TextInput::make('csv_last_name_column')
                    ->label(__('filament-email-campaigns::translations.fields.csv_last_name_column'))
                    ->default('last_name')
                    ->visible(fn (callable $get): bool => $get('source') === 'csv'),
                TextInput::make('csv_tags_column')
                    ->label(__('filament-email-campaigns::translations.fields.csv_tags_column'))
                    ->default('tags')
                    ->visible(fn (callable $get): bool => $get('source') === 'csv'),
                Select::make('status')
                    ->label(__('filament-email-campaigns::translations.fields.status'))
                    ->options(SubscriptionStatus::class)
                    ->default(fn (): string => $record->requires_confirmation
                        ? SubscriptionStatus::Unconfirmed->value
                        : SubscriptionStatus::Subscribed->value)
                    ->required(),
                TagsInput::make('tags')
                    ->label(__('filament-email-campaigns::translations.fields.tags')),
                Checkbox::make('consent')
                    ->label(__('filament-email-campaigns::translations.fields.import_consent'))
                    ->required()
                    ->accepted(),
            ])
            ->action(function (array $data, EmailList $record): void {
                static::handle($record, $data);

                Notification::make()
                    ->title(__('filament-email-campaigns::translations.notifications.import_started'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function handle(EmailList $list, array $data): void
    {
        $sourceKey = $data['source'];

        if ($sourceKey === 'csv') {
            $option = CsvSubscriberSource::encodeOption(
                path: $data['csv_file'],
                mapping: [
                    'email' => $data['csv_email_column'] ?? 'email',
                    'first_name' => $data['csv_first_name_column'] ?? 'first_name',
                    'last_name' => $data['csv_last_name_column'] ?? 'last_name',
                    'tags' => $data['csv_tags_column'] ?? 'tags',
                ],
                disk: config('filament-email-campaigns.storage.disk', 'local'),
            );
        } else {
            $option = $data['option'] ?? null;
        }

        app(ImportSubscribersActionHandler::class)(
            list: $list,
            sourceKey: $sourceKey,
            option: $option,
            status: SubscriptionStatus::from($data['status']),
            tags: $data['tags'] ?? [],
            notifiable: Auth::user(),
        );
    }
}
