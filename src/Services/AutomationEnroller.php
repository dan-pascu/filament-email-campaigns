<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Enums\AutomationTriggerType;
use DanPascu\FilamentEmailCampaigns\Models\Automation;
use DanPascu\FilamentEmailCampaigns\Models\AutomationActionRun;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;

/**
 * Starts a subscriber on every enabled automation whose trigger matches an
 * event that just happened (subscribed/unsubscribed from a list, tag
 * added/removed), by scheduling a run of that automation's first action.
 */
class AutomationEnroller
{
    public function enrollForListTrigger(Subscriber $subscriber, EmailList $list, AutomationTriggerType $type): void
    {
        $automations = $list->automations()
            ->where('is_enabled', true)
            ->whereHas('triggers', fn ($query) => $query->where('type', $type->value))
            ->get();

        foreach ($automations as $automation) {
            $this->enroll($subscriber, $automation);
        }
    }

    public function enrollForTag(Subscriber $subscriber, string $tag, AutomationTriggerType $type): void
    {
        $automations = Automation::query()
            ->where('tenant_id', $subscriber->tenant_id)
            ->where('is_enabled', true)
            ->whereHas('triggers', fn ($query) => $query->where('type', $type->value)
                ->whereJsonContains('config->tag', $tag))
            ->get();

        foreach ($automations as $automation) {
            $this->enroll($subscriber, $automation);
        }
    }

    protected function enroll(Subscriber $subscriber, Automation $automation): void
    {
        $firstAction = $automation->actions()->first();

        if (! $firstAction) {
            return;
        }

        $alreadyEnrolled = AutomationActionRun::query()
            ->where('automation_id', $automation->id)
            ->where('subscriber_id', $subscriber->id)
            ->exists();

        if ($alreadyEnrolled) {
            return;
        }

        AutomationActionRun::create([
            'automation_id' => $automation->id,
            'automation_action_id' => $firstAction->id,
            'subscriber_id' => $subscriber->id,
            'run_at' => now(),
        ]);
    }
}
