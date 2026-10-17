<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasLabel;

enum EspProvider: string implements HasLabel
{
    case Mailgun = 'mailgun';
    case Ses = 'ses';
    case Postmark = 'postmark';
    case Brevo = 'brevo';
    case SendGrid = 'sendgrid';
    case Resend = 'resend';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.esp_provider.'.$this->value);
    }
}
