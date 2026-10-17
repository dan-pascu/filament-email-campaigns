<?php

use DanPascu\FilamentEmailCampaigns\Http\Controllers\EspWebhookController;
use DanPascu\FilamentEmailCampaigns\Http\Controllers\TrackClickController;
use DanPascu\FilamentEmailCampaigns\Http\Controllers\TrackOpenController;
use DanPascu\FilamentEmailCampaigns\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('filament-email-campaigns.route.prefix', 'email-campaigns'))->group(function (): void {
    Route::get('track/open/{send}', TrackOpenController::class)
        ->middleware('signed')
        ->name('filament-email-campaigns.track.open');

    Route::get('track/click/{send}/{link}', TrackClickController::class)
        ->middleware('signed')
        ->name('filament-email-campaigns.track.click');

    Route::get('unsubscribe/{list}/{subscriber}', UnsubscribeController::class)
        ->middleware('signed')
        ->name('filament-email-campaigns.unsubscribe');

    Route::post('webhooks/{provider}', EspWebhookController::class)
        ->name('filament-email-campaigns.webhooks');
});
