<?php

namespace DanPascu\FilamentEmailCampaigns\Tests\Fixtures;

use DanPascu\FilamentEmailCampaigns\Contracts\SubscriberSource;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;

/**
 * Test double for a host-application subscriber source (e.g. event
 * attendees). Records are seeded per-test via the static $records property,
 * keyed by option.
 */
class FixtureSubscriberSource implements SubscriberSource
{
    /** @var array<string, SubscriberData[]> */
    public static array $records = [];

    public function key(): string
    {
        return 'fixture';
    }

    public function label(): string
    {
        return 'Fixture source';
    }

    public function options(int|string|null $tenantId): array
    {
        return array_combine(array_keys(static::$records), array_keys(static::$records));
    }

    public function count(int|string|null $tenantId, ?string $option): int
    {
        return count(static::$records[$option] ?? []);
    }

    public function records(int|string|null $tenantId, ?string $option): iterable
    {
        foreach (static::$records[$option] ?? [] as $record) {
            yield $record;
        }
    }
}
