<?php

namespace DanPascu\FilamentEmailCampaigns\Tests\Fixtures;

use DanPascu\FilamentEmailCampaigns\Contracts\EspDriver;
use DanPascu\FilamentEmailCampaigns\Models\EmailProvider;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use DanPascu\FilamentEmailCampaigns\Support\EspSendResult;
use Illuminate\Http\Request;

class FakeEspDriver implements EspDriver
{
    /** @var EspMessage[] */
    public static array $sent = [];

    public function __construct(protected EmailProvider $provider) {}

    public static function credentialFields(): array
    {
        return [];
    }

    public function send(EspMessage $message): EspSendResult
    {
        static::$sent[] = $message;

        return EspSendResult::success('fake-message-id');
    }

    public function verifyWebhookRequest(Request $request): bool
    {
        return true;
    }

    public function parseWebhookRequest(Request $request): array
    {
        return [];
    }
}
