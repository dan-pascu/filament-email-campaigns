<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use DanPascu\FilamentEmailCampaigns\Models\TransactionalMail;
use DanPascu\FilamentEmailCampaigns\Support\EspDriverManager;
use DanPascu\FilamentEmailCampaigns\Support\EspMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SendConfirmationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $subscriberId,
        public int $emailListId,
    ) {
        $this->onConnection(config('filament-email-campaigns.sending.queue_connection'));
        $this->onQueue(config('filament-email-campaigns.sending.queue', 'email-campaigns'));
    }

    public function handle(): void
    {
        TenantScope::withoutScoping(function (): void {
            $subscriber = Subscriber::find($this->subscriberId);
            $list = EmailList::find($this->emailListId);

            if (! $subscriber || ! $list || ! $list->emailProvider) {
                return;
            }

            $confirmationUrl = URL::signedRoute('filament-email-campaigns.confirm', [
                'list' => $list->id,
                'subscriber' => $subscriber->id,
            ], now()->addDays((int) config('filament-email-campaigns.tracking.signed_url_lifetime_days', 365)));

            $subject = $list->confirmation_subject
                ?: __('filament-email-campaigns::translations.confirmation.default_subject', ['list' => $list->name]);

            $body = $list->confirmation_body
                ?: __('filament-email-campaigns::translations.confirmation.default_body', ['list' => $list->name]);

            $html = str_replace('{confirmation_url}', $confirmationUrl, $body);

            if (! str_contains($body, '{confirmation_url}')) {
                $html .= '<p><a href="'.$confirmationUrl.'">'.$confirmationUrl.'</a></p>';
            }

            $message = new EspMessage(
                toEmail: $subscriber->email,
                toName: $subscriber->fullName(),
                fromEmail: $list->default_from_email,
                fromName: $list->default_from_name ?: $list->name,
                subject: $subject,
                html: $html,
                replyToEmail: $list->default_reply_to_email,
                tags: ['confirmation', "list-{$list->id}"],
            );

            $result = EspDriverManager::driverFor($list->emailProvider)->send($message);

            TransactionalMail::create([
                'tenant_id' => $list->tenant_id,
                'email_provider_id' => $list->emailProvider->id,
                'to' => $subscriber->email,
                'subject' => $message->subject,
                'esp_message_id' => $result->messageId,
                'sent_at' => $result->successful ? now() : null,
                'failed_at' => $result->successful ? null : now(),
                'failure_reason' => $result->errorMessage,
            ]);
        });
    }
}
