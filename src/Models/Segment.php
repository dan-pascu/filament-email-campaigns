<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A segment narrows an email list down using simple condition groups, e.g.
 *
 *   ['match' => 'all', 'rules' => [
 *       ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
 *       ['field' => 'email', 'operator' => 'ends_with', 'value' => '@acme.com'],
 *   ]]
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
        $rules = $conditions['rules'] ?? [];

        if ($rules === []) {
            return true;
        }

        $results = array_map(fn (array $rule) => $this->ruleMatches($subscriber, $rule), $rules);

        return ($conditions['match'] ?? 'all') === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    protected function ruleMatches(Subscriber $subscriber, array $rule): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;

        if ($field === 'tags') {
            return $subscriber->hasTag((string) $value);
        }

        $subject = (string) ($subscriber->{$field} ?? '');

        return match ($operator) {
            'equals' => $subject === (string) $value,
            'contains' => str_contains($subject, (string) $value),
            'starts_with' => str_starts_with($subject, (string) $value),
            'ends_with' => str_ends_with($subject, (string) $value),
            default => false,
        };
    }
}
