<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy
    |--------------------------------------------------------------------------
    |
    | Every model in this package (lists, subscribers, campaigns, providers,
    | ...) belongs to a tenant. Point these at the tenant model your
    | Filament panel already uses (the model passed to Panel::tenant()).
    |
    | If your app doesn't use Filament's panel tenancy at all, set `resolver`
    | to an invokable class (a class with an `__invoke(): int|string|null`
    | method) instead, or call
    | FilamentEmailCampaignsPlugin::make()->resolveTenantUsing(fn () => ...)
    | when registering the plugin. See Support\TenantResolver.
    */
    'tenant' => [
        'model' => env('FILAMENT_EMAIL_CAMPAIGNS_TENANT_MODEL', 'App\\Models\\Team'),
        'foreign_key' => 'tenant_id',
        'relationship' => 'tenant',
        'resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sending
    |--------------------------------------------------------------------------
    */
    'sending' => [
        // Queue connection/name used for dispatching campaign sends.
        'queue_connection' => env('FILAMENT_EMAIL_CAMPAIGNS_QUEUE_CONNECTION', config('queue.default')),
        'queue' => env('FILAMENT_EMAIL_CAMPAIGNS_QUEUE', 'email-campaigns'),

        // Max emails sent per second per email provider, to stay under ESP limits.
        'rate_limit_per_second' => (int) env('FILAMENT_EMAIL_CAMPAIGNS_RATE_LIMIT', 10),

        // How many recipients get queued in a single batch job.
        'chunk_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | A/B testing
    |--------------------------------------------------------------------------
    */
    'ab_testing' => [
        // Default delay, in minutes, before the winning variant is picked and
        // sent to the remaining recipients.
        'default_wait_minutes' => 240,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking
    |--------------------------------------------------------------------------
    */
    'tracking' => [
        'track_opens' => true,
        'track_clicks' => true,
        // Signed tracking/unsubscribe links expire after this many days.
        'signed_url_lifetime_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email service providers
    |--------------------------------------------------------------------------
    |
    | Driver implementations available to DanPascu\FilamentEmailCampaigns\Support\EspDriverManager.
    | Additional providers can be registered by binding another class to the
    | same contract and adding it here.
    */
    'providers' => [
        'mailgun' => DanPascu\FilamentEmailCampaigns\Drivers\MailgunDriver::class,
        'ses' => DanPascu\FilamentEmailCampaigns\Drivers\SesDriver::class,
        'postmark' => DanPascu\FilamentEmailCampaigns\Drivers\PostmarkDriver::class,
        'brevo' => DanPascu\FilamentEmailCampaigns\Drivers\BrevoDriver::class,
        'sendgrid' => DanPascu\FilamentEmailCampaigns\Drivers\SendGridDriver::class,
        'resend' => DanPascu\FilamentEmailCampaigns\Drivers\ResendDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    |
    | Tracking/unsubscribe links are signed, and the webhook route verifies
    | its own signature per-provider, so none of these routes run through
    | the 'web' middleware group (no session/CSRF needed).
    */
    'route' => [
        'prefix' => 'email-campaigns',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscriber sources
    |--------------------------------------------------------------------------
    |
    | Extra places subscribers can be imported from, besides the built-in
    | CSV upload: class-strings or instances implementing
    | Contracts\SubscriberSource. Can also be registered at runtime via
    | FilamentEmailCampaignsPlugin::make()->subscriberSources([...]).
    */
    'subscriber_sources' => [],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Disk used for uploaded import CSVs and generated export files. Must
    | be shared across every queue worker that can process these jobs.
    */
    'storage' => [
        'disk' => env('FILAMENT_EMAIL_CAMPAIGNS_STORAGE_DISK', 'local'),
    ],
];
