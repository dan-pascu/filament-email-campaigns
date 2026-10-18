<?php

namespace DanPascu\FilamentEmailCampaigns\Services;

use DanPascu\FilamentEmailCampaigns\Models\EmailList;
use DanPascu\FilamentEmailCampaigns\Models\Segment;
use DanPascu\FilamentEmailCampaigns\Models\Subscriber;
use Illuminate\Support\Collection;

/**
 * Evaluates a not-yet-saved set of segment conditions against a list's
 * current subscribers, for the live preview in the segment builder.
 */
class SegmentPreviewer
{
    /**
     * @param  array<string, mixed>  $conditions
     * @return Collection<int, Subscriber>
     */
    public function matches(EmailList $list, array $conditions): Collection
    {
        $segment = new Segment(['email_list_id' => $list->id, 'conditions' => $conditions]);

        return $list->subscribers()->get()->filter(fn (Subscriber $subscriber) => $segment->matches($subscriber))->values();
    }

    /**
     * @param  array<string, mixed>  $conditions
     * @return array{count: int, sample: string[]}
     */
    public function preview(EmailList $list, array $conditions): array
    {
        $matches = $this->matches($list, $conditions);

        return [
            'count' => $matches->count(),
            'sample' => $matches->take(10)->pluck('email')->all(),
        ];
    }
}
