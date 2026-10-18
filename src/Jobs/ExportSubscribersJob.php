<?php

namespace DanPascu\FilamentEmailCampaigns\Jobs;

use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Scopes\TenantScope;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ExportSubscribersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $emailListId,
        public ?int $notifiableId = null,
        public ?string $notifiableType = null,
    ) {
        $this->onConnection(config('filament-email-campaigns.sending.queue_connection'));
        $this->onQueue(config('filament-email-campaigns.sending.queue', 'email-campaigns'));
    }

    public function handle(): void
    {
        TenantScope::withoutScoping(function (): void {
            $list = EmailList::find($this->emailListId);

            if (! $list) {
                return;
            }

            $path = $this->write($list);

            $this->notify($list, $path);
        });
    }

    protected function write(EmailList $list): string
    {
        $disk = Storage::disk(config('filament-email-campaigns.storage.disk', 'local'));
        $path = 'email-campaigns-exports/'.$list->id.'-'.now()->timestamp.'.csv';

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['email', 'first_name', 'last_name', 'status', 'tags']);

        $list->subscribers()->orderBy('email')->each(function ($subscriber) use ($stream): void {
            fputcsv($stream, [
                $subscriber->email,
                $subscriber->first_name,
                $subscriber->last_name,
                $subscriber->pivot->status->value,
                implode(';', $subscriber->tags ?? []),
            ]);
        });

        rewind($stream);
        $disk->put($path, stream_get_contents($stream));
        fclose($stream);

        return $path;
    }

    protected function notify(EmailList $list, string $path): void
    {
        $notifiable = $this->resolveNotifiable();

        if (! $notifiable) {
            return;
        }

        $url = URL::signedRoute('filament-email-campaigns.exports.download', [
            'path' => base64_encode($path),
        ], now()->addDays(7));

        Notification::make()
            ->title(__('filament-email-campaigns::translations.notifications.export_finished_title', ['list' => $list->name]))
            ->body(__('filament-email-campaigns::translations.notifications.export_finished_body'))
            ->success()
            ->actions([
                NotificationAction::make('download')
                    ->label(__('filament-email-campaigns::translations.actions.download'))
                    ->url($url)
                    ->openUrlInNewTab(),
            ])
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
