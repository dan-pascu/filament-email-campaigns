<?php

namespace DanPascu\FilamentEmailCampaigns\Enums;

use Filament\Support\Contracts\HasLabel;

enum AutomationTriggerType: string implements HasLabel
{
    case SubscribesToList = 'subscribes_to_list';
    case UnsubscribesFromList = 'unsubscribes_from_list';
    case TagAdded = 'tag_added';
    case TagRemoved = 'tag_removed';

    public function getLabel(): string
    {
        return __('filament-email-campaigns::translations.enums.automation_trigger_type.'.$this->value);
    }
}
