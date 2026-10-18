<?php

namespace DanPascu\FilamentEmailCampaigns\Actions;

use DanPascu\FilamentEmailCampaigns\Enums\SubscriptionStatus;
use DanPascu\FilamentEmailCampaigns\Jobs\ImportSubscribersJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use Illuminate\Database\Eloquent\Model;

class ImportSubscribersActionHandler
{
    /**
     * @param  string[]  $tags
     */
    public function __invoke(
        EmailList $list,
        string $sourceKey,
        ?string $option,
        SubscriptionStatus $status,
        array $tags = [],
        ?Model $notifiable = null,
    ): void {
        ImportSubscribersJob::dispatch(
            $list->id,
            $sourceKey,
            $option,
            $status->value,
            $tags,
            $notifiable?->getKey(),
            $notifiable ? $notifiable::class : null,
        );
    }
}
