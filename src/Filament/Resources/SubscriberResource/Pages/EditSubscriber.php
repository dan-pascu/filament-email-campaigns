<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriber extends EditRecord
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
