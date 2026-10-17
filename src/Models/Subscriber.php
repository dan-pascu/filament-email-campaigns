<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_subscribers';

    protected $fillable = [
        'tenant_id',
        'email',
        'first_name',
        'last_name',
        'tags',
        'extra_attributes',
    ];

    protected $casts = [
        'tags' => 'array',
        'extra_attributes' => 'array',
    ];

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(EmailList::class, 'email_campaigns_subscriber_lists')
            ->using(SubscriberList::class)
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    public function subscribedLists(): BelongsToMany
    {
        return $this->lists()->wherePivot('status', SubscriptionStatus::Subscribed->value);
    }

    public function campaignSends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->email;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }

    public function statusFor(EmailList $list): ?SubscriptionStatus
    {
        $pivot = $this->lists->firstWhere('id', $list->id)?->pivot;

        return $pivot ? SubscriptionStatus::from($pivot->status) : null;
    }
}
