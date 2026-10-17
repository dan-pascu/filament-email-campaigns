<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('filament-email-campaigns::translations.pages.confirmed.title') }}</title>
</head>
<body style="font-family: sans-serif; max-width: 32rem; margin: 4rem auto; text-align: center;">
    <h1>{{ __('filament-email-campaigns::translations.pages.confirmed.title') }}</h1>
    <p>{{ __('filament-email-campaigns::translations.pages.confirmed.body', ['email' => $subscriber->email, 'list' => $list->name]) }}</p>
</body>
</html>
