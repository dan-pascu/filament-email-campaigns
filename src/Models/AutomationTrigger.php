<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationTrigger extends Model
{
    protected $table = 'email_campaigns_automation_triggers';

    protected $fillable = [
        'automation_id',
        'type',
        'config',
    ];

    protected $casts = [
        'type' => AutomationTriggerType::class,
        'config' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
