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
- **Furnizori de email** — contract `EspDriver` extensibil, cu șase drivere
  implementate: **Mailgun**, **Amazon SES**, **Postmark**, **Brevo**, **SendGrid**,
  **Resend** — fiecare trimite direct prin HTTP (fără SDK-uri), verifică semnătura
  propriului webhook și mapează evenimentele la `CampaignEventType`. Credențialele sunt
  stocate criptat, per tenant. Vezi secțiunea „Furnizori ESP" de mai jos.
- **Liste & abonați** — liste, abonați, etichete (tags) și un constructor vizual de
  segmente (grupuri de condiții combinate AND/OR, pe email/nume/etichete/data abonării/
  atribute extra, cu previzualizare live a numărului de potriviri). Vezi secțiunea
  „Constructorul de segmente" de mai jos.
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
- **Surse de abonați, import/export, API programatic** — import CSV cu mapare de
  coloane, plus un contract `Contracts\SubscriberSource` prin care aplicația găzduitoare
  poate oferi propriile surse (participanți la un eveniment, un export CRM, ...). Import
  și export rulează pe queue, cu notificare Filament (bază de date) la final. Pentru cod
  care nu trece prin panou, fațada `EmailCampaigns::subscribe()`/`unsubscribe()` face
  același lucru programatic. O listă de suprimare (`SuppressedEmail`) are acum și
  propria resursă Filament. Vezi secțiunea „Surse de abonați" de mai jos.
- **Confirmare dublu opt-in** — când o listă are `requires_confirmation` activ, orice
  abonat nou cu statusul "neconfirmat" primește automat un email cu un link semnat de
  confirmare; la click, statusul devine "abonat" și se declanșează automatizările ca la
  o abonare normală. Subiect/conținut editabile per-listă (cu un text implicit tradus),
  acțiune manuală de retrimitere, iar abonații neconfirmați sunt excluși din trimiterile
  de campanie. Vezi secțiunea „Confirmare dublu opt-in" de mai jos.

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

## Surse de abonați

Pe lângă import CSV (din acțiunea "Importă abonați" de pe o listă, cu mapare de
coloane configurabilă), aplicația găzduitoare poate oferi propriile surse — de
exemplu participanții la un eveniment stocat în propriile tale tabele — implementând
`Contracts\SubscriberSource`:

```php
use DanPascu\FilamentEmailCampaigns\Contracts\SubscriberSource;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;

class EventAttendeesSource implements SubscriberSource
{
    public function key(): string
    {
        return 'event_attendees';
    }

    public function label(): string
    {
        return 'Participanți la un eveniment';
    }

    public function options(int|string|null $tenantId): array
    {
        return Event::query()->where('tenant_id', $tenantId)->pluck('name', 'id')->all();
    }

    public function count(int|string|null $tenantId, ?string $option): int
    {
        return Attendee::where('event_id', $option)->count();
    }

    public function records(int|string|null $tenantId, ?string $option): iterable
    {
        foreach (Attendee::where('event_id', $option)->cursor() as $attendee) {
            yield new SubscriberData(email: $attendee->email, firstName: $attendee->first_name);
        }
    }
}
```

Înregistrează-o în Panel Provider:

```php
\DanPascu\FilamentEmailCampaigns\FilamentEmailCampaignsPlugin::make()
    ->subscriberSources([EventAttendeesSource::class]);
```

sau direct în config, `'subscriber_sources' => [App\Support\EventAttendeesSource::class]`.
Sursa apare automat în selectorul de surse din acțiunea de import, alături de CSV, cu
numărul de înregistrări afișat lângă fiecare opțiune.

Pentru cod care nu trece prin panou (webhook-uri, API-uri proprii, comenzi), fațada
`EmailCampaigns` abonează/dezabonează programatic, fără job pe queue:

```php
use DanPascu\FilamentEmailCampaigns\Facades\EmailCampaigns;
use DanPascu\FilamentEmailCampaigns\Support\SubscriberData;

EmailCampaigns::subscribe($list, new SubscriberData(email: 'client@exemplu.ro', firstName: 'Ion'));
EmailCampaigns::unsubscribe($list, 'client@exemplu.ro');
```

## Confirmare dublu opt-in

