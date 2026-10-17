<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;
use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TrackOpenController extends Controller
{
    protected const TRANSPARENT_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function __invoke(CampaignSend $send): Response
    {
        $send->events()->create([
            'tenant_id' => $send->tenant_id,
            'campaign_id' => $send->campaign_id,
            'type' => CampaignEventType::Opened->value,
            'occurred_at' => now(),
        ]);

        return response(self::TRANSPARENT_GIF, 200, ['Content-Type' => 'image/gif']);
    }
}
