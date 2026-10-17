# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adopts
[Semantic Versioning](https://semver.org/) from this release onward.

## [Unreleased]

Nothing yet.

## [0.1.0] - 2026-10-17

First tagged release. Everything below was built across five pull requests on top of
the initial package; see each PR for full details.

### Added

- Multi-tenancy for every model (lists, subscribers, campaigns, providers,
  automations, suppression list), with two supported modes: Filament panel tenancy
  (`->tenant(...)`), or an app-defined resolver for apps without it
  (`FilamentEmailCampaignsPlugin::make()->resolveTenantUsing(...)`, or
  `config('filament-email-campaigns.tenant.resolver')` for code outside the panel).
  A single `TenantScope` global scope handles both modes and never filters when there
  is no current tenant (jobs, commands, a super-admin).
- Email lists, subscribers, tags, and a visual segment builder (AND/OR condition
  groups on email/first name/last name/tags/subscription date/extra attributes), with
  a live preview of the matching subscriber count and a sample of the first 10. The
  JSON `conditions` format stays backward compatible with the original flat shape.
- Campaigns: rich-text content, list + segment targeting, scheduling, manual sending.
- A/B testing: 2+ content variants, a configurable sample size, and automatic winner
  selection (by open or click rate) after a configurable wait, sending the winner to
  the rest of the list.
- Tracking: open pixel, per-link click tracking (signed URLs), an unsubscribe link, a
  per-campaign report page.
- Linear automations (wait / send email / add tag / remove tag / subscribe / unsubscribe)
  triggered when a subscriber subscribes/unsubscribes from a list or gains/loses a tag.
- Subscriber sources and bulk import/export: a `Contracts\SubscriberSource` contract
  host apps can implement for their own sources (event attendees, a CRM export, ...),
  plus a built-in CSV source with configurable column mapping. Import and export run
  on the queue and notify via a Filament database notification when finished. A
  `EmailCampaigns::subscribe()`/`unsubscribe()` facade covers code outside the panel. A
  suppression list (`SuppressedEmail`) got its own Filament resource.
- Double opt-in confirmation: when a list requires confirmation, a new unconfirmed
  subscriber automatically gets a signed confirmation email (subject/body editable
  per-list, with a translated default); confirming sets the subscriber to subscribed
  and triggers the same automations as a normal subscribe. A manual "resend
  confirmation" action is available per subscriber.
- Six email service provider drivers behind the `Contracts\EspDriver` contract, each
  sending over plain HTTP (no SDKs) and verifying its own webhook signature: Mailgun,
  Amazon SES (hand-rolled AWS SigV4 signing, events via Amazon SNS), Postmark (HTTP
  Basic Auth on the webhook URL), Brevo (a shared token in the webhook URL), SendGrid
  (ECDSA P-256/SHA-256 signature), and Resend (Svix HMAC-SHA256 signature).
- Full Romanian and English translations throughout the panel, kept in
  `resources/lang/{en,ro}/translations.php` with matching keys.

### Known limitations

See the README's "Limitări cunoscute" / "Known limitations" section for the current
list — in short: segment/link-rewriting logic is intentionally simple (no heavy HTML
parsing dependency); automations only reliably trigger from code paths that save the
`SubscriberList` pivot as a model instance, not from a bulk `attach()`/`sync()`; the
test suite runs on Laravel 12 because `orchestra/testbench` doesn't support Laravel 13
yet, even though the package itself allows it; import/export files need a shared
storage disk between web and queue workers; the segment builder and the five new ESP
drivers are covered by automated tests but have not been exercised in a live browser
or against a real provider account, respectively.
