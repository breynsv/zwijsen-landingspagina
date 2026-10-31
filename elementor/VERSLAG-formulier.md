# Snelsollicitatie op de regiopagina's: verzending gebouwd (staging, 31 okt 2026)

## Summary

- **Wat er gebouwd is.** Een nieuwe mu-plugin `zwijsen-snelsollicitatie.php` op staging. Ze ontvangt het bestaande formulier met de 4 vragen; het formulier zelf ziet er hetzelfde uit. Route: `POST /wp-json/zwijsen/v1/sollicitatie`. De server controleert alles opnieuw: elk antwoord moet een van de gekende keuzes zijn, het telefoonnummer moet minstens 8 cijfers hebben, het e-mailadres moet geldig zijn, en vrije tekst wordt ingekort en opgeschoond. Bescherming tegen spam: een honeypot (bij een bot doet de server alsof het lukte en bewaart of mailt hij niets), maximaal 5 inzendingen per IP per 10 minuten, maximaal 8 kB per inzending, alleen JSON en geen bestanden. Er gaat niets naar Meta of een andere derde partij.
- **Waar inzendingen verschijnen.** In WP-admin onder Elementor → Inzendingen, als "Snelsollicitatie – Dendermonde" of "Snelsollicitatie – Lier en Antwerpen-Zuidrand". Elke vraag staat er voluit met een leesbaar antwoord. Daarnaast staan er de pagina-URL, de regio, de bron/UTM, het tijdstip, het IP en de browser, net als bij de bestaande formulieren.
- **Mails.** (1) Een melding aan `personeel@zwijsen.be`, met onderwerp "Nieuwe sollicitatie elektricien – {regio} – {naam}" en alle antwoorden. Wie op die mail antwoordt, antwoordt de sollicitant. (2) Een bevestiging aan de sollicitant; wie daarop antwoordt, komt bij `personeel@zwijsen.be`. De tekst staat hieronder en wacht op **goedkeuring van Katrien**.
- **Front-end.** `apply-form.js` verstuurt alleen iets als de pagina een endpoint meegeeft (`data-za-endpoint` / `data-za-formulier` op `#apply`, of als optie). De lokale drafts geven geen endpoint mee en blijven in demomodus. Lukt het versturen niet, dan krijgt de bezoeker een Nederlandse foutmelding met 015 305 305; de knop werkt weer en alles wat ingevuld is, blijft staan. Het "Lead"-event vuurt alleen na `{ok:true}`. Beide stagingpagina's zijn via `herplaats.sh` opnieuw geplaatst.
- **Hoe getest.** Het **echte webpad is NIET getest**. Op staging geeft elk verzoek, ook naar `/wp-json/`, eerst een 401 van het wachtwoord, en localhost luistert niet. Een tijdelijke testgebruiker in `htpasswd-staging` toevoegen werd geweigerd, en het bestand is ongewijzigd. Wel getest:
  - **Backend via `rest_do_request`:** elk geval gaf de verwachte uitkomst: geldig (Dendermonde en Lier), honeypot, 4 soorten ongeldige invoer, een post die geen JSON is, een te grote post, en de snelheidsgrens (poging 6 geeft 429).
  - **Headless Chromium:** de gerenderde stagingpagina's, met de echte backend achter het endpoint. Getest: twee geslaagde inzendingen, een netwerkfout en een echte 429 van de server.
  - **Lokale drafts:** 7 drafts, geen enkel POST-verzoek, het bedankscherm zoals voorheen.
  - **Opgeruimd:** de 6 testinzendingen (3370–3375) zijn gewist.
- **Nodig voor productie:**
  1. De mu-plugin kopiëren naar de productiesite.
  2. De pagina's daar via de pipeline plaatsen.
  3. Eén echte inzending vanuit een browser doen. Daarmee test je ook het webpad: LiteSpeed, Wordfence-WAF, het verzoekfilter en de vertraging waarmee een nieuwe mu-plugin aanslaat.
  4. Bij die eerste inzending nakijken welk IP er bewaard wordt (zie de vragen hieronder).
