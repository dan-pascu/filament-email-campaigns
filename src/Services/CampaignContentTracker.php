<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Models\CampaignSend;
use DanPascu\FilamentEmailCampaigns\Models\Link;
use Illuminate\Support\Facades\URL;

/**
 * Rewrites a campaign's raw HTML into the version actually mailed to one
 * recipient: every <a href> becomes a signed click-tracking link, and an
 * invisible open-tracking pixel is appended.
 *
 * Link/click rewriting is done with a simple regex rather than a full HTML
 * parser, to keep the package dependency-light. It handles the common case
 * (double-quoted absolute http(s) hrefs) produced by Filament's rich text
 * editor; hand-written HTML with unusual quoting may not be rewritten.
 */
class CampaignContentTracker
{
    public function track(string $html, CampaignSend $send): string
    {
        if (config('filament-email-campaigns.tracking.track_clicks', true)) {
            $html = $this->rewriteLinks($html, $send);
        }

        if (config('filament-email-campaigns.tracking.track_opens', true)) {
            $html = $this->appendOpenPixel($html, $send);
        }

        return $html;
    }

    protected function rewriteLinks(string $html, CampaignSend $send): string
    {
        return preg_replace_callback(
            '/href="(https?:\/\/[^"]+)"/i',
            function (array $matches) use ($send) {
                $url = html_entity_decode($matches[1]);

                $link = Link::firstOrCreate(
                    ['campaign_id' => $send->campaign_id, 'url' => $url],
                    ['tenant_id' => $send->tenant_id],
                );

                $trackingUrl = URL::signedRoute('filament-email-campaigns.track.click', [
                    'send' => $send->id,
                    'link' => $link->id,
                ], $this->expiration());

                return 'href="'.$trackingUrl.'"';
            },
            $html,
        );
    }

    protected function appendOpenPixel(string $html, CampaignSend $send): string
    {
        $pixelUrl = URL::signedRoute('filament-email-campaigns.track.open', [
            'send' => $send->id,
        ], $this->expiration());

        $pixel = '<img src="'.$pixelUrl.'" width="1" height="1" alt="" style="display:none;border:0;" />';

        return str_contains($html, '</body>')
            ? str_replace('</body>', $pixel.'</body>', $html)
            : $html.$pixel;
    }

    protected function expiration(): \DateTimeInterface
    {
        return now()->addDays((int) config('filament-email-campaigns.tracking.signed_url_lifetime_days', 365));
    }
}
