<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
