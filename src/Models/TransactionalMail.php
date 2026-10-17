<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * A log entry for a one-off transactional email sent through a tenant's
 * configured ESP (outside of campaigns/automations), kept for stats and
 * webhook correlation.
 */
class TransactionalMail extends Model
{
    use BelongsToTenant;

    protected $table = 'email_campaigns_transactional_mails';

    protected $fillable = [
        'tenant_id',
        'email_provider_id',
        'to',
        'subject',
        'esp_message_id',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
