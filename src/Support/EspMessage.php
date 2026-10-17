<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

/**
 * A provider-agnostic outbound email, built by the sending pipeline and
 * handed to whichever EspDriver is configured for the tenant.
 */
class EspMessage
{
    public function __construct(
        public string $toEmail,
        public ?string $toName,
        public string $fromEmail,
        public string $fromName,
        public string $subject,
        public string $html,
        public ?string $replyToEmail = null,
        /** @var array<string, string> */
        public array $tags = [],
        /** @var array<string, mixed> arbitrary data returned unchanged in the ESP's webhook payload */
        public array $metadata = [],
    ) {}
}
