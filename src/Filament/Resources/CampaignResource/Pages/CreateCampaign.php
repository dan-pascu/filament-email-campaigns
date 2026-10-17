<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['scheduled_at'])) {
            $data['status'] = CampaignStatus::Scheduled->value;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Every campaign is sent through a variant, even when it isn't an
        // A/B test, so the sending pipeline never has to special-case it.
        if ($this->record->variants()->doesntExist()) {
            $this->record->variants()->create(['name' => 'A']);
        }
    }
}
