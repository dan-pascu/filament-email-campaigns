<?php

use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Support\TenantResolver;
use DanPascu\FilamentEmailCampaigns\Tests\Fixtures\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * A stand-in for Filament's real FilamentManager, bound at the same
 * 'filament' container key the Filament facade resolves. This exercises
 * exactly what TenantResolver reads (Filament::getTenant()) without
 * booting Filament's own (Livewire-dependent) service providers, which
 * these tests have no other reason to need.
 */
function fakeFilamentTenant(?Model $tenant): void
{
    app()->instance('filament', new class($tenant)
    {
        public function __construct(public ?Model $tenant) {}

        public function getTenant(): ?Model
        {
            return $this->tenant;
        }
    });

    // The Filament facade caches its resolved root the first time it's
    // used, so rebinding the container alone wouldn't be picked up.
    Facade::clearResolvedInstance('filament');
}

function fakeFilamentTenantThrows(): void
{
    app()->instance('filament', new class
    {
        public function getTenant(): never
        {
            throw new RuntimeException('no panels registered');
        }
    });

    Facade::clearResolvedInstance('filament');
}

it('falls back to Filament::getTenant() when no custom resolver is configured', function () {
    $tenant = Tenant::create(['name' => 'Acme']);

    fakeFilamentTenant($tenant);

    expect(app(TenantResolver::class)->current())->toBe($tenant->id);
});

it('returns null when Filament has no current tenant and nothing else is configured', function () {
    fakeFilamentTenant(null);

    expect(app(TenantResolver::class)->current())->toBeNull();
});

it('prefers a closure registered via resolveUsing over Filament tenancy', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    fakeFilamentTenant($tenant);

    TenantResolver::resolveUsing(fn () => 'client-42');

    expect(app(TenantResolver::class)->current())->toBe('client-42');
});

it('prefers an invokable class configured at tenant.resolver over Filament tenancy', function () {
    $tenant = Tenant::create(['name' => 'Acme']);
    fakeFilamentTenant($tenant);

    config(['filament-email-campaigns.tenant.resolver' => StaticTenantResolverStub::class]);

    expect(app(TenantResolver::class)->current())->toBe('config-99');
});

it('is swallowed into null if resolving from Filament tenancy throws', function () {
    fakeFilamentTenantThrows();

    expect(app(TenantResolver::class)->current())->toBeNull();
});

it('scopes models through the Filament tenancy fallback exactly as before, with no resolver configured', function () {
    $tenantA = Tenant::create(['name' => 'Acme']);
    $tenantB = Tenant::create(['name' => 'Widgets Inc']);

    fakeFilamentTenant($tenantA);
    EmailList::create(['tenant_id' => $tenantA->id, 'name' => 'A list']);
    EmailList::create(['tenant_id' => $tenantB->id, 'name' => 'B list']);

    expect(EmailList::count())->toBe(1);
    expect(EmailList::first()->name)->toBe('A list');

    fakeFilamentTenant($tenantB);

    expect(EmailList::count())->toBe(1);
    expect(EmailList::first()->name)->toBe('B list');
});

it('ignores TenantScope while inside withoutScoping, even across nested calls', function () {
    TenantResolver::resolveUsing(fn () => 999);

    $results = TenantScope::withoutScoping(function () {
        return TenantScope::withoutScoping(fn () => 'inner');
    });

    expect($results)->toBe('inner');
});

class StaticTenantResolverStub
{
    public function __invoke(): string
    {
        return 'config-99';
    }
}
