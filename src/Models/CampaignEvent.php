<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEvent extends Model
{
    use BelongsToTenant;

    protected $table = 'email_campaigns_campaign_events';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'campaign_send_id',
        'link_id',
        'type',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'type' => CampaignEventType::class,
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function send(): BelongsTo
    {
        return $this->belongsTo(CampaignSend::class, 'campaign_send_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
