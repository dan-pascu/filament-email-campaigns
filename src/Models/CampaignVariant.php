<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One variant ("A", "B", ...) of an A/B tested campaign. A non-A/B campaign
 * still gets exactly one variant, so sending logic never has to branch on
 * whether A/B testing is enabled.
 */
class CampaignVariant extends Model
{
    protected $table = 'email_campaigns_campaign_variants';

    protected $fillable = [
        'campaign_id',
        'name',
        'subject',
        'html_content',
        'is_winner',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }
}
