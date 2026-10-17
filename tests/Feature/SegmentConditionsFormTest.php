<?php

use DanPascu\FilamentEmailCampaigns\Support\SegmentConditionsForm;

it('upgrades a legacy flat conditions shape into the nested editable shape', function () {
    $data = ['conditions' => ['match' => 'any', 'rules' => [
        ['field' => 'email', 'operator' => 'contains', 'value' => '@acme.com'],
    ]]];

    $denormalized = SegmentConditionsForm::denormalize($data);

    expect($denormalized['conditions']['match'])->toBe('all');
    expect($denormalized['conditions']['groups'])->toHaveCount(1);
    expect($denormalized['conditions']['groups'][0]['match'])->toBe('any');
    expect($denormalized['conditions']['groups'][0]['rules'][0]['field'])->toBe('email');
});

it('marks an unknown field as custom with its path in field_custom when denormalizing', function () {
    $data = ['conditions' => ['match' => 'all', 'groups' => [
        ['match' => 'all', 'rules' => [
            ['field' => 'extra_attributes.company', 'operator' => 'equals', 'value' => 'Claar'],
        ]],
    ]]];

    $denormalized = SegmentConditionsForm::denormalize($data);
    $rule = $denormalized['conditions']['groups'][0]['rules'][0];

    expect($rule['field'])->toBe('custom');
    expect($rule['field_custom'])->toBe('extra_attributes.company');
});

it('leaves a known field untouched when denormalizing', function () {
    $data = ['conditions' => ['match' => 'all', 'groups' => [
        ['match' => 'all', 'rules' => [
            ['field' => 'tags', 'operator' => 'equals', 'value' => 'vip'],
        ]],
    ]]];

    $denormalized = SegmentConditionsForm::denormalize($data);

    expect($denormalized['conditions']['groups'][0]['rules'][0]['field'])->toBe('tags');
    expect($denormalized['conditions']['groups'][0]['rules'][0])->not->toHaveKey('field_custom');
});

it('resolves a custom field back into its real path when normalizing', function () {
    $data = ['conditions' => ['match' => 'all', 'groups' => [
        ['match' => 'all', 'rules' => [
            ['field' => 'custom', 'field_custom' => 'extra_attributes.company', 'operator' => 'equals', 'value' => 'Claar'],
        ]],
    ]]];

    $normalized = SegmentConditionsForm::normalize($data);
    $rule = $normalized['conditions']['groups'][0]['rules'][0];

    expect($rule['field'])->toBe('extra_attributes.company');
    expect($rule)->not->toHaveKey('field_custom');
});

it('round-trips a normal field through normalize without changes', function () {
    $data = ['conditions' => ['match' => 'any', 'groups' => [
        ['match' => 'all', 'rules' => [
            ['field' => 'email', 'operator' => 'ends_with', 'value' => '@acme.com'],
        ]],
    ]]];

    $normalized = SegmentConditionsForm::normalize($data);

    expect($normalized)->toBe($data);
});
