<?php

use DanPascu\FilamentEmailCampaigns\Filament\Actions\ExportSubscribersAction;
use DanPascu\FilamentEmailCampaigns\Filament\Actions\ImportSubscribersAction;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\AutomationResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\CampaignResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailListResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\EmailProviderResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SubscriberResource;
use DanPascu\FilamentEmailCampaigns\Filament\Resources\SuppressedEmailResource;
use Filament\Actions\Action;
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
    SuppressedEmailResource::class,
]);

it('builds the import/export subscriber actions without error', function () {
    expect(ImportSubscribersAction::make())->toBeInstanceOf(Action::class);
    expect(ExportSubscribersAction::make())->toBeInstanceOf(Action::class);
});
