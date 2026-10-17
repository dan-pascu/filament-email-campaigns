<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources;

use BackedEnum;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages\CampaignReport;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages\EditCampaign;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use DanPascu\FilamentEmailCampaigns\Models\Campaign;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament-email-campaigns::translations.nav.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.campaign.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.campaign.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-email-campaigns::translations.resources.campaign.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make(__('filament-email-campaigns::translations.steps.content'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-email-campaigns::translations.fields.name'))
                            ->required()
                            ->helperText(__('filament-email-campaigns::translations.helper_text.internal_campaign_name'))
                            ->columnSpanFull(),
                        TextInput::make('subject')
                            ->label(__('filament-email-campaigns::translations.fields.subject'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('from_name')
                            ->label(__('filament-email-campaigns::translations.fields.from_name')),
                        TextInput::make('from_email')
                            ->label(__('filament-email-campaigns::translations.fields.from_email'))
                            ->email(),
                        TextInput::make('reply_to_email')
                            ->label(__('filament-email-campaigns::translations.fields.reply_to_email'))
                            ->email(),
                        RichEditor::make('html_content')
                            ->label(__('filament-email-campaigns::translations.fields.content'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Step::make(__('filament-email-campaigns::translations.steps.recipients'))
                    ->schema([
                        Select::make('email_list_id')
                            ->label(__('filament-email-campaigns::translations.fields.list'))
                            ->options(fn () => EmailList::query()->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('segment_id', null)),
                        Select::make('segment_id')
                            ->label(__('filament-email-campaigns::translations.fields.segment_optional'))
                            ->options(fn (callable $get) => EmailList::find($get('email_list_id'))?->segments()->pluck('name', 'id') ?? [])
                            ->helperText(__('filament-email-campaigns::translations.helper_text.segment_empty_sends_to_everyone')),
                        Select::make('email_provider_id')
                            ->label(__('filament-email-campaigns::translations.fields.email_provider_override_optional'))
                            ->relationship('emailProvider', 'name')
                            ->helperText(__('filament-email-campaigns::translations.helper_text.provider_defaults_to_list')),
                    ]),

                Step::make(__('filament-email-campaigns::translations.steps.ab_testing'))
                    ->schema([
                        Toggle::make('is_ab_test')
                            ->label(__('filament-email-campaigns::translations.fields.run_as_ab_test'))
                            ->live(),
                        TextInput::make('ab_test_sample_percentage')
                            ->label(__('filament-email-campaigns::translations.fields.sample_size_percent'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(20)
                            ->visible(fn (callable $get) => $get('is_ab_test')),
                        Select::make('ab_test_winning_metric')
                            ->label(__('filament-email-campaigns::translations.fields.pick_winner_by'))
                            ->options([
                                'opens' => __('filament-email-campaigns::translations.fields.open_rate'),
                                'clicks' => __('filament-email-campaigns::translations.fields.click_rate'),
                            ])
                            ->default('opens')
                            ->visible(fn (callable $get) => $get('is_ab_test')),
                        Repeater::make('variants')
                            ->relationship('variants')
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-email-campaigns::translations.fields.name'))
                                    ->required(),
                                TextInput::make('subject')
                                    ->label(__('filament-email-campaigns::translations.fields.subject'))
                                    ->helperText(__('filament-email-campaigns::translations.helper_text.variant_subject_empty_uses_main')),
                                RichEditor::make('html_content')
                                    ->label(__('filament-email-campaigns::translations.fields.content'))
                                    ->helperText(__('filament-email-campaigns::translations.helper_text.variant_content_empty_uses_main')),
                            ])
                            ->columns(2)
                            ->minItems(2)
                            ->visible(fn (callable $get) => $get('is_ab_test'))
                            ->columnSpanFull(),
                    ]),

                Step::make(__('filament-email-campaigns::translations.steps.schedule'))
                    ->schema([
                        DateTimePicker::make('scheduled_at')
                            ->label(__('filament-email-campaigns::translations.fields.send_at'))
                            ->helperText(__('filament-email-campaigns::translations.helper_text.schedule_empty_keeps_draft'))
                            ->native(false),
                    ]),
            ])->columnSpanFull(),
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
                TextColumn::make('status')
                    ->label(__('filament-email-campaigns::translations.fields.status'))
                    ->badge(),
                TextColumn::make('scheduled_at')
                    ->label(__('filament-email-campaigns::translations.fields.scheduled_at'))
                    ->dateTime()
                    ->placeholder(__('filament-email-campaigns::translations.placeholders.none')),
                TextColumn::make('sent_at')
                    ->label(__('filament-email-campaigns::translations.fields.sent_at'))
                    ->dateTime()
                    ->placeholder(__('filament-email-campaigns::translations.placeholders.none')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(CampaignStatus::cases())->mapWithKeys(
                        fn (CampaignStatus $status) => [$status->value => $status->getLabel()],
                    )),
            ])
            ->recordActions([
                Action::make('report')
                    ->label(__('filament-email-campaigns::translations.actions.report'))
                    ->icon(Heroicon::OutlinedChartBar)
                    ->url(fn (Campaign $record) => CampaignReport::getUrl(['record' => $record])),
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
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
            'report' => CampaignReport::route('/{record}/report'),
        ];
    }
}
