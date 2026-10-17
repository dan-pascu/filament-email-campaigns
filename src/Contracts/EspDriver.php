<?php

namespace DanPascu\FilamentEmailCampaigns\Contracts;

use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Support\EspSendResult;
use DanPascu\FilamentEmailCampaigns\Support\EspWebhookEvent;
use Illuminate\Http\Request;

interface EspDriver
{
    public function __construct(EmailProvider $provider);

    public function send(EspMessage $message): EspSendResult;

    /**
     * The fields a settings form needs to configure this provider, as
     * [key => label]. Values are stored encrypted on EmailProvider::credentials.
     *
     * @return array<string, string>
     */
    public static function credentialFields(): array;

    public function verifyWebhookRequest(Request $request): bool;

    /**
     * @return EspWebhookEvent[]
     */
    public function parseWebhookRequest(Request $request): array;
}
