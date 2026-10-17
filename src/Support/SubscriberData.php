<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

class SubscriberData
{
    /**
     * @param  string[]  $tags
     * @param  array<string, mixed>  $extraAttributes
     */
    public function __construct(
        public string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public array $tags = [],
        public array $extraAttributes = [],
    ) {}
}
