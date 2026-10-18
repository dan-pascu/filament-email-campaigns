<?php

namespace DanPascu\FilamentEmailCampaigns\Contracts;

use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;

/**
 * A pluggable origin for subscribers to import into a list: a CSV upload,
 * or anything the host application wants to offer (event attendees, a CRM
 * export, ...). Registered via config('filament-email-campaigns.subscriber_sources')
 * or FilamentEmailCampaignsPlugin::make()->subscriberSources([...]).
 */
interface SubscriberSource
{
    /**
     * Stable identifier stored on the import job; must be unique across
     * every registered source.
     */
    public function key(): string;

    public function label(): string;

    /**
     * Sub-choices within this source (e.g. "Event: Conference 2026"), shown
     * in a select before importing. Return [] when the source has none
     * (like the built-in CSV source, which asks for a file instead).
     *
     * @return array<string, string> option value => label
     */
    public function options(int|string|null $tenantId): array;

    /**
     * How many records {@see records()} would yield for this option,
     * shown next to it in the picker.
     */
    public function count(int|string|null $tenantId, ?string $option): int;

    /**
     * @return iterable<SubscriberData>
     */
    public function records(int|string|null $tenantId, ?string $option): iterable;
}
