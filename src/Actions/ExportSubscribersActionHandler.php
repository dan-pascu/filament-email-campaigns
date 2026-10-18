<?php

namespace DanPascu\FilamentEmailCampaigns\Actions;

use DanPascu\FilamentEmailCampaigns\Jobs\ExportSubscribersJob;
use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use Illuminate\Database\Eloquent\Model;

class ExportSubscribersActionHandler
{
    public function __invoke(EmailList $list, ?Model $notifiable = null): void
    {
        ExportSubscribersJob::dispatch(
            $list->id,
            $notifiable?->getKey(),
            $notifiable ? $notifiable::class : null,
        );
    }
}
