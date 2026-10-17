<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use DanPascu\FilamentEmailCampaigns\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class TrackClickController extends Controller
{
    public function __invoke(CampaignSend $send, Link $link): RedirectResponse
    {
        $send->events()->create([
            'tenant_id' => $send->tenant_id,
            'campaign_id' => $send->campaign_id,
            'link_id' => $link->id,
            'type' => CampaignEventType::Clicked->value,
            'occurred_at' => now(),
        ]);

        return redirect()->away($link->url);
    }
}
