<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailLists extends ListRecords
{
    protected static string $resource = EmailListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
