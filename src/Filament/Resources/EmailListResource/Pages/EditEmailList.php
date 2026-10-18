<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Actions\ExportSubscribersAction;
use DanPascu\FilamentEmailCampaigns\Filament\Actions\ImportSubscribersAction;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailList extends EditRecord
{
    protected static string $resource = EmailListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportSubscribersAction::make(),
            ExportSubscribersAction::make(),
            DeleteAction::make(),
        ];
    }
}
