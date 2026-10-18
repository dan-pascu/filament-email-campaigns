<?php

use DanPascu\FilamentEmailCampaigns\SubscriberSources\CsvSubscriberSource;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;
use Illuminate\Support\Facades\Storage;

function putCsv(string $path, string $contents): void
{
    Storage::disk('local')->put($path, $contents);
}

it('reads subscribers from a CSV using the default column names', function () {
    Storage::fake('local');
    putCsv('import.csv', "email,first_name,last_name,tags\nann@acme.com,Ann,Smith,vip;lead\nbob@acme.com,Bob,Jones,\n");

    $source = new CsvSubscriberSource;
    $option = CsvSubscriberSource::encodeOption('import.csv', [], 'local');

    $records = iterator_to_array($source->records(null, $option));

    expect($records)->toHaveCount(2);
    expect($records[0])->toBeInstanceOf(SubscriberData::class);
    expect($records[0]->email)->toBe('ann@acme.com');
    expect($records[0]->firstName)->toBe('Ann');
    expect($records[0]->lastName)->toBe('Smith');
    expect($records[0]->tags)->toBe(['vip', 'lead']);
    expect($records[1]->tags)->toBe([]);
});

it('maps custom column names', function () {
    Storage::fake('local');
    putCsv('import.csv', "E-mail,Prenume\nann@acme.com,Ann\n");

    $source = new CsvSubscriberSource;
    $option = CsvSubscriberSource::encodeOption('import.csv', [
        'email' => 'E-mail',
        'first_name' => 'Prenume',
    ], 'local');

    $records = iterator_to_array($source->records(null, $option));

    expect($records)->toHaveCount(1);
    expect($records[0]->email)->toBe('ann@acme.com');
    expect($records[0]->firstName)->toBe('Ann');
});

it('skips rows without an email', function () {
    Storage::fake('local');
    putCsv('import.csv', "email,first_name\n,Nobody\nann@acme.com,Ann\n");

    $source = new CsvSubscriberSource;
    $option = CsvSubscriberSource::encodeOption('import.csv', [], 'local');

    $records = iterator_to_array($source->records(null, $option));

    expect($records)->toHaveCount(1);
    expect($records[0]->email)->toBe('ann@acme.com');
});

it('returns nothing for a missing path', function () {
    Storage::fake('local');

    $source = new CsvSubscriberSource;
    $option = CsvSubscriberSource::encodeOption('missing.csv', [], 'local');

    expect(iterator_to_array($source->records(null, $option)))->toBe([]);
});

it('returns nothing when no option is given', function () {
    $source = new CsvSubscriberSource;

    expect(iterator_to_array($source->records(null, null)))->toBe([]);
});

it('has no selectable options of its own', function () {
    $source = new CsvSubscriberSource;

    expect($source->options(null))->toBe([]);
    expect($source->key())->toBe('csv');
});
