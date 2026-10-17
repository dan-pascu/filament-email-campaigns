<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
