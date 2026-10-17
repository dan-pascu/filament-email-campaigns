<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_automations';

    protected $fillable = [
        'tenant_id',
        'email_list_id',
        'name',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(AutomationTrigger::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class)->orderBy('order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationActionRun::class);
    }
}
