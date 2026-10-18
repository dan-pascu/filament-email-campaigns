<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

use DanPascu\FilamentEmailCampaigns\Contracts\SubscriberSource;
use DanPascu\FilamentEmailCampaigns\SubscriberSources\CsvSubscriberSource;

class SubscriberSourceRegistry
{
    /** @var array<string, SubscriberSource> */
    protected static array $resolved = [];

    /**
     * @param  array<string|SubscriberSource>  $sources
     */
    public static function register(array $sources): void
    {
        foreach ($sources as $source) {
            $instance = static::resolve($source);
            static::$resolved[$instance->key()] = $instance;
        }
    }

    public static function reset(): void
    {
        static::$resolved = [];
    }

    /**
     * @return array<string, SubscriberSource>
     */
    public static function all(): array
    {
        $sources = static::$resolved;

        foreach (config('filament-email-campaigns.subscriber_sources', []) as $source) {
            $instance = static::resolve($source);
            $sources[$instance->key()] ??= $instance;
        }

        $csv = app(CsvSubscriberSource::class);
        $sources[$csv->key()] ??= $csv;

        return $sources;
    }

    public static function get(string $key): ?SubscriberSource
    {
        return static::all()[$key] ?? null;
    }

    protected static function resolve(string|SubscriberSource $source): SubscriberSource
    {
        return $source instanceof SubscriberSource ? $source : app($source);
    }
}
