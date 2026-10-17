<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use DanPascu\FilamentEmailCampaigns\Services\CampaignStatsAggregator;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class CampaignReport extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CampaignResource::class;

    protected string $view = 'filament-email-campaigns::filament.pages.campaign-report';

    public array $stats = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->stats = app(CampaignStatsAggregator::class)->summarize($this->record);
    }

    public function getTitle(): string
    {
        return __('filament-email-campaigns::translations.pages.campaign_report.title', [
            'name' => $this->record->name,
        ]);
    }
}
