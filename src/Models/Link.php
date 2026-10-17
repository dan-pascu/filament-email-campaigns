<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A tracked link inside a campaign's HTML content. Every occurrence of the
 * same destination URL in one campaign shares a single Link row, so click
 * counts can be grouped per URL.
 */
class Link extends Model
{
    use BelongsToTenant;

    protected $table = 'email_campaigns_links';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'url',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class);
    }
}
