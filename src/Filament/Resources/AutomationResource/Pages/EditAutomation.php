<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomation extends EditRecord
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
