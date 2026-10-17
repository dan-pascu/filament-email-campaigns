<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks one subscriber's progress through one automation: which action
 * they are currently waiting on/have completed, and when they should be
 * advanced next (used for "wait" steps).
 */
class AutomationActionRun extends Model
{
    protected $table = 'email_campaigns_automation_action_runs';

    protected $fillable = [
        'automation_id',
        'automation_action_id',
        'subscriber_id',
        'run_at',
        'completed_at',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(AutomationAction::class, 'automation_action_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
