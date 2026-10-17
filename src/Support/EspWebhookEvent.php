<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

use Carbon\CarbonInterface;
use DanPascu\FilamentEmailCampaigns\Enums\CampaignEventType;

class EspWebhookEvent
{
    public function __construct(
        public CampaignEventType $type,
        public ?string $messageId,
        public CarbonInterface $occurredAt,
        /** @var array<string, mixed> */
        public array $meta = [],
        public ?string $clickedUrl = null,
    ) {}
}
