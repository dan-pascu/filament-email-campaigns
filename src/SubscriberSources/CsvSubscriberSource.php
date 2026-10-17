<?php

namespace DanPascu\FilamentEmailCampaigns\SubscriberSources;

use DanPascu\FilamentEmailCampaigns\Contracts\SubscriberSource;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;
use Illuminate\Support\Facades\Storage;

/**
 * Built-in source reading an uploaded CSV off a Storage disk. The "option"
 * passed around the import pipeline is a small JSON payload (path, column
 * mapping, disk) rather than a selectable value, since this source has
 * nothing to pick from a list.
 */
class CsvSubscriberSource implements SubscriberSource
{
    public function key(): string
    {
        return 'csv';
    }

    public function label(): string
    {
        return __('filament-email-campaigns::translations.subscriber_sources.csv.label');
    }

    public function options(int|string|null $tenantId): array
    {
        return [];
    }

    public function count(int|string|null $tenantId, ?string $option): int
    {
        return count(iterator_to_array($this->records($tenantId, $option), false));
    }

    public function records(int|string|null $tenantId, ?string $option): iterable
    {
        if ($option === null) {
            return;
        }

        $decoded = $this->decodeOption($option);

        if ($decoded === null) {
            return;
        }

        [$path, $mapping, $disk] = $decoded;

        $stream = Storage::disk($disk)->readStream($path);

        if ($stream === null || $stream === false) {
            return;
        }

        yield from $this->parse($stream, $mapping);

        fclose($stream);
    }

    /**
     * @param  resource  $stream
     * @param  array<string, string>  $mapping
     * @return iterable<SubscriberData>
     */
    protected function parse($stream, array $mapping): iterable
    {
        $header = fgetcsv($stream);

        if ($header === false) {
            return;
        }

        $columns = $this->resolveColumns($header, $mapping);

        while (($row = fgetcsv($stream)) !== false) {
            $email = $columns['email'] !== null ? ($row[$columns['email']] ?? null) : null;

            if (! $email) {
                continue;
            }

            $tagsValue = $columns['tags'] !== null ? ($row[$columns['tags']] ?? null) : null;

            yield new SubscriberData(
                email: (string) $email,
                firstName: $columns['first_name'] !== null ? ($row[$columns['first_name']] ?? null) : null,
                lastName: $columns['last_name'] !== null ? ($row[$columns['last_name']] ?? null) : null,
                tags: $tagsValue ? array_values(array_filter(array_map('trim', explode(';', (string) $tagsValue)))) : [],
            );
        }
    }

    /**
     * @param  string[]  $header
     * @param  array<string, string>  $mapping
     * @return array<string, int|null>
     */
    protected function resolveColumns(array $header, array $mapping): array
    {
        $resolved = [];

        foreach (['email', 'first_name', 'last_name', 'tags'] as $field) {
            $columnName = $mapping[$field] ?? $field;
            $index = array_search($columnName, $header, true);
            $resolved[$field] = $index === false ? null : $index;
        }

        return $resolved;
    }

    /**
     * @param  array<string, string>  $mapping
     */
    public static function encodeOption(string $path, array $mapping = [], string $disk = 'local'): string
    {
        return json_encode(['path' => $path, 'mapping' => $mapping, 'disk' => $disk]);
    }

    /**
     * @return array{0: string, 1: array<string, string>, 2: string}|null
     */
    protected function decodeOption(string $option): ?array
    {
        $decoded = json_decode($option, true);

        if (! is_array($decoded) || ! isset($decoded['path'])) {
            return null;
        }

        return [$decoded['path'], $decoded['mapping'] ?? [], $decoded['disk'] ?? 'local'];
    }
}
