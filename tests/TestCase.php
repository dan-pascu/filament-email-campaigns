<?php

namespace DanPascu\FilamentEmailCampaigns\Tests;

use DanPascu\FilamentEmailCampaigns\FilamentEmailCampaignsServiceProvider;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberSourceRegistry;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\FixtureSubscriberSource;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // TenantResolver's registered closure and the subscriber source
        // registry are process-wide statics, so they must not leak
        // between tests.
        TenantResolver::resolveUsing(null);
        SubscriberSourceRegistry::reset();
        FixtureSubscriberSource::$records = [];

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('notifiable_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [FilamentEmailCampaignsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('filament-email-campaigns.tenant.model', Tenant::class);
    }
}
