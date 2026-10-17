<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CampaignStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Paused = 'paused';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.campaign_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'info',
            self::Sending => 'warning',
            self::Sent => 'success',
            self::Paused => 'warning',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Scheduled => 'heroicon-o-clock',
            self::Sending => 'heroicon-o-paper-airplane',
            self::Sent => 'heroicon-o-check-circle',
            self::Paused => 'heroicon-o-pause-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }
}
