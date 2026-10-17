<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

class EspSendResult
{
    public function __construct(
        public bool $successful,
        public ?string $messageId = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(string $messageId): self
    {
        return new self(true, messageId: $messageId);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, errorMessage: $errorMessage);
    }
}
