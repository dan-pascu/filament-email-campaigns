<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Jobs\ProcessEspWebhookEventJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspDriverManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class EspWebhookController extends Controller
{
    public function __invoke(Request $request, EmailProvider $provider): Response
    {
        $driver = EspDriverManager::driverFor($provider);

        if (! $driver->verifyWebhookRequest($request)) {
            return response('Invalid signature', 403);
        }

        foreach ($driver->parseWebhookRequest($request) as $event) {
            ProcessEspWebhookEventJob::dispatch($event);
        }

        return response('OK');
    }
}
