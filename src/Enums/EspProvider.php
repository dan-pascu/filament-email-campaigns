<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasLabel;

enum EspProvider: string implements HasLabel
{
    case Mailgun = 'mailgun';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.esp_provider.'.$this->value);
    }
}
