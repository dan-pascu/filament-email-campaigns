<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One outbound email for one subscriber. This is the row campaign events
 * (open/click/bounce/...) hang off, and the row the ESP's message id maps
 * back to when a webhook event arrives.
 */
class CampaignSend extends Model
{
    use BelongsToTenant;

    protected $table = 'email_campaigns_campaign_sends';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'campaign_variant_id',
        'subscriber_id',
        'esp_message_id',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CampaignVariant::class, 'campaign_variant_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class);
    }
}
