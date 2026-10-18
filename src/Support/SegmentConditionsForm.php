<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

/**
 * Bridges the segment builder's form shape and the shape stored on
 * Segment::conditions.
 *
 * The form always edits the nested shape (a top-level match plus a list of
 * groups, each with its own match and rules), and a rule whose field isn't
 * one of the fixed options is edited as field => 'custom' with the real
 * path in a sibling 'field_custom' input. denormalize() upgrades a legacy
 * flat segment (or one already using 'custom') into that editable shape;
 * normalize() turns submitted form data back into the real conditions
 * array before it's saved.
 */
class SegmentConditionsForm
{
    public const KNOWN_FIELDS = ['email', 'first_name', 'last_name', 'tags', 'subscribed_at'];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function denormalize(array $data): array
    {
        $conditions = $data['conditions'] ?? [];

        if (! isset($conditions['groups'])) {
            $conditions = $conditions === []
                ? ['match' => 'all', 'groups' => []]
                : ['match' => 'all', 'groups' => [['match' => $conditions['match'] ?? 'all', 'rules' => $conditions['rules'] ?? []]]];
        }

        $conditions['groups'] = array_map(function (array $group): array {
            $group['rules'] = array_map(function (array $rule): array {
                $field = (string) ($rule['field'] ?? '');

                if ($field !== '' && ! in_array($field, self::KNOWN_FIELDS, true)) {
                    $rule['field_custom'] = $field;
                    $rule['field'] = 'custom';
                }

                return $rule;
            }, $group['rules'] ?? []);

            return $group;
        }, $conditions['groups'] ?? []);

        $data['conditions'] = $conditions;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $conditions = $data['conditions'] ?? ['match' => 'all', 'groups' => []];

        $conditions['groups'] = array_map(function (array $group): array {
            $group['rules'] = array_map(function (array $rule): array {
                if (($rule['field'] ?? null) === 'custom') {
                    $rule['field'] = $rule['field_custom'] ?? '';
                }

                unset($rule['field_custom']);

                return $rule;
            }, $group['rules'] ?? []);

            return $group;
        }, $conditions['groups'] ?? []);

        $data['conditions'] = $conditions;

        return $data;
    }
}
