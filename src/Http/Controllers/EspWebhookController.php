<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Jobs\ProcessEspWebhookEventJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Support\EspDriverManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class EspWebhookController extends Controller
{
    public function __invoke(Request $request, int $provider): Response
    {
        // The ESP calling this has no auth/session tenant, so this
        // resolves the provider by its unique id rather than depending on
        // whatever TenantResolver::current() would otherwise report.
        return TenantScope::withoutScoping(function () use ($request, $provider): Response {
            $provider = EmailProvider::findOrFail($provider);
            $driver = EspDriverManager::driverFor($provider);

            if (! $driver->verifyWebhookRequest($request)) {
                return response('Invalid signature', 403);
            }

            foreach ($driver->parseWebhookRequest($request) as $event) {
                ProcessEspWebhookEventJob::dispatch($event);
            }

            return response('OK');
        });
    }
}
