<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Enums\AutomationActionType;
use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\AutomationActionRun;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\SubscriberList;
use DanPascu\FilamentEmailCampaigns\Models\TransactionalMail;
use DanPascu\FilamentEmailCampaigns\Support\EspDriverManager;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;

/**
 * Advances every subscriber through their automations' linear action
 * sequence. Each due run executes its action, marks itself complete, and
 * schedules the next action in the sequence (immediately, or after a
 * delay for a "wait" action).
 */
class AutomationRunner
{
    public function runDue(): void
    {
        AutomationActionRun::query()
            ->whereNull('completed_at')
            ->where('run_at', '<=', now())
            ->with(['action', 'automation', 'subscriber'])
            ->each(fn (AutomationActionRun $run) => $this->execute($run));
    }

    protected function execute(AutomationActionRun $run): void
    {
        $action = $run->action;
        $subscriber = $run->subscriber;
        $automation = $run->automation;

        $nextRunAt = now();

        match ($action->type) {
            AutomationActionType::Wait => $nextRunAt = now()->addMinutes((int) ($action->config['minutes'] ?? 60)),
            AutomationActionType::AddTag => $this->addTag($subscriber, (string) ($action->config['tag'] ?? '')),
            AutomationActionType::RemoveTag => $this->removeTag($subscriber, (string) ($action->config['tag'] ?? '')),
            AutomationActionType::SubscribeToList => $this->setListStatus($subscriber->id, $automation->email_list_id, SubscriptionStatus::Subscribed),
            AutomationActionType::UnsubscribeFromList => $this->setListStatus($subscriber->id, $automation->email_list_id, SubscriptionStatus::Unsubscribed),
            AutomationActionType::SendEmail => $this->sendEmail($run),
        };

        $run->update(['completed_at' => now()]);

        $nextAction = $automation->actions()->where('order', '>', $action->order)->first();

        if ($nextAction) {
            AutomationActionRun::create([
                'automation_id' => $automation->id,
                'automation_action_id' => $nextAction->id,
                'subscriber_id' => $subscriber->id,
                'run_at' => $nextRunAt,
            ]);
        }
    }

    protected function addTag(Subscriber $subscriber, string $tag): void
    {
        if ($tag === '') {
            return;
        }

        $tags = $subscriber->tags ?? [];

        if (! in_array($tag, $tags, true)) {
            $subscriber->update(['tags' => [...$tags, $tag]]);
        }
    }

    protected function removeTag(Subscriber $subscriber, string $tag): void
    {
        $subscriber->update(['tags' => array_values(array_diff($subscriber->tags ?? [], [$tag]))]);
    }

    protected function setListStatus(int $subscriberId, int $emailListId, SubscriptionStatus $status): void
    {
        SubscriberList::query()->updateOrCreate(
            ['subscriber_id' => $subscriberId, 'email_list_id' => $emailListId],
            [
                'status' => $status->value,
                'subscribed_at' => $status === SubscriptionStatus::Subscribed ? now() : null,
                'unsubscribed_at' => $status === SubscriptionStatus::Unsubscribed ? now() : null,
            ],
        );
    }

    protected function sendEmail(AutomationActionRun $run): void
    {
        $automation = $run->automation;
        $subscriber = $run->subscriber;
        $config = $run->action->config ?? [];
        $emailProvider = $automation->emailList->emailProvider;

        if (! $emailProvider) {
            return;
        }

        $message = new EspMessage(
            toEmail: $subscriber->email,
            toName: $subscriber->fullName(),
            fromEmail: $automation->emailList->default_from_email,
            fromName: $automation->emailList->default_from_name,
            subject: (string) ($config['subject'] ?? ''),
            html: (string) ($config['html_content'] ?? ''),
            replyToEmail: $automation->emailList->default_reply_to_email,
            tags: ["automation-{$automation->id}"],
        );

        $result = EspDriverManager::driverFor($emailProvider)->send($message);

        TransactionalMail::create([
            'tenant_id' => $automation->tenant_id,
            'email_provider_id' => $emailProvider->id,
            'to' => $subscriber->email,
            'subject' => $message->subject,
            'esp_message_id' => $result->messageId,
            'sent_at' => $result->successful ? now() : null,
            'failed_at' => $result->successful ? null : now(),
            'failure_reason' => $result->errorMessage,
        ]);
    }
}
