<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ([
            __('filament-email-campaigns::translations.pages.campaign_report.stats.sent') => $stats['sent'],
            __('filament-email-campaigns::translations.pages.campaign_report.stats.delivered') => $stats['delivered'],
            __('filament-email-campaigns::translations.pages.campaign_report.stats.opened') => $stats['opened'].' ('.$stats['open_rate'].'%)',
            __('filament-email-campaigns::translations.pages.campaign_report.stats.clicked') => $stats['clicked'].' ('.$stats['click_rate'].'%)',
            __('filament-email-campaigns::translations.pages.campaign_report.stats.bounced') => $stats['bounced'].' ('.$stats['bounce_rate'].'%)',
            __('filament-email-campaigns::translations.pages.campaign_report.stats.complained') => $stats['complained'],
            __('filament-email-campaigns::translations.pages.campaign_report.stats.unsubscribed') => $stats['unsubscribed'],
        ] as $label => $value)
            <div class="rounded-xl border p-4 fi-color-gray border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-semibold">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