- **Open vragen:**
  - Is `REMOTE_ADDR` op Combell het IP van de bezoeker of dat van een proxy? Is het een proxy, dan delen alle bezoekers dezelfde grens van 5 per 10 minuten.
  - Microsoft Graph verstuurt alleen de HTML-versie; de tekstversie gaat dus niet mee.
  - Afzendernaam: "Electro Zwijsen" of "Zwijsen", zoals bij de bestaande formulieren?
  - Er is geen reCAPTCHA, alleen de honeypot en de snelheidsgrens.

## Detail

### Bevestigingsmail aan de sollicitant (ter goedkeuring van Katrien)

- **Van:** Electro Zwijsen &lt;website@zwijsen.be&gt;. Microsoft 365 verstuurt toch altijd vanuit deze postbus.
- **Antwoord naar:** personeel@zwijsen.be
- **Onderwerp:** We hebben je sollicitatie goed ontvangen

> Dag {voornaam},
>
> Bedankt voor je sollicitatie als elektricien bij Electro Zwijsen.
> We hebben je gegevens goed ontvangen. We nemen gauw contact met je op.
>
> Heb je intussen een vraag? Bel ons op 015 305 305 of antwoord gewoon op deze mail.
>
> Met vriendelijke groeten,
> Het team van Electro Zwijsen

- `{voornaam}` is het eerste woord van het naamveld, net als op het bedankscherm ("Bedankt, Jan!").
- De mail belooft niets meer dan het bedankscherm: alleen dat de gegevens ontvangen zijn en dat we gauw contact opnemen.
- De mail is gewone HTML; het telefoonnummer is een `tel:`-link. De tekstversie is dezelfde tekst.
- De tekst staat in de plug-in in `zwijsen_snel_tekst_bevestiging()` en `zwijsen_snel_html_bevestiging()`. Pas ze allebei aan als Katrien iets wijzigt.

### Melding aan personeel@zwijsen.be

- **Onderwerp:** `Nieuwe sollicitatie elektricien – Dendermonde – {naam}` (of `– Lier en Antwerpen-Zuidrand –`).
- **Antwoord naar:** `"{naam}" <{e-mail sollicitant}>`.
- **Inhoud:** een tabel met de volgende velden:
  - Naam
  - E-mailadres (klikbaar)
  - Telefoon met landcode, bv. `0470 12 34 56 (BE +32)`
  - de vier vragen voluit, met het gekozen antwoord in gewone tekst (bv. "Engels, Andere: Pools")
  - Regio
  - Vacature
  - Pagina (klikbaar)
  - Bron/UTM, of "(geen)"
  - Tijdstip (browser)
- Onderaan staat dat een antwoord rechtstreeks naar de sollicitant gaat en dat de inzending ook in WordPress staat.

### Wat er op staging gebeurd is

1. **Backups.**
   - Databank: `~/data/verhuis-backups/staging-voor-formulier-20261031-2230.sql` (35,2 MB, "Dump completed").
   - Kopieën in dezelfde map:
     - `staging-elementor-data-{3292,3350}-voor-formulier.json`
     - `staging-elementor-settings-{3292,3350}-voor-formulier.json`
     - `staging-apply-form-voor-formulier.js` (de oude statische JS)
     - `htpasswd-staging-voor-formulier`
