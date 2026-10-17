<?php

use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;

it('translates enum labels into Romanian', function () {
    app()->setLocale('ro');

    expect(SubscriptionStatus::Subscribed->getLabel())->toBe('Abonat');
    expect(CampaignStatus::Sending->getLabel())->toBe('Se trimite');
});

it('falls back to English enum labels by default', function () {
    app()->setLocale('en');

    expect(SubscriptionStatus::Subscribed->getLabel())->toBe('Subscribed');
    expect(CampaignStatus::Sending->getLabel())->toBe('Sending');
});
