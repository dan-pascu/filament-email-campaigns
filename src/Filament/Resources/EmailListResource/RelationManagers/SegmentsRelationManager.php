<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\RelationManagers;

use DanPascu\FilamentEmailCampaigns\Services\SegmentPreviewer;
use DanPascu\FilamentEmailCampaigns\Support\SegmentConditionsForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SegmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'segments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('filament-email-campaigns::translations.fields.name'))
                ->required()
                ->maxLength(255),
            Select::make('conditions.match')
                ->label(__('filament-email-campaigns::translations.fields.segment_match'))
                ->options($this->matchOptions())
                ->default('all')
                ->live()
                ->required(),
            Repeater::make('conditions.groups')
                ->label(__('filament-email-campaigns::translations.fields.segment_groups'))
                ->addActionLabel(__('filament-email-campaigns::translations.actions.add_segment_group'))
                ->defaultItems(1)
                ->live()
                ->schema([
                    Select::make('match')
                        ->label(__('filament-email-campaigns::translations.fields.segment_match'))
                        ->options($this->matchOptions())
                        ->default('all')
                        ->required(),
                    Repeater::make('rules')
                        ->label(__('filament-email-campaigns::translations.fields.segment_rules'))
                        ->addActionLabel(__('filament-email-campaigns::translations.actions.add_segment_rule'))
                        ->defaultItems(1)
                        ->live()
                        ->schema([
                            Select::make('field')
                                ->label(__('filament-email-campaigns::translations.fields.segment_field'))
                                ->options($this->fieldOptions())
                                ->default('email')
                                ->live()
                                ->required(),
                            TextInput::make('field_custom')
                                ->label(__('filament-email-campaigns::translations.fields.segment_custom_field'))
                                ->helperText(__('filament-email-campaigns::translations.helper_text.segment_custom_field'))
                                ->visible(fn (Get $get): bool => $get('field') === 'custom')
                                ->required(fn (Get $get): bool => $get('field') === 'custom'),
                            Select::make('operator')
                                ->label(__('filament-email-campaigns::translations.fields.segment_operator'))
                                ->options(fn (Get $get): array => $this->operatorOptions($get('field')))
                                ->default('equals')
                                ->visible(fn (Get $get): bool => $get('field') !== 'tags'),
                            TextInput::make('value')
                                ->label(__('filament-email-campaigns::translations.fields.segment_value'))
                                ->helperText(fn (Get $get): ?string => $get('field') === 'subscribed_at'
                                    ? __('filament-email-campaigns::translations.helper_text.segment_date_value')
                                    : null)
                                ->required(),
                        ])
                        ->columns(1),
                ]),
            Section::make(__('filament-email-campaigns::translations.fields.segment_preview'))
                ->schema([
                    Placeholder::make('preview')
                        ->label('')
                        ->content(function (Get $get): string {
                            $conditions = SegmentConditionsForm::normalize(['conditions' => [
                                'match' => $get('conditions.match') ?? 'all',
                                'groups' => $get('conditions.groups') ?? [],
                            ]])['conditions'];

                            $result = app(SegmentPreviewer::class)->preview($this->getOwnerRecord(), $conditions);

                            if ($result['count'] === 0) {
                                return __('filament-email-campaigns::translations.notifications.segment_preview_empty');
                            }

                            return __('filament-email-campaigns::translations.notifications.segment_preview_count', ['count' => $result['count']])
                                .' '.implode(', ', $result['sample']);
                        }),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-email-campaigns::translations.fields.name'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('filament-email-campaigns::translations.fields.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => SegmentConditionsForm::normalize($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => SegmentConditionsForm::denormalize($data))
                    ->mutateFormDataUsing(fn (array $data): array => SegmentConditionsForm::normalize($data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function matchOptions(): array
    {
        return [
            'all' => __('filament-email-campaigns::translations.segment_match.all'),
            'any' => __('filament-email-campaigns::translations.segment_match.any'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldOptions(): array
    {
        return [
            'email' => __('filament-email-campaigns::translations.fields.email'),
            'first_name' => __('filament-email-campaigns::translations.fields.first_name'),
            'last_name' => __('filament-email-campaigns::translations.fields.last_name'),
            'tags' => __('filament-email-campaigns::translations.fields.tags'),
            'subscribed_at' => __('filament-email-campaigns::translations.fields.subscribed_at'),
            'custom' => __('filament-email-campaigns::translations.segment_field.custom'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function operatorOptions(?string $field): array
    {
        if ($field === 'subscribed_at') {
            return [
                'before' => __('filament-email-campaigns::translations.segment_operator.before'),
                'after' => __('filament-email-campaigns::translations.segment_operator.after'),
                'equals' => __('filament-email-campaigns::translations.segment_operator.equals'),
            ];
        }

        return [
            'equals' => __('filament-email-campaigns::translations.segment_operator.equals'),
            'contains' => __('filament-email-campaigns::translations.segment_operator.contains'),
            'starts_with' => __('filament-email-campaigns::translations.segment_operator.starts_with'),
            'ends_with' => __('filament-email-campaigns::translations.segment_operator.ends_with'),
        ];
    }
}
