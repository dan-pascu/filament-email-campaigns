<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use DanPascu\FilamentEmailCampaigns\Services\SubscriberImporter;
use DanPascu\FilamentEmailCampaigns\Support\ImportResult;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberSourceRegistry;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\LazyCollection;

class ImportSubscribersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  string[]  $tags
     */
    public function __construct(
        public int $emailListId,
        public string $sourceKey,
        public ?string $option,
        public string $status,
        public array $tags = [],
        public ?int $notifiableId = null,
        public ?string $notifiableType = null,
    ) {
        $this->onConnection(config('filament-email-campaigns.sending.queue_connection'));
        $this->onQueue(config('filament-email-campaigns.sending.queue', 'email-campaigns'));
    }

    public function handle(SubscriberImporter $importer): void
    {
        TenantScope::withoutScoping(function () use ($importer): void {
            $list = EmailList::find($this->emailListId);
            $source = SubscriberSourceRegistry::get($this->sourceKey);

            if (! $list || ! $source) {
                return;
            }

            $status = SubscriptionStatus::from($this->status);
            $result = new ImportResult;
            $chunkSize = (int) config('filament-email-campaigns.sending.chunk_size', 100);

            // LazyCollection::make() rejects a bare Generator; it must be
            // given a closure that creates one, so it can be iterated more
            // than once if needed (and chunk() requires the latter).
            foreach (LazyCollection::make(fn () => $source->records($list->tenant_id, $this->option))->chunk($chunkSize) as $chunk) {
                $result->merge($importer->import($list, $chunk, $status, $this->tags));
            }

            $this->notify($list, $result);
        });
    }

    protected function notify(EmailList $list, ImportResult $result): void
    {
        $notifiable = $this->resolveNotifiable();

        if (! $notifiable) {
            return;
        }

        Notification::make()
            ->title(__('filament-email-campaigns::translations.notifications.import_finished_title', ['list' => $list->name]))
            ->body(__('filament-email-campaigns::translations.notifications.import_finished_body', [
                'created' => $result->created,
                'updated' => $result->updated,
                'skipped_suppressed' => $result->skippedSuppressed,
                'skipped_unsubscribed' => $result->skippedUnsubscribed,
                'invalid' => $result->invalid,
            ]))
            ->success()
            ->sendToDatabase($notifiable);
    }

    protected function resolveNotifiable(): ?Model
    {
        if (! $this->notifiableId || ! $this->notifiableType || ! class_exists($this->notifiableType)) {
            return null;
        }

        return $this->notifiableType::find($this->notifiableId);
    }
}