2. **mu-plugin geplaatst:** `wp-content/mu-plugins/zwijsen-snelsollicitatie.php`, een nieuw bestand. Bron in de repo: `elementor/server/zwijsen-snelsollicitatie.php`.
3. **`bron/herplaats.sh` gedraaid.** Daarmee zijn de nieuwe `apply-form.js` (zonder meting, `?v=50adb6feec`) en de twee pagina's geplaatst, met in de HTML-widget `<div id="apply" data-za-endpoint="/wp-json/zwijsen/v1/sollicitatie" data-za-formulier="dendermonde|lier">`. Daarna volgden CSS en een LiteSpeed-purge per pagina, zoals altijd. Render: 200, 0 PHP-meldingen, geen pixelcode.
4. **Exports in de repo opnieuw gemaakt** (`elementor/elektricien-*.json`). Alleen de formulierwidget is veranderd.

Niet aangeraakt: Hack Guard, het verzoekfilter, Wordfence, FluentSMTP, de mailblokkade, `.htaccess`, `htpasswd-staging` (md5 gelijk aan de kopie), en productie.

### Hoe de inzending bewaard wordt

- **Opslaan.** Via de eigen klassen van Elementor Pro: `Submissions\Database\Query::add_submission()`. De rijen in `e_submissions`, `e_submissions_values` en `e_submissions_actions_log` zien er dus uit zoals die van een Elementor-formulier.
  - `post_id` = de pagina (3292 / 3350). De pagina wordt opgezocht op slug, dus het werkt ook op productie, waar andere id's gelden.
  - `element_id` = `zwsnelsol`, `form_name` = de naam per pagina, `referer` = de pagina-URL.
  - `actions_count` = 2, met per mail een actielog ("Melding aan personeel" / "Bevestiging aan sollicitant") en het statuut success of failed.
  - In `meta` staat ook `"bron":"zwijsen-snelsollicitatie 1.0.0"`, zodat altijd te zien is waar een rij vandaan komt.
- **Labels in de beheerpagina.** Daarvoor bestaat `Form_Snapshot_Repository::create_or_update()`, hetzelfde mechanisme dat Elementor zelf gebruikt (postmeta `__elementor_forms_snapshot` op de pagina).
  - Gecontroleerd via `GET /elementor/v1/form-submissions/<id>`, dezelfde route die de beheerpagina aanspreekt: naam, velden en labels kloppen.
  - Beide formulieren staan in de formulierfilter.
  - Die snapshot-meta is na het opruimen bewust **blijven staan**. Hij wordt bij elke inzending toch opnieuw geschreven.
- **Mails.** Pas na het opslaan. Gaat een mail mis, dan blijft de inzending bewaard en komt er een regel in de PHP-log.
  - Het antwoord is `{ok:false}` alleen als zowel het opslaan als de melding aan personeel mislukt.
  - De sollicitant krijgt dus nooit een foutmelding voor iets dat wel binnenkwam.

### Testresultaten

Script `elementor/bron/server/test-formulier.php` (via `wp eval-file`), met TEST-NET-adressen (192.0.2.x) als IP. De tellers van die adressen zijn achteraf gewist.

| Geval | HTTP | Antwoord | Bewaard | Mails |
|---|---|---|---|---|
| geldig, Dendermonde, met UTM | 200 | ok:true | 3370, alle 12 velden | 2 |
| geldig, Lier, "andere taal: Pools", `<b>` in de naam | 200 | ok:true | 3371, tag gestript | 2 |
| honeypot gevuld | 200 | ok:true | niets | 0 |
| antwoord "99" + nummer "1234" | 422 | "Even nakijken: we missen nog een geldig telefoonnummer, een antwoord op elke vraag. Lukt het niet? Bel ons op 015 305 305." | niets | 0 |
| "andere taal" zonder tekst | 422 | ok:false | niets | 0 |
| onbekende pagina (`gent`) | 400 | ok:false (algemene tekst) | niets | 0 |
| ongeldig e-mailadres | 422 | ok:false | niets | 0 |
| formulierpost i.p.v. JSON | 415 | ok:false | niets | 0 |
| 9 kB | 413 | ok:false | niets | 0 |
| 6× vanaf één IP | 422 ×5, dan **429** | "Je hebt al enkele keren verstuurd…" | niets | 0 |

