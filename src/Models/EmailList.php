<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailList extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_email_lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'default_from_name',
        'default_from_email',
        'default_reply_to_email',
        'email_provider_id',
        'requires_confirmation',
    ];

    protected $casts = [
        'requires_confirmation' => 'boolean',
    ];

    public function emailProvider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'email_campaigns_subscriber_lists')
            ->using(SubscriberList::class)
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    public function subscribedSubscribers(): BelongsToMany
    {
        return $this->subscribers()->wherePivot('status', SubscriptionStatus::Subscribed->value);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }
}
