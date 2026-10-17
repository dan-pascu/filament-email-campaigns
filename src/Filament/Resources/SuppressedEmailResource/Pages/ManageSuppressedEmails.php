<?php

namespace DanPascu\FilamentEmailCampaigns\Filament\Resources\SuppressedEmailResource\Pages;

use DanPascu\FilamentEmailCampaigns\Filament\Resources\SuppressedEmailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuppressedEmails extends ManageRecords
{
    protected static string $resource = SuppressedEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
