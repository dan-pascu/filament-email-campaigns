<?php

return [

    'nav' => [
        'group' => 'Campanii email',
    ],

    'resources' => [
        'email_list' => [
            'label' => 'Listă',
            'plural_label' => 'Liste',
            'navigation_label' => 'Liste',
        ],
        'subscriber' => [
            'label' => 'Abonat',
            'plural_label' => 'Abonați',
            'navigation_label' => 'Abonați',
        ],
        'campaign' => [
            'label' => 'Campanie',
            'plural_label' => 'Campanii',
            'navigation_label' => 'Campanii',
        ],
        'email_provider' => [
            'label' => 'Furnizor de email',
            'plural_label' => 'Furnizori de email',
            'navigation_label' => 'Furnizori de email',
        ],
        'automation' => [
            'label' => 'Automatizare',
            'plural_label' => 'Automatizări',
            'navigation_label' => 'Automatizări',
        ],
        'suppressed_email' => [
            'label' => 'Email suprimat',
            'plural_label' => 'Emailuri suprimate',
            'navigation_label' => 'Emailuri suprimate',
        ],
    ],

    'fields' => [
        'name' => 'Nume',
        'email' => 'Email',
        'first_name' => 'Prenume',
        'last_name' => 'Nume de familie',
        'tags' => 'Etichete',
        'lists' => 'Liste',
        'list' => 'Listă',
        'subject' => 'Subiect',
        'from_name' => 'Nume expeditor',
        'from_email' => 'Email expeditor',
        'reply_to_email' => 'Email de răspuns',
        'content' => 'Conținut',
        'default_from_name' => 'Nume expeditor implicit',
        'default_from_email' => 'Email expeditor implicit',
        'default_reply_to_email' => 'Email de răspuns implicit',
        'email_provider' => 'Furnizor de email',
        'require_double_opt_in' => 'Necesită confirmare prin dublu opt-in',
        'subscribers' => 'Abonați',
        'provider' => 'Furnizor',
        'double_opt_in' => 'Dublu opt-in',
        'created_at' => 'Creat la',
        'segment_optional' => 'Segment (opțional)',
        'email_provider_override_optional' => 'Furnizor de email alternativ (opțional)',
        'run_as_ab_test' => 'Rulează ca test A/B',
        'sample_size_percent' => 'Mărimea eșantionului (%)',
        'pick_winner_by' => 'Alege câștigătorul după',
        'open_rate' => 'Rată de deschidere',
        'click_rate' => 'Rată de click',
        'send_at' => 'Trimite la',
        'status' => 'Status',
        'scheduled_at' => 'Programat la',
        'sent_at' => 'Trimis la',
        'is_default' => 'Implicit',
        'sending_domain' => 'Domeniu de trimitere',
        'private_api_key' => 'Cheie API privată',
        'region' => 'Regiune',
        'webhook_signing_key' => 'Cheie de semnare webhook',
        'is_enabled' => 'Activă',
        'steps' => 'Pași',
        'tag' => 'Etichetă',
        'minutes_to_wait' => 'Minute de așteptare',
        'order' => 'Ordine',
        'type' => 'Tip',
        'reason' => 'Motiv',
        'source' => 'Sursă',
        'source_option' => 'Opțiune sursă',
        'csv_file' => 'Fișier CSV',
        'csv_email_column' => 'Coloana email',
        'csv_first_name_column' => 'Coloana prenume',
        'csv_last_name_column' => 'Coloana nume de familie',
        'csv_tags_column' => 'Coloana etichete',
        'import_consent' => 'Confirm că am dreptul de a trimite emailuri către aceste adrese.',
        'confirmation_subject' => 'Subiectul emailului de confirmare',
        'confirmation_body' => 'Conținutul emailului de confirmare',
    ],

    'helper_text' => [
        'internal_campaign_name' => 'Nume intern, nu este afișat destinatarilor.',
        'segment_empty_sends_to_everyone' => 'Lasă necompletat pentru a trimite tuturor abonaților din listă.',
        'provider_defaults_to_list' => 'Implicit, se folosește furnizorul listei.',
        'variant_subject_empty_uses_main' => 'Lasă necompletat pentru a folosi subiectul principal.',
        'variant_content_empty_uses_main' => 'Lasă necompletat pentru a folosi conținutul principal.',
        'schedule_empty_keeps_draft' => 'Lasă necompletat pentru a păstra campania ca ciornă și a o trimite manual.',
        'webhook_signing_key' => 'Se găsește în setările webhook ale furnizorului. Este folosită pentru a verifica evenimentele primite (livrare/retur/click).',
        'automation_trigger' => 'Ce pornește această automatizare pentru un abonat.',
        'automation_actions' => 'Pașii se execută în ordine crescătoare pentru fiecare abonat înscris.',
        'confirmation_body' => 'Lasă necompletat pentru textul implicit. Include {confirmation_url} unde ar trebui să apară linkul de confirmare; dacă îl omiți, linkul este adăugat automat la final.',
    ],

    'sections' => [
        'trigger' => 'Declanșator',
        'actions' => 'Acțiuni',
    ],

    'steps' => [
        'content' => 'Conținut',
        'recipients' => 'Destinatari',
        'ab_testing' => 'Test A/B',
        'schedule' => 'Programare',
    ],

    'actions' => [
        'report' => 'Raport',
        'send_now' => 'Trimite acum',
        'import_subscribers' => 'Importă abonați',
        'export_subscribers' => 'Exportă abonați',
        'download' => 'Descarcă',
        'resend_confirmation' => 'Retrimite confirmarea',
    ],

    'filters' => [
        'list' => 'Listă',
    ],

    'placeholders' => [
        'none' => '—',
        'sending_domain_example' => 'mail.exemplu.ro',
    ],

    'notifications' => [
        'campaign_sending_title' => 'Campania este în curs de trimitere',
        'import_started' => 'Importul a început. Vei fi notificat când se termină.',
        'import_finished_title' => 'Import finalizat: :list',
        'import_finished_body' => ':created adăugați, :updated actualizați, :skipped_suppressed omiși (suprimați), :skipped_unsubscribed omiși (dezabonați), :invalid invalizi.',
        'export_started' => 'Exportul a început. Vei fi notificat când se termină.',
        'export_finished_title' => 'Export finalizat: :list',
        'export_finished_body' => 'Exportul tău este pregătit pentru descărcare.',
        'confirmation_resent' => 'Emailul de confirmare a fost retrimis.',
    ],

    'widgets' => [
        'overview' => [
            'lists' => 'Liste',
            'subscribed_contacts' => 'Contacte abonate',
            'campaigns_sent' => 'Campanii trimise',
        ],
    ],

    'pages' => [
        'campaign_report' => [
            'title' => 'Raport: :name',
            'stats' => [
                'sent' => 'Trimise',
                'delivered' => 'Livrate',
                'opened' => 'Deschise',
                'clicked' => 'Click-uri',
                'bounced' => 'Retururi',
                'complained' => 'Reclamații',
                'unsubscribed' => 'Dezabonări',
            ],
        ],
        'unsubscribed' => [
            'title' => 'Te-ai dezabonat',
            'body' => ':email nu va mai primi emailuri din ":list".',
        ],
        'confirmed' => [
            'title' => 'Abonare confirmată',
            'body' => ':email este acum confirmat pe ":list". Mulțumim!',
        ],
    ],

    'enums' => [
        'subscription_status' => [
            'unconfirmed' => 'Neconfirmat',
            'subscribed' => 'Abonat',
            'unsubscribed' => 'Dezabonat',
            'complained' => 'Reclamație',
            'bounced' => 'Retur',
        ],
        'campaign_status' => [
            'draft' => 'Ciornă',
            'scheduled' => 'Programată',
            'sending' => 'Se trimite',
            'sent' => 'Trimisă',
            'paused' => 'Pauzată',
            'cancelled' => 'Anulată',
        ],
        'campaign_event_type' => [
            'queued' => 'În așteptare',
            'sent' => 'Trimis',
            'delivered' => 'Livrat',
            'opened' => 'Deschis',
            'clicked' => 'Click',
            'bounced' => 'Retur',
            'complained' => 'Reclamație',
            'unsubscribed' => 'Dezabonare',
            'failed' => 'Eșuat',
        ],
        'automation_trigger_type' => [
            'subscribes_to_list' => 'Se abonează la o listă',
            'unsubscribes_from_list' => 'Se dezabonează de la o listă',
            'tag_added' => 'Se adaugă o etichetă',
            'tag_removed' => 'Se elimină o etichetă',
        ],
        'automation_action_type' => [
            'send_email' => 'Trimite un email',
            'wait' => 'Așteaptă',
            'add_tag' => 'Adaugă o etichetă',
            'remove_tag' => 'Elimină o etichetă',
            'subscribe_to_list' => 'Abonează la o listă',
            'unsubscribe_from_list' => 'Dezabonează de la o listă',
        ],
        'esp_provider' => [
            'mailgun' => 'Mailgun',
        ],
    ],

    'subscriber_sources' => [
        'csv' => [
            'label' => 'Fișier CSV',
        ],
    ],

    'confirmation' => [
        'default_subject' => 'Confirmă-ți abonarea la :list',
        'default_body' => '<p>Confirmă-ți abonarea la <strong>:list</strong> apăsând pe linkul de mai jos:</p><p>{confirmation_url}</p>',
    ],

];
