<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasLabel;

enum AutomationActionType: string implements HasLabel
{
    case SendEmail = 'send_email';
    case Wait = 'wait';
    case AddTag = 'add_tag';
    case RemoveTag = 'remove_tag';
    case SubscribeToList = 'subscribe_to_list';
    case UnsubscribeFromList = 'unsubscribe_from_list';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.automation_action_type.'.$this->value);
    }
}