Când `EmailList::requires_confirmation` este activ (bifa "Necesită confirmare prin dublu
opt-in" din formularul listei), orice abonat care ajunge pe acea listă cu statusul
`unconfirmed` — prin import, prin `EmailCampaigns::subscribe(status: SubscriptionStatus::Unconfirmed)`
sau direct din panou — primește automat un email cu un link semnat de confirmare valabil
`tracking.signed_url_lifetime_days` zile. La click, statusul devine `subscribed`,
`subscribed_at` se completează și se declanșează automatizările de tip
"se abonează la o listă", exact ca la o abonare normală (ambele treceri de status se fac
prin salvarea pivotului `SubscriberList` ca model, nu printr-un update în masă).

Subiectul și conținutul emailului se editează per-listă (câmpurile apar în formular când
bifa de confirmare e activă); necompletate, se folosește un text implicit tradus
(en/ro). În conținut, `{confirmation_url}` este înlocuit cu linkul; dacă îl omiți, linkul
e adăugat automat la final. Din tab-ul de abonați al unei liste, acțiunea "Retrimite
confirmarea" retrimite emailul manual pentru un abonat neconfirmat. Abonații
neconfirmați sunt excluși din trimiterile de campanie (`CampaignRecipientResolver`
rezolvă doar abonați cu statusul `subscribed`).

## Constructorul de segmente

Din tab-ul "Segments" al unei liste, un segment nou se construiește direct din panou,
fără a scrie JSON de mână: un grup de reguli combinate ȚI/SAU, iar grupurile între ele
combinate tot ȚI/SAU — ex. „(etichetă = vip ȚI email termină cu @acme.com) SAU
(etichetă = lead)". Câmpuri disponibile: `email`, `first_name`, `last_name`, `tags`,
`subscribed_at` (relativ la data abonării pe *această* listă, operatori înainte/după/
egal, format `AAAA-LL-ZZ`) și orice `extra_attributes.<cheie>` (alege "Personalizat" ca
câmp). Sub formular apare o previzualizare live cu numărul de abonați care s-ar potrivi
chiar acum și primii zece, actualizată la fiecare modificare.

Formatul JSON existent (`Segment::conditions`, un singur grup implicit de reguli) rămâne
compatibil — `Segment::matches()` înțelege ambele forme — iar segmentele vechi se
„urcă" automat la forma nouă (cu grupuri) când le editezi din panou.

## Furnizori ESP

Din **Furnizori de email**, alege furnizorul din listă — formularul arată automat doar
câmpurile de credențiale relevante pentru el:

| Furnizor | Credențiale | Verificare webhook |
| --- | --- | --- |
| Mailgun | domeniu, cheie API privată, regiune | semnătură HMAC-SHA256 (timestamp+token) |
| Amazon SES | access key ID, secret access key, regiune AWS | evenimentele vin prin notificări Amazon SNS; semnătura plicului SNS e verificată cu certificatul de pe `SigningCertURL` (doar domenii `amazonaws.com`) |
| Postmark | server token | credențiale HTTP Basic Auth incluse în URL-ul webhook-ului din Postmark (stocate ca `user:parolă` în "Cheie de semnare webhook") |
| Brevo | cheie API | un token partajat trimis ca `?token=...` în query string-ul URL-ului webhook-ului |
| SendGrid | cheie API | semnătură ECDSA (P-256/SHA-256); cheia publică din dashboard-ul SendGrid se pune în "Cheie de semnare webhook" |
| Resend | cheie API | webhook-uri semnate prin Svix (HMAC-SHA256); secretul `whsec_...` din Resend se pune în "Cheie de semnare webhook" |

Toate cele șase trimit prin HTTP direct (fără SDK-uri AWS/Twilio/etc.) — Amazon SES
folosește un semnator SigV4 propriu (`Support\AwsSigV4Signer`), scris fără dependențe
noi. Corelarea unui eveniment de webhook cu trimiterea care l-a generat (`CampaignSend`)
se face fie prin `send_id` (transmis ca metadată/tag specifică fiecărui furnizor și
primită înapoi în webhook), fie, ca rezervă, prin id-ul de mesaj al furnizorului.

## Ce urmează

În ordine aproximativă după cât de apropiate sunt de ce ar trebui să facă un modul
complet de campanii email:

- **Formular public de abonare încorporabil** — import/export CSV și API-ul programatic
  (`EmailCampaigns::subscribe()`) există deja; rămâne un formular HTML de abonare
  pe care îl poți încorpora pe un site public, cu propriul flux de confirmare.
- **Automatizări cu ramificații** — motorul actual e o listă ordonată, liniară, de
  pași per automatizare. Ramificații/condiții per abonat nu sunt implementate.
- **Hartă de click-uri, statistici geo/dispozitiv** — evenimentele de click sunt
  înregistrate per link, dar nu există încă o hartă vizuală de click-uri sau o
  defalcare geo/dispozitiv a deschiderilor și click-urilor.
- **Panou pentru emailuri tranzacționale** — trimiterea funcționează (`TransactionalMail`
  + `AutomationRunner::sendEmail`), dar nu există încă o resursă/UI dedicată pentru a
  le vedea.

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
- Fișierele CSV de import și cele generate la export sunt scrise pe discul Laravel
  configurat în `filament-email-campaigns.storage.disk` (implicit `local`). Dacă
  importul/exportul rulează pe un worker de queue separat de procesul web, discul
  trebuie să fie partajat între ele (un disk de rețea/S3), altfel worker-ul nu va găsi
  fișierul încărcat de pe web sau invers.
- Constructorul de segmente a fost verificat prin teste automate pe logica de bază
  (`Segment::matches()`, formatul de conditions, serviciul de previzualizare), dar nu a
  fost verificat vizual într-un browser real (nu există deocamdată un mediu de testare
  cu browser pentru acest pachet).
- Cele cinci drivere ESP noi (SES, Postmark, Brevo, SendGrid, Resend) sunt verificate
  prin teste automate (cereri HTTP simulate cu `Http::fake()`, verificare de semnătură
  cu chei criptografice generate în test), dar **nu** au fost testate împotriva unui
  cont real al furnizorului respectiv — nu există credențiale/acces la rețea pentru asta
  în acest mediu. Formatul cererilor și al verificării semnăturii urmează documentația
  publică a fiecărui furnizor, dar merită un test cu un cont real înainte de producție.

## Traduceri

Fișierele de traducere sunt în `resources/lang/en/translations.php` și
`resources/lang/ro/translations.php`. Pentru a adăuga altă limbă, publică-le
(`php artisan vendor:publish --tag=filament-email-campaigns-translations`), copiază
fișierul într-un folder nou cu codul limbii respective și tradu valorile.

## Versionare și changelog

De la `v0.1.0`, pachetul urmează [Semantic Versioning](https://semver.org/) — orice
schimbare care rupe compatibilitatea crește versiunea majoră. Tot ce s-a schimbat
versiune cu versiune e în [CHANGELOG.md](CHANGELOG.md) (format
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/)). Instrucțiunea de instalare
de mai sus cu `:@dev` va deveni `composer require dan-pascu/filament-email-campaigns`
simplu, de la momentul în care taggul `v0.1.0` există pe `main` (vezi CHANGELOG pentru
ce conține).
