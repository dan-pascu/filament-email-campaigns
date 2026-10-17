<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources;

use BackedEnum;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\Pages\CreateEmailList;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\Pages\EditEmailList;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\Pages\ListEmailLists;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\RelationManagers\SubscribersRelationManager;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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

class EmailListResource extends Resource
{
    protected static ?string $model = EmailList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament-email-campaigns::translations.nav.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.email_list.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.email_list.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.email_list.plural_label');
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
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('default_from_name')
                        ->label(__('filament-email-campaigns::translations.fields.default_from_name')),
                    TextInput::make('default_from_email')
                        ->label(__('filament-email-campaigns::translations.fields.default_from_email'))
                        ->email(),
                    TextInput::make('default_reply_to_email')
                        ->label(__('filament-email-campaigns::translations.fields.default_reply_to_email'))
                        ->email(),
                    Select::make('email_provider_id')
                        ->label(__('filament-email-campaigns::translations.fields.email_provider'))
                        ->relationship('emailProvider', 'name')
                        ->searchable()
                        ->preload(),
                    Toggle::make('requires_confirmation')
                        ->label(__('filament-email-campaigns::translations.fields.require_double_opt_in'))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-email-campaigns::translations.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscribers_count')
                    ->label(__('filament-email-campaigns::translations.fields.subscribers'))
                    ->counts('subscribers')
                    ->sortable(),
                TextColumn::make('emailProvider.name')
                    ->label(__('filament-email-campaigns::translations.fields.provider'))
                    ->placeholder(__('filament-email-campaigns::translations.placeholders.none')),
                IconColumn::make('requires_confirmation')
                    ->label(__('filament-email-campaigns::translations.fields.double_opt_in'))
                    ->boolean(),
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

    public static function getRelations(): array
    {
        return [
            SubscribersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailLists::route('/'),
            'create' => CreateEmailList::route('/create'),
            'edit' => EditEmailList::route('/{record}/edit'),
        ];
    }
}
