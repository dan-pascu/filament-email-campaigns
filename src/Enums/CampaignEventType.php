<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CampaignEventType: string implements HasColor, HasLabel
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Unsubscribed = 'unsubscribed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.campaign_event_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Queued, self::Sent, self::Delivered => 'gray',
            self::Opened, self::Clicked => 'success',
            self::Bounced, self::Complained, self::Failed => 'danger',
            self::Unsubscribed => 'warning',
        };
    }
}
