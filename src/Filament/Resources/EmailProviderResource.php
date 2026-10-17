<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources;

use BackedEnum;
use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource\Pages\CreateEmailProvider;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource\Pages\EditEmailProvider;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource\Pages\ListEmailProviders;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EmailProviderResource extends Resource
{
    protected static ?string $model = EmailProvider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament-email-campaigns::translations.nav.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.email_provider.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.email_provider.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.email_provider.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label(__('filament-email-campaigns::translations.fields.name'))
                        ->required()
                        ->columnSpanFull(),
                    Select::make('provider')
                        ->label(__('filament-email-campaigns::translations.fields.provider'))
                        ->options(collect(EspProvider::cases())->mapWithKeys(
                            fn (EspProvider $provider) => [$provider->value => $provider->getLabel()],
                        ))
                        ->default(EspProvider::Mailgun->value)
                        ->required()
                        ->live(),
                    Toggle::make('is_default')
                        ->label(__('filament-email-campaigns::translations.fields.is_default')),
                    TextInput::make('credentials.domain')
                        ->label(__('filament-email-campaigns::translations.fields.sending_domain'))
                        ->placeholder(__('filament-email-campaigns::translations.placeholders.sending_domain_example'))
                        ->required(),
                    TextInput::make('credentials.api_key')
                        ->label(__('filament-email-campaigns::translations.fields.private_api_key'))
                        ->password()
                        ->revealable()
                        ->required(),
                    Select::make('credentials.region')
                        ->label(__('filament-email-campaigns::translations.fields.region'))
                        ->options(['us' => 'US', 'eu' => 'EU'])
                        ->default('us'),
                    TextInput::make('webhook_signing_secret')
                        ->label(__('filament-email-campaigns::translations.fields.webhook_signing_key'))
                        ->password()
                        ->revealable()
                        ->columnSpanFull()
                        ->helperText(__('filament-email-campaigns::translations.helper_text.webhook_signing_key')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-email-campaigns::translations.fields.name'))
                    ->searchable(),
                TextColumn::make('provider')
                    ->label(__('filament-email-campaigns::translations.fields.provider')),
                IconColumn::make('is_default')
                    ->label(__('filament-email-campaigns::translations.fields.is_default'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('filament-email-campaigns::translations.fields.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailProviders::route('/'),
            'create' => CreateEmailProvider::route('/create'),
            'edit' => EditEmailProvider::route('/{record}/edit'),
        ];
    }
}
