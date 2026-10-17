<?php

use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource;
use Filament\Schemas\Schema;

it('builds every resource form schema without error', function (string $resource) {
    $schema = $resource::form(Schema::make());

    expect($schema)->toBeInstanceOf(Schema::class);
})->with([
    EmailListResource::class,
    SubscriberResource::class,
    CampaignResource::class,
    EmailProviderResource::class,
    AutomationResource::class,
]);
