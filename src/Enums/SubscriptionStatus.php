<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Unconfirmed = 'unconfirmed';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Complained = 'complained';
    case Bounced = 'bounced';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.subscription_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unconfirmed => 'gray',
            self::Subscribed => 'success',
            self::Unsubscribed => 'warning',
            self::Complained => 'danger',
            self::Bounced => 'danger',
        };
    }

    public function isSendable(): bool
    {
        return $this === self::Subscribed;
    }
}
