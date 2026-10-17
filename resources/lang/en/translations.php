<?php

return [

    'nav' => [
        'group' => 'Email campaigns',
    ],

    'resources' => [
        'email_list' => [
            'label' => 'List',
            'plural_label' => 'Lists',
            'navigation_label' => 'Lists',
        ],
        'subscriber' => [
            'label' => 'Subscriber',
            'plural_label' => 'Subscribers',
            'navigation_label' => 'Subscribers',
        ],
        'campaign' => [
            'label' => 'Campaign',
            'plural_label' => 'Campaigns',
            'navigation_label' => 'Campaigns',
        ],
        'email_provider' => [
            'label' => 'Email provider',
            'plural_label' => 'Email providers',
            'navigation_label' => 'Email providers',
        ],
        'automation' => [
            'label' => 'Automation',
            'plural_label' => 'Automations',
            'navigation_label' => 'Automations',
        ],
        'suppressed_email' => [
            'label' => 'Suppressed email',
            'plural_label' => 'Suppressed emails',
            'navigation_label' => 'Suppressed emails',
        ],
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'tags' => 'Tags',
        'lists' => 'Lists',
        'list' => 'List',
        'subject' => 'Subject',
        'from_name' => 'From name',
        'from_email' => 'From email',
        'reply_to_email' => 'Reply-to email',
        'content' => 'Content',
        'default_from_name' => 'Default from name',
        'default_from_email' => 'Default from email',
        'default_reply_to_email' => 'Default reply-to email',
        'email_provider' => 'Email provider',
        'require_double_opt_in' => 'Require double opt-in confirmation',
        'subscribers' => 'Subscribers',
        'provider' => 'Provider',
        'double_opt_in' => 'Double opt-in',
        'created_at' => 'Created at',
        'segment_optional' => 'Segment (optional)',
        'email_provider_override_optional' => 'Email provider override (optional)',
        'run_as_ab_test' => 'Run this as an A/B test',
        'sample_size_percent' => 'Sample size (%)',
        'pick_winner_by' => 'Pick the winner by',
        'open_rate' => 'Open rate',
        'click_rate' => 'Click rate',
        'send_at' => 'Send at',
        'status' => 'Status',
        'scheduled_at' => 'Scheduled at',
        'sent_at' => 'Sent at',
        'is_default' => 'Default',
        'sending_domain' => 'Sending domain',
        'private_api_key' => 'Private API key',
        'region' => 'Region',
        'webhook_signing_key' => 'Webhook signing key',
        'is_enabled' => 'Enabled',
        'steps' => 'Steps',
        'tag' => 'Tag',
        'minutes_to_wait' => 'Minutes to wait',
        'order' => 'Order',
        'type' => 'Type',
        'reason' => 'Reason',
        'source' => 'Source',
        'source_option' => 'Source option',
        'csv_file' => 'CSV file',
        'csv_email_column' => 'Email column',
        'csv_first_name_column' => 'First name column',
        'csv_last_name_column' => 'Last name column',
        'csv_tags_column' => 'Tags column',
        'import_consent' => 'I confirm I have the right to email these addresses.',
    ],

    'helper_text' => [
        'internal_campaign_name' => 'Internal name, not shown to recipients.',
        'segment_empty_sends_to_everyone' => 'Leave empty to send to everyone subscribed to the list.',
        'provider_defaults_to_list' => "Defaults to the list's provider.",
        'variant_subject_empty_uses_main' => 'Leave empty to use the main subject.',
        'variant_content_empty_uses_main' => 'Leave empty to use the main content.',
        'schedule_empty_keeps_draft' => 'Leave empty to keep this campaign as a draft you send manually.',
        'webhook_signing_key' => "Found on the provider's webhook settings page. Used to verify incoming delivery/bounce/click events.",
        'automation_trigger' => 'What starts this automation for a subscriber.',
        'automation_actions' => 'Steps run in ascending order for every enrolled subscriber.',
    ],

    'sections' => [
        'trigger' => 'Trigger',
        'actions' => 'Actions',
    ],

    'steps' => [
        'content' => 'Content',
        'recipients' => 'Recipients',
        'ab_testing' => 'A/B testing',
        'schedule' => 'Schedule',
    ],

    'actions' => [
        'report' => 'Report',
        'send_now' => 'Send now',
        'import_subscribers' => 'Import subscribers',
        'export_subscribers' => 'Export subscribers',
        'download' => 'Download',
    ],

    'filters' => [
        'list' => 'List',
    ],

    'placeholders' => [
        'none' => '—',
        'sending_domain_example' => 'mail.example.com',
    ],

    'notifications' => [
        'campaign_sending_title' => 'Campaign is being sent',
        'import_started' => 'Import started. You will be notified when it finishes.',
        'import_finished_title' => 'Import finished: :list',
        'import_finished_body' => ':created added, :updated updated, :skipped_suppressed skipped (suppressed), :skipped_unsubscribed skipped (unsubscribed), :invalid invalid.',
        'export_started' => 'Export started. You will be notified when it finishes.',
        'export_finished_title' => 'Export finished: :list',
        'export_finished_body' => 'Your export is ready to download.',
    ],

    'widgets' => [
        'overview' => [
            'lists' => 'Lists',
            'subscribed_contacts' => 'Subscribed contacts',
            'campaigns_sent' => 'Campaigns sent',
        ],
    ],

    'pages' => [
        'campaign_report' => [
            'title' => 'Report: :name',
            'stats' => [
                'sent' => 'Sent',
                'delivered' => 'Delivered',
                'opened' => 'Opened',
                'clicked' => 'Clicked',
                'bounced' => 'Bounced',
                'complained' => 'Complained',
                'unsubscribed' => 'Unsubscribed',
            ],
        ],
        'unsubscribed' => [
            'title' => "You've been unsubscribed",
            'body' => ':email will no longer receive emails from ":list".',
        ],
    ],

    'enums' => [
        'subscription_status' => [
            'unconfirmed' => 'Unconfirmed',
            'subscribed' => 'Subscribed',
            'unsubscribed' => 'Unsubscribed',
            'complained' => 'Complained',
            'bounced' => 'Bounced',
        ],
        'campaign_status' => [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'sending' => 'Sending',
            'sent' => 'Sent',
            'paused' => 'Paused',
            'cancelled' => 'Cancelled',
        ],
        'campaign_event_type' => [
            'queued' => 'Queued',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'opened' => 'Opened',
            'clicked' => 'Clicked',
            'bounced' => 'Bounced',
            'complained' => 'Complained',
            'unsubscribed' => 'Unsubscribed',
            'failed' => 'Failed',
        ],
        'automation_trigger_type' => [
            'subscribes_to_list' => 'Subscribes to a list',
            'unsubscribes_from_list' => 'Unsubscribes from a list',
            'tag_added' => 'Tag is added',
            'tag_removed' => 'Tag is removed',
        ],
        'automation_action_type' => [
            'send_email' => 'Send an email',
            'wait' => 'Wait',
            'add_tag' => 'Add a tag',
            'remove_tag' => 'Remove a tag',
            'subscribe_to_list' => 'Subscribe to a list',
            'unsubscribe_from_list' => 'Unsubscribe from a list',
        ],
        'esp_provider' => [
            'mailgun' => 'Mailgun',
        ],
    ],

    'subscriber_sources' => [
        'csv' => [
            'label' => 'CSV file',
        ],
    ],

];
