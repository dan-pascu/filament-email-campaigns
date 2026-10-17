<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailProvider extends EditRecord
{
    protected static string $resource = EmailProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
