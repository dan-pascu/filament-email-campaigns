# Filament Email Campaigns

Modul de management al campaniilor de email (liste, abonați, campanii, test A/B,
automatizări, statistici), construit ca plugin pentru [Filament 5](https://filamentphp.com)
(Laravel 13), cu suport multi-tenant integrat din prima zi.

Interfața este disponibilă în **română** și **engleză** (limba se alege automat după
locale-ul aplicației host, `app()->getLocale()`).

## Status

Acesta este un nucleu funcțional, nu un produs complet. Ce există acum merge de la un
cap la altul (trimiți o campanie reală prin Mailgun, urmărești deschideri/click-uri/
retururi, rulezi un test A/B, rulezi automatizări simple), dar câteva funcționalități
sunt, intenționat, încă neconstruite. Vezi [Ce urmează](#ce-urmează) mai jos înainte să
te bazezi pe el în producție.

## Ce există acum

- **Multi-tenancy** — fiecare model (liste, abonați, campanii, furnizori,
  automatizări, ...) apartine unui tenant. Funcționează în două moduri:
  - cu tenancy-ul Filament (`->tenant(...)` pe panou), ca până acum;
  - **fără tenancy Filament** — `FilamentEmailCampaignsPlugin::make()->resolveTenantUsing(fn () => ...)`
    (sau, pentru cod din afara panoului, `'tenant' => ['resolver' => SomeInvokableClass::class]`
    în config), pentru aplicații cu propriul sistem de tenancy (ex. un `client_id`
    pe utilizator). Un singur `TenantScope` global, aplicat automat pe orice model cu
    `tenant_id`, filtrează după tenant-ul curent indiferent de mod, și nu filtrează
    nimic când nu există un tenant curent (job-uri, comenzi, un super-admin). Vezi
    `Support\TenantResolver`.
- **Furnizori de email** — contract `EspDriver` extensibil; **Mailgun** este singurul
  driver implementat momentan. Credențialele sunt stocate criptat, per tenant.
- **Liste & abonați** — liste, abonați, etichete (tags) și un constructor de segmente
  (grupuri simple de condiții AND/OR pe email/etichete).
- **Campanii** — conținut rich-text, targetare pe listă + segment, programare
  (`scheduled_at` + o comandă cron la fiecare minut), trimitere manuală ("Trimite acum").
- **Test A/B** — 2+ variante de conținut, un eșantion configurabil (%) și alegerea
  automată a câștigătorului (după rata de deschidere sau de click) după un timp de
  așteptare configurabil, cu trimiterea variantei câștigătoare către restul listei.
- **Tracking** — pixel de deschidere și tracking de click per link (URL-uri semnate),
  link de dezabonare, o pagină de raport per campanie cu numărul și rata de
  trimise/livrate/deschise/click-uri/retururi/reclamații/dezabonări.
- **Webhook-uri** — evenimentele Mailgun (livrare/deschidere/click/retur/reclamație/
  dezabonare) sunt verificate (semnătură HMAC) și procesate, actualizând automat
  statusul abonatului și o listă de suprimare.
- **Automatizări (de bază)** — o secvență *liniară* de pași (așteptare / trimite email /
  adaugă etichetă / elimină etichetă / abonează / dezabonează) declanșată când un
  abonat se abonează/dezabonează de la o listă sau primește/pierde o etichetă. Nu este
  un constructor vizual cu ramificații — vezi secțiunea de mai jos.

## Instalare într-un proiect existent

1. Adaugă pachetul în `composer.json`. Repo-ul nu are încă un release/tag, așa că
   trebuie cerută explicit versiunea de dezvoltare:

   ```bash
   composer require dan-pascu/filament-email-campaigns:@dev
   ```

   (După ce creezi un tag/release pe GitHub — Releases → "Create a new release" →
   ex. `v0.1.0` — poți cere pachetul normal, cu `composer require dan-pascu/filament-email-campaigns`.)

2. Publică și rulează migrațiile:

   ```bash
   php artisan vendor:publish --tag=filament-email-campaigns-migrations
   php artisan migrate
   ```

3. Configurează modelul de tenant în `config/filament-email-campaigns.php`
   (`php artisan vendor:publish --tag=filament-email-campaigns-config`):

   ```php
   'tenant' => [
       'model' => App\Models\Team::class,
       'foreign_key' => 'tenant_id',
       'relationship' => 'tenant',
   ],
   ```

4. Înregistrează tenancy-ul panoului cu **același nume de relație**, și adaugă
   plugin-ul, în Panel Provider-ul tău:

   ```php
   $panel
       ->tenant(Team::class, ownershipRelationship: 'tenant')
       ->plugin(\DanPascu\FilamentEmailCampaigns\FilamentEmailCampaignsPlugin::make());
   ```

5. Asigură-te că rulează un worker de queue (`php artisan queue:work`) și scheduler-ul
   e pornit (`php artisan schedule:work`, sau intrarea ta de cron) — trimiterea
   campaniilor, programarea și automatizările trec toate prin queue și scheduler.

6. Setează limba interfeței în `config/app.php` (`'locale' => 'ro'`) sau per-request,
   după cum are nevoie aplicația ta — pachetul urmează automat locale-ul curent.

7. Din panou, mergi la **Furnizori de email** și adaugă un domeniu de trimitere
   Mailgun + cheia API privată, apoi conectează o listă la el. În dashboard-ul
   Mailgun, adaugă un webhook (pentru delivered/opened/clicked/permanent_fail/
   complained/unsubscribed) către:

   ```
   https://aplicatia-ta.test/email-campaigns/webhooks/{email_provider_id}
   ```

   și copiază cheia de semnare a webhook-ului în același record de furnizor.

## Configurare

Vezi `config/filament-email-campaigns.php` pentru toate opțiunile: modelul/coloana de
tenant, coada/conexiunea de trimitere și limita de rată (emailuri/secundă, ca să nu
depășești limitele furnizorului), timpul de așteptare implicit pentru testul A/B,
comutatoarele de tracking, și harta `providers` (clasa driver per furnizor) — acesta e
punctul de extensie pentru adăugarea altui furnizor mai târziu (implementezi
`DanPascu\FilamentEmailCampaigns\Contracts\EspDriver` și îl adaugi în hartă).

### Mod fără tenancy Filament

Dacă aplicația ta nu folosește `->tenant(...)` pe panoul Filament, ai două variante
echivalente pentru a spune pachetului care e tenant-ul curent:

```php
// în Panel Provider, alături de ->plugin(...)
\DanPascu\FilamentEmailCampaigns\FilamentEmailCampaignsPlugin::make()
    ->resolveTenantUsing(fn (): int|string|null => auth()->user()?->client_id);
```

```php
// config/filament-email-campaigns.php — pentru cod care rulează în afara
// panoului (job-uri, comenzi, webhook-uri) și nu are context de autentificare
'tenant' => [
    'resolver' => App\Support\CurrentClientResolver::class, // o clasă invocabilă: __invoke(): int|string|null
],
```

Codul pachetului care rulează în afara panoului (trimiterea campaniilor, webhook-urile,
rutele de tracking, comenzile de scheduling) nu depinde niciodată de `auth()` sau de
resolver: identifică înregistrările explicit, după id, indiferent de tenant-ul curent
(`Models\Scopes\TenantScope::withoutScoping(...)`).

## Ce urmează

În ordine aproximativă după cât de apropiate sunt de ce ar trebui să facă un modul
complet de campanii email:

- **Mai mulți furnizori ESP** — Postmark, SES, SendGrid, Brevo, Resend. Contractul
  `EspDriver` există deja pentru asta; doar Mailgun e implementat.
- **Flux de confirmare dublu opt-in** — coloana `EmailList::requires_confirmation`
  există, dar emailul de confirmare + link-ul de confirmare nu sunt încă legate.
- **Formulare publice de abonare/import & API public** — nu există încă un formular
  de abonare încorporabil sau import/export CSV; abonații se gestionează doar din
  panoul de administrare.
- **UI pentru segmente** — segmentele sunt stocate și evaluate (`Segment::matches()`),
  dar nu există încă o interfață Filament dedicată pentru a le construi; sunt JSON
  simplu, momentan.
- **Automatizări cu ramificații** — motorul actual e o listă ordonată, liniară, de
  pași per automatizare. Ramificații/condiții per abonat nu sunt implementate.
- **Hartă de click-uri, statistici geo/dispozitiv** — evenimentele de click sunt
  înregistrate per link, dar nu există încă o hartă vizuală de click-uri sau o
  defalcare geo/dispozitiv a deschiderilor și click-urilor.
- **Panou pentru emailuri tranzacționale** — trimiterea funcționează (`TransactionalMail`
  + `AutomationRunner::sendEmail`), dar nu există încă o resursă/UI dedicată pentru a
  le vedea.
- **Import/export abonați în masă, UI pentru lista de suprimare** — ambele există ca
  tabele, fără ecrane Filament dedicate încă.

## Limitări cunoscute

- Evaluarea segmentelor și rescrierea link-urilor sunt intenționat simple (evaluare
  în PHP a condițiilor, rescriere `href` prin regex) în loc de parsare HTML completă,
  ca să păstrăm pachetul fără dependențe grele. Liste foarte mari sau HTML scris de
  mână neobișnuit pot avea nevoie de o abordare mai robustă mai târziu.
- Înscrierea în automatizări (`AutomationEnroller`) este declanșată la salvarea unei
  *instanțe* de model `SubscriberList` (`SubscriberList::booted()`), fiindcă Eloquent
  nu declanșează evenimente de model pentru `attach()`/`sync()`/`detach()` în masă.
  Schimbările de status prin webhook-uri, link-ul de dezabonare și `AutomationRunner`
  trec toate printr-o salvare de model și declanșează corect automatizările. Atașarea
  manuală a unui abonat la o listă din panou (acțiunea "Attach" din relation manager,
  sau checkbox-urile de liste din formularul abonatului) **nu** declanșează momentan o
  automatizare de tip `subscribes_to_list`, fiindcă Filament face asta printr-un
  `sync()` în masă. Dacă ai nevoie ca automatizările să se declanșeze și din panou,
  înlocuiește acele apeluri `attach`/`sync` cu `SubscriberList::updateOrCreate(...)`
  într-o acțiune personalizată.
- La momentul scrierii, `orchestra/testbench` nu suportă încă Laravel 13, așa că suita
  de teste automate rulează (și e verificată) pe Laravel 12, nu pe 13; `composer.json`
  al pachetului permite în continuare Laravel 13 pentru instalări reale, fiindcă nimic
  de aici nu folosește API-uri schimbate între 12 și 13.

## Traduceri

Fișierele de traducere sunt în `resources/lang/en/translations.php` și
`resources/lang/ro/translations.php`. Pentru a adăuga altă limbă, publică-le
(`php artisan vendor:publish --tag=filament-email-campaigns-translations`), copiază
fișierul într-un folder nou cu codul limbii respective și tradu valorile.
