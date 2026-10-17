<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

use DanPascu\FilamentEmailCampaigns\Contracts\EspDriver;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use InvalidArgumentException;

class EspDriverManager
{
    public static function driverFor(EmailProvider $provider): EspDriver
    {
        $providerKey = $provider->provider->value;
        $drivers = config('filament-email-campaigns.providers', []);

        if (! isset($drivers[$providerKey])) {
            throw new InvalidArgumentException("No ESP driver registered for provider [{$providerKey}].");
        }

        return new $drivers[$providerKey]($provider);
    }
}
