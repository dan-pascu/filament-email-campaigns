<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\AutomationActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One step in an automation's linear sequence, e.g. "wait 1 day" then
 * "send email" then "add tag". `order` determines execution order.
 */
class AutomationAction extends Model
{
    protected $table = 'email_campaigns_automation_actions';

    protected $fillable = [
        'automation_id',
        'order',
        'type',
        'config',
    ];

    protected $casts = [
        'type' => AutomationActionType::class,
        'config' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationActionRun::class);
    }
}