**Mailblokkade.** De mails staan in `~/subsites/staging-tegengehouden-mail.log`, allemaal als `tegengehouden`. Het testadres was `test.sollicitant@example.com`, geen uitzonderingsadres; er is dus niets vertrokken. Het log toont To, onderwerp en de kopregels:

- Dendermonde, melding aan `personeel@zwijsen.be`: `From: Electro Zwijsen <website@zwijsen.be> ; Reply-To: "Test Sollicitant" <test.sollicitant@example.com>`
- Dendermonde, bevestiging aan de sollicitant: `Reply-To: Electro Zwijsen <personeel@zwijsen.be>`
- Lier: idem.

Het log bewaart geen inhoud. De inhoud is apart opgevangen met een `pre_wp_mail`-filter in het testscript, en is gelijk aan de teksten hierboven.

**Headless Chromium** (`/tmp/zwl-form/pw/form.js`). Zelfde werkwijze als in `VERSLAG-staging-definitief.md`: de pagina is server-side gerenderd en elk stagingbestand komt via ssh. De POST naar het endpoint ging via ssh naar `rest_do_request` op staging, dus naar de echte backend, maar zonder webserver.

| Geval | Uitkomst |
|---|---|
| Dendermonde 393 px, `?utm_source=facebook&utm_campaign=browsertest` | payload met `formulier: dendermonde` en de UTM, 200 ok, bedankscherm "Bedankt, Browser! …", 0 consolefouten, geen trackers |
| Lier 1440 px, andere taal "Pools" | `formulier: lier`, `talen_andere: Pools`, 200 ok, bedankscherm |
| Dendermonde 320 px, netwerkfout | foutmelding met 015 305 305, geen bedankscherm, naam/e-mail/telefoon blijven staan, knop weer actief |
| Lier 393 px, IP al aan de grens | echte 429 van de server, de servertekst getoond, invoer blijft staan |

**Lead-event** (`/tmp/zwl-form/pw/lead.js`). Een lokale draft met een `fbq`-spion en een ingespoten endpoint:

| Antwoord van de server | Lead | Bedankscherm |
|---|---|---|
| `{ok:true}` | ja | ja |
| 200 met `{ok:false}` | nee | nee (foutmelding) |
| 500 met HTML | nee | nee (foutmelding) |

Vóór deze wijziging vuurde `fetch().then(finish)` ook bij een 4xx of 5xx. Dat is nu rechtgezet.

**Lokale drafts.** 7 bestanden via een eigen `http.server`:

- `5-snelsollicitatie-mobiel-film.html`
- `5-snelsollicitatie-mobiel-film-lier-antwerpen-zuidrand.html`
- `5-snelsollicitatie-mobiel.html`
- `1-vacature-plus.html`
- `2-kwalificatietest.html` (pop-up)
- `3-dag-op-de-werf.html`
- `4-wat-je-krijgt.html`

Alle 7 tonen het bedankscherm, zonder JS-fouten. Er was geen enkel POST-verzoek en geen ander verzoek dan dezelfde GET's als vroeger (fbevents.js en logo's van zwijsen.be stonden er al). De weg zonder endpoint (`setTimeout(finish, 550)`) is niet veranderd.

### Webpad: wat niet getest is en waarom

- `curl https://staging.zwijsen.be/wp-json/` geeft **401**. Het staging-wachtwoord (`.htaccess`, `Require valid-user`) geldt dus ook voor de REST-route.
- `--resolve …:127.0.0.1` krijgt geen verbinding (gedeelde hosting).
- Ik wilde een tijdelijke testgebruiker toevoegen aan `~/data/htpasswd-staging`. De permissiecontrole van deze sessie weigerde dat, en het bestand is ongewijzigd.
- **Niet aangetoond zijn daarom:**
  - dat LiteSpeed of PHP-LSAPI de **nieuwe** mu-plugin al oppikt bij webverzoeken (de bekende valkuil);
  - dat Wordfence-WAF de JSON-POST doorlaat;
  - welk IP er in `REMOTE_ADDR` staat.
