<?php

namespace DanPascu\FilamentEmailCampaigns\Models;

use DanPascu\FilamentEmailCampaigns\Enums\EspProvider;
use DanPascu\FilamentEmailCampaigns\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailProvider extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'email_campaigns_email_providers';

    protected $fillable = [
        'tenant_id',
        'name',
        'provider',
        'credentials',
        'webhook_signing_secret',
        'is_default',
    ];

    protected $casts = [
        'provider' => EspProvider::class,
        'credentials' => 'encrypted:array',
        'webhook_signing_secret' => 'encrypted',
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'credentials',
        'webhook_signing_secret',
    ];

    public function emailLists(): HasMany
    {
        return $this->hasMany(EmailList::class);
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }
}
