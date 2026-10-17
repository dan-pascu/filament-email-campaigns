<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailList extends EditRecord
{
    protected static string $resource = EmailListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
