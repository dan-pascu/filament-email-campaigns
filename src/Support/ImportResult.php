<?php

namespace DanPascu\FilamentEmailCampaigns\Support;

class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $skippedSuppressed = 0;

    public int $skippedUnsubscribed = 0;

    public int $invalid = 0;

    public function merge(self $other): static
    {
        $this->created += $other->created;
        $this->updated += $other->updated;
        $this->skippedSuppressed += $other->skippedSuppressed;
        $this->skippedUnsubscribed += $other->skippedUnsubscribed;
        $this->invalid += $other->invalid;

        return $this;
    }

    public function total(): int
    {
        return $this->created + $this->updated + $this->skippedSuppressed + $this->skippedUnsubscribed + $this->invalid;
    }
}
