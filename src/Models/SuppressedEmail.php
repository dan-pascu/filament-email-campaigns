<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Emails that must never be sent to again within a tenant, because they
 * bounced permanently or complained. Checked before every campaign send,
 * on top of the per-list subscription status.
 */
class SuppressedEmail extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_suppressed_emails';

    protected $fillable = [
        'tenant_id',
        'email',
        'reason',
    ];
}
