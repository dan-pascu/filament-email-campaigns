<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources;

use BackedEnum;
use DanPascu\FilamentEmailCampaigns\Enums\AutomationActionType;
use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource\Pages\CreateAutomation;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource\Pages\EditAutomation;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource\Pages\ListAutomations;
use DanPascu\FilamentEmailCampaigns\Models\Automation;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
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

class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament-email-campaigns::translations.nav.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.automation.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.automation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.automation.plural_label');
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
                    Select::make('email_list_id')
                        ->label(__('filament-email-campaigns::translations.fields.list'))
                        ->relationship('emailList', 'name')
                        ->required(),
                    Toggle::make('is_enabled')
                        ->label(__('filament-email-campaigns::translations.fields.is_enabled'))
                        ->default(true),
                ]),

            Section::make(__('filament-email-campaigns::translations.sections.trigger'))
                ->description(__('filament-email-campaigns::translations.helper_text.automation_trigger'))
                ->components([
                    Repeater::make('triggers')
                        ->relationship('triggers')
                        ->schema([
                            Select::make('type')
                                ->label(__('filament-email-campaigns::translations.fields.type'))
                                ->options(collect(AutomationTriggerType::cases())->mapWithKeys(
                                    fn (AutomationTriggerType $type) => [$type->value => $type->getLabel()],
                                ))
                                ->required()
                                ->live(),
                            TextInput::make('config.tag')
                                ->label(__('filament-email-campaigns::translations.fields.tag'))
                                ->visible(fn (callable $get) => in_array($get('type'), [
                                    AutomationTriggerType::TagAdded->value,
                                    AutomationTriggerType::TagRemoved->value,
                                ], true)),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->hiddenLabel(),
                ]),

            Section::make(__('filament-email-campaigns::translations.sections.actions'))
                ->description(__('filament-email-campaigns::translations.helper_text.automation_actions'))
                ->components([
                    Repeater::make('actions')
                        ->relationship('actions')
                        ->schema([
                            TextInput::make('order')
                                ->label(__('filament-email-campaigns::translations.fields.order'))
                                ->numeric()
                                ->default(0)
                                ->required(),
                            Select::make('type')
                                ->label(__('filament-email-campaigns::translations.fields.type'))
                                ->options(collect(AutomationActionType::cases())->mapWithKeys(
                                    fn (AutomationActionType $type) => [$type->value => $type->getLabel()],
                                ))
                                ->required()
                                ->live(),
                            TextInput::make('config.minutes')
                                ->label(__('filament-email-campaigns::translations.fields.minutes_to_wait'))
                                ->numeric()
                                ->visible(fn (callable $get) => $get('type') === AutomationActionType::Wait->value),
                            TextInput::make('config.tag')
                                ->label(__('filament-email-campaigns::translations.fields.tag'))
                                ->visible(fn (callable $get) => in_array($get('type'), [
                                    AutomationActionType::AddTag->value,
                                    AutomationActionType::RemoveTag->value,
                                ], true)),
                            TextInput::make('config.subject')
                                ->label(__('filament-email-campaigns::translations.fields.subject'))
                                ->visible(fn (callable $get) => $get('type') === AutomationActionType::SendEmail->value)
                                ->columnSpanFull(),
                            RichEditor::make('config.html_content')
                                ->label(__('filament-email-campaigns::translations.fields.content'))
                                ->visible(fn (callable $get) => $get('type') === AutomationActionType::SendEmail->value)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->hiddenLabel(),
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
                TextColumn::make('emailList.name')
                    ->label(__('filament-email-campaigns::translations.fields.list')),
                IconColumn::make('is_enabled')
                    ->label(__('filament-email-campaigns::translations.fields.is_enabled'))
                    ->boolean(),
                TextColumn::make('actions_count')
                    ->counts('actions')
                    ->label(__('filament-email-campaigns::translations.fields.steps')),
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
            'index' => ListAutomations::route('/'),
            'create' => CreateAutomation::route('/create'),
            'edit' => EditAutomation::route('/{record}/edit'),
        ];
    }
}
