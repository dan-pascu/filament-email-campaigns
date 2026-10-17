<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources;

use BackedEnum;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource\Pages\CreateSubscriber;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource\Pages\EditSubscriber;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource\Pages\ListSubscribers;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament-email-campaigns::translations.nav.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.subscriber.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.subscriber.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.subscriber.plural_label');
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
                        ->unique(ignoreRecord: true)
                        ->columnSpanFull(),
                    TextInput::make('first_name')
                        ->label(__('filament-email-campaigns::translations.fields.first_name')),
                    TextInput::make('last_name')
                        ->label(__('filament-email-campaigns::translations.fields.last_name')),
                    TagsInput::make('tags')
                        ->label(__('filament-email-campaigns::translations.fields.tags'))
                        ->columnSpanFull(),
                    CheckboxList::make('lists')
                        ->label(__('filament-email-campaigns::translations.fields.lists'))
                        ->relationship('lists', 'name')
                        ->columnSpanFull(),
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
                TextColumn::make('first_name')
                    ->label(__('filament-email-campaigns::translations.fields.first_name'))
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label(__('filament-email-campaigns::translations.fields.last_name'))
                    ->searchable(),
                TextColumn::make('tags')
                    ->label(__('filament-email-campaigns::translations.fields.tags'))
                    ->badge(),
                TextColumn::make('lists.name')
                    ->label(__('filament-email-campaigns::translations.fields.lists'))
                    ->badge()
                    ->separator(','),
                TextColumn::make('created_at')
                    ->label(__('filament-email-campaigns::translations.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('lists')
                    ->relationship('lists', 'name')
                    ->label(__('filament-email-campaigns::translations.filters.list')),
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
            'index' => ListSubscribers::route('/'),
            'create' => CreateSubscriber::route('/create'),
            'edit' => EditSubscriber::route('/{record}/edit'),
        ];
    }
}
