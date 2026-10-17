<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources;

use BackedEnum;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SuppressedEmailResource\Pages\ManageSuppressedEmails;
use DanPascu\FilamentEmailCampaigns\Models\SuppressedEmail;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SuppressedEmailResource extends Resource
{
    protected static ?string $model = SuppressedEmail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament-email-campaigns::translations.nav.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.suppressed_email.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.suppressed_email.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.suppressed_email.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->components([
                    TextInput::make('email')
                        ->label(__('filament-email-campaigns::translations.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('reason')
                        ->label(__('filament-email-campaigns::translations.fields.reason')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(__('filament-email-campaigns::translations.fields.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label(__('filament-email-campaigns::translations.fields.reason'))
                    ->placeholder(__('filament-email-campaigns::translations.placeholders.none')),
                TextColumn::make('created_at')
                    ->label(__('filament-email-campaigns::translations.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppressedEmails::route('/'),
        ];
    }
}
