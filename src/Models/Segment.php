<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A segment narrows an email list down using condition groups, combined
 * with AND/OR at two levels. Two JSON shapes are supported on
 * `conditions`, so existing segments keep working unchanged:
 *
 *   // legacy flat shape (a single implicit group)
 *   ['match' => 'all', 'rules' => [
 *       ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
 *       ['field' => 'email', 'operator' => 'ends_with', 'value' => '@acme.com'],
 *   ]]
 *
 *   // nested shape (groups of rules, each combined by its own match)
 *   ['match' => 'any', 'groups' => [
 *       ['match' => 'all', 'rules' => [
 *           ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
 *       ]],
 *       ['match' => 'all', 'rules' => [
 *           ['field' => 'subscribed_at', 'operator' => 'after', 'value' => '2026-01-01'],
 *       ]],
 *   ]]
 *
 * Supported fields: email, first_name, last_name, tags, subscribed_at
 * (based on this segment's own list), and any extra_attributes.<key> path.
 */
class Segment extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_segments';

    protected $fillable = [
        'tenant_id',
        'email_list_id',
        'name',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function matches(Subscriber $subscriber): bool
    {
        $conditions = $this->conditions ?? [];

        if (isset($conditions['groups'])) {
            $groups = $conditions['groups'];

            if ($groups === []) {
                return true;
            }

            $results = array_map(fn (array $group) => $this->groupMatches($subscriber, $group), $groups);

            return ($conditions['match'] ?? 'all') === 'any'
                ? in_array(true, $results, true)
                : ! in_array(false, $results, true);
        }

        // Legacy flat shape: a single implicit group.
        return $this->groupMatches($subscriber, $conditions);
    }

    protected function groupMatches(Subscriber $subscriber, array $group): bool
    {
        $rules = $group['rules'] ?? [];

        if ($rules === []) {
            return true;
        }

        $results = array_map(fn (array $rule) => $this->ruleMatches($subscriber, $rule), $rules);

        return ($group['match'] ?? 'all') === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    protected function ruleMatches(Subscriber $subscriber, array $rule): bool
    {
        $field = (string) ($rule['field'] ?? '');
        $operator = (string) ($rule['operator'] ?? 'equals');
        $value = $rule['value'] ?? null;

        if ($field === 'tags') {
            return $subscriber->hasTag((string) $value);
        }

        if ($field === 'subscribed_at') {
            return $this->subscribedAtMatches($subscriber, $operator, (string) $value);
        }

        if (str_starts_with($field, 'extra_attributes.')) {
            $subject = (string) (data_get($subscriber->extra_attributes, substr($field, strlen('extra_attributes.'))) ?? '');

            return $this->stringMatches($subject, $operator, (string) $value);
        }

        $subject = (string) ($subscriber->{$field} ?? '');

        return $this->stringMatches($subject, $operator, (string) $value);
    }

    protected function stringMatches(string $subject, string $operator, string $value): bool
    {
        return match ($operator) {
            'equals' => $subject === $value,
            'contains' => str_contains($subject, $value),
            'starts_with' => str_starts_with($subject, $value),
            'ends_with' => str_ends_with($subject, $value),
            default => false,
        };
    }

    protected function subscribedAtMatches(Subscriber $subscriber, string $operator, string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $pivot = $subscriber->lists->firstWhere('id', $this->email_list_id)?->pivot;
        $subscribedAt = $pivot?->subscribed_at;

        if (! $subscribedAt) {
            return false;
        }

        $compare = \Illuminate\Support\Carbon::parse($value);

        return match ($operator) {
            'before' => $subscribedAt->lt($compare),
            'after' => $subscribedAt->gt($compare),
            'equals' => $subscribedAt->isSameDay($compare),
            default => false,
        };
    }
}
