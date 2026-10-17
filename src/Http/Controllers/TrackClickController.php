<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use DanPascu\FilamentEmailCampaigns\Models\Link;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class TrackClickController extends Controller
{
    public function __invoke(int $send, int $link): RedirectResponse
    {
        // An anonymous request (no auth, no session tenant), so this
        // resolves both rows by their unique ids rather than depending on
        // whatever TenantResolver::current() would otherwise report.
        $url = TenantScope::withoutScoping(function () use ($send, $link): string {
            $send = CampaignSend::findOrFail($send);
            $link = Link::findOrFail($link);

            $send->events()->create([
                'tenant_id' => $send->tenant_id,
                'campaign_id' => $send->campaign_id,
                'link_id' => $link->id,
                'type' => CampaignEventType::Clicked->value,
                'occurred_at' => now(),
            ]);

            return $link->url;
        });

        return redirect()->away($url);
    }
}