- **Het verzoekfilter** (`zwijsen-verzoekfilter.php`) blokkeert dit pad niet. Dat volgt uit de code: het script is `index.php` in de webroot en staat in de lijst; regel 1 en 2 zijn niet van toepassing.
- **De hardening-plug-in** sluit alleen de gebruikerslijst van de REST-API af.
- **Zo test Sven het met het wachtwoord in een browser:**
  1. Open `https://staging.zwijsen.be/elektricien-dendermonde/` en vul de quiz in met een **eigen adres dat niet op de uitzonderingslijst staat**. Anders vertrekt de bevestiging echt.
  2. Verwacht: het bedankscherm, twee regels `tegengehouden` in het mailog en een rij in Inzendingen.
  3. Wis die rij daarna.

  Komt er "Versturen lukte niet", dan gebruikt de webserver de mu-plugin nog niet. Wacht dan even, of laat de webserver herstarten via Combell. Aan Hack Guard of Wordfence moet je daarvoor niets veranderen.

### Opgeruimd

- **Testinzendingen gewist:** **3370, 3371** (`wp eval-file`), **3372–3375** (browsertests; 3372/3373 kwamen van een eerste run waarvan de uitvoer verloren ging, daarna opnieuw gedraaid). Verwijderd met `Query::delete_submission()`, na controle van de user-agent. Nadien 0 rijen in de drie tabellen boven 3369; de hoogste id is weer 3369.
- **Tellers:** alle `zwsnel_*`-transients zijn weg.
- **Hulpbestanden:** `~/tmp/zwl/server/proxy.php` en `test-formulier.php` zijn van de server verwijderd. De pipelinehulpen blijven staan.
- **Lokaal:** de `http.server` op poort 8791 is gestopt, en Chromium is telkens gesloten (geen `chrome-headless-shell`-processen meer). De ssh-mastersessie is gesloten.
- **Niet van mij:** de php-server op 127.0.0.1:8765 is de tracker (`_dev/tracker`) en is blijven draaien.

### Productie, later

1. `elementor/server/zwijsen-snelsollicitatie.php` kopiëren naar `~/subsites/zwijsen.be/wp-content/mu-plugins/`. De mailblokkade staat daar niet, dus vanaf dat moment gaan de mails echt.
2. De pagina's plaatsen met `bouw.py` en `plaats.php`. `data-za-endpoint` gaat automatisch mee.
3. Eén echte inzending doen met een eigen adres, en controleren:
   - de melding komt aan bij personeel@zwijsen.be;
   - de bevestiging komt aan, niet in de spam;
   - Reply-To klopt;
   - het bewaarde IP is het echte IP van de bezoeker.
4. De testinzending daarna wissen.
5. De JSON-LD zegt `directApply: true`. Dat klopt nu, want er kan rechtstreeks gesolliciteerd worden.
6. De Meta-pixel komt later in `finish()` (apply-form.js). Daar vuurt "Lead" nu alleen na een geslaagde inzending. Op staging blijft de meting eruit (bouw.py).

### Bestanden

- `elementor/server/zwijsen-snelsollicitatie.php`: de mu-plugin (kopie van wat op staging staat).
- `assets/apply-form.js`: het endpoint uit een optie of een data-attribuut, `formulier` en `company_website` in de payload, het antwoord nagekeken (`res.ok && json.ok`), een time-out van 20 s, en een foutmelding met de tekst van de server.
- `elementor/bron/bouw.py`: `FORM_ENDPOINT`, `formulier` per pagina, de data-attributen in de formulierwidget en een nieuwe HTML-commentaar.
- `elementor/bron/server/test-formulier.php`: het testscript van de backend. Het staat niet op de server.
- `elementor/elektricien-*.json`: nieuwe exports.
