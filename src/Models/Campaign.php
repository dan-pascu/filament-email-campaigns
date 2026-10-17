<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignStatus;
use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_campaigns';

    protected $fillable = [
        'tenant_id',
        'email_list_id',
        'segment_id',
        'email_provider_id',
        'name',
        'subject',
        'from_name',
        'from_email',
        'reply_to_email',
        'html_content',
        'status',
        'scheduled_at',
        'sent_at',
        'is_ab_test',
        'ab_test_sample_percentage',
        'ab_test_winning_metric',
        'ab_test_decided_at',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'ab_test_decided_at' => 'datetime',
        'is_ab_test' => 'boolean',
        'ab_test_sample_percentage' => 'integer',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function emailProvider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(CampaignVariant::class)->orderBy('id');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function eventCount(CampaignEventType $type): int
    {
        return $this->events()->where('type', $type->value)->count();
    }

    public function uniqueEventCount(CampaignEventType $type): int
    {
        return $this->events()
            ->where('type', $type->value)
            ->distinct('campaign_send_id')
            ->count('campaign_send_id');
    }

    public function isSendable(): bool
    {
        return in_array($this->status, [CampaignStatus::Draft, CampaignStatus::Scheduled], true);
    }

    public function resolvedEmailProvider(): ?EmailProvider
    {
        return $this->emailProvider ?? $this->emailList->emailProvider;
    }
}
