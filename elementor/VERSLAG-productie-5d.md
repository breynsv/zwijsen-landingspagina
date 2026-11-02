# Vacaturepagina's op productie (Combell) — 5d uitgevoerd (2 nov 2026, 09:57–10:16)

## Summary

- **Productie-id's:** Dendermonde = pagina **3291**, Lier = pagina **3294**. Vacaturekaarten **3298** (Dendermonde, foto 3289) en **3299** (Lier, foto 3280). Oude vacature **1500** → concept. Media **3278–3290** (13 foto's). Alles nog alleen via `--resolve`; DNS niet aangeraakt.
- **301's werken**, over echte HTTPS gemeten, met exact de juiste Location: `/Vacatures/technieker-algemene-elektriciteit/` → `https://zwijsen.be/open-vacatures/`, en de twee kaart-URL's → de twee regiopagina's.
- **Formulier:** twee echte inzendingen over HTTPS (één per pagina, `test-5d@example.com`). Beide kwamen in `e_submissions` (3368, 3369) en op de pagina "Sollicitaties". Het bewaarde IP is het echte IP van de bezoeker (78.20.48.68, hetzelfde als `SSH_CLIENT`), dus geen proxy. Alle 4 mails zijn tegengehouden, er is niets verstuurd. Daarna zijn beide inzendingen en de snelheidsteller gewist: terug op 382 inzendingen, hoogste id 3367.
- **Tijdelijke mailblokkade** `zz-tijdelijk-geen-mail-5d.php`: het bestand stond er van **10:12:18 tot 10:15:11 CET**. Bij webverzoeken was ze actief vanaf 10:12:50 en weg vanaf 10:15:24, telkens 5 op 5 gemeten. Ze is **verwijderd en dat is bevestigd**. Het log is bewaard in de backupmap.
- **Post 85, term 16 en homepage zijn klaar.** Post 85 en term 16 zijn byte-gelijk aan staging (md5 `b20c2632…` en `6cc78460…`); term 16 is rechtstreeks in de DB geschreven, beide `<h2>`'s staan er nog. De homepage was **al rechtgezet** in de verhuissessie (5g): `_elementor_data` is md5-gelijk aan staging (`3a826a0a…`), met 2× "word" en 0× "wordt". Daar heb ik niets aan veranderd.
- **Pagina's:** 200 met hoofding en voettekst, getest op 393 en 1440. De film loopt (rand tot rand op gsm), de quiz komt tot het bedankscherm, de kaart toont 5/16 labels, de 8 vragen zijn gelijk aan de FAQ-JSON-LD, de 13 foto's laden, geen 404, geen zijwaarts scrollen. Consolefouten: alleen de trackers die ik zelf afbrak. **Geen nieuwe pixel**: de enige is de bestaande site-brede `2386668898494686` + GTM, dezelfde als op `/` en `/werken-bij/`. `/open-vacatures/` toont 7 kaarten, de 2 nieuwe bovenaan.
- **Afwijkend van staging, bewust: de pagina's staan op `index, follow`** omdat `blog_public` = 1 op productie. Ze komen dus in `page-sitemap.xml` en worden geïndexeerd zodra de DNS omgaat. Dat is de bedoeling voor JobPosting, maar het is een beslissing.
- **Niet geverifieerd:**
  - De beheerpagina via een echte HTTP-login. Het aanmaken van een admin-sessiecookie werd door de veiligheidsregels geweigerd. De pagina is als gebruiker 20 server-side gerenderd, en anoniem geeft ze 302 naar de login.
  - De echte mailaflevering: FluentSMTP is inactief tot 5a, dus een mail gaat nu via PHP `mail()`.
  - Elementor-editor, Rich Results Test, een echte iPhone, en `.php` in uploads → 403 (ik heb geen testbestand aangemaakt).
- **Correcties voor het draaiboek:** zie "Correcties voor OMSCHAKELING.md" onderaan. Het belangrijkste: `media-prod.json` en `herplaats.sh --doel productie`; `wp redis flush` bestaat niet; de homepage was al gedaan; de rol wordt al bij elke WP-lading aangemaakt; de sitemapgroottes in hoofdstuk 7 kloppen niet meer.

## Detail

### Reservekopieën (`~/data/verhuis-backups/`)
- `prod-voor-vacaturepaginas-20261102-0957.sql`: 27,9 MB, 74 tabellen, "Dump completed".
- Map `prod-voor-vacaturepaginas-20261102-0957/` bevat:
  - `htaccess` (md5 `cc129d5f…`);
  - `post-85-post_content.html` en `term-16-description.html` (de originelen, md5 `bb62380b…` en `a3fdb881…`);
  - `_elementor_data` van 8, 1641 en 1145 (1641/1145 = `61f37a05…` / `010a5ab6…`, gelijk aan de staging-kopie van vóór het knoppenblok, dus er was niets terug te zetten);
  - `post-1500.json`, een lijst van de mu-plugins, en `tijdelijk-5d-tegengehouden-mail.log`.
- Vooraf gecontroleerd: post 85 en term 16 op productie waren byte-gelijk aan de originelen die op staging bewaard zijn (`staging-interne-links-origineel-20261031-2302/`).

### Wat er gebeurd is, in volgorde
1. **Pipeline nagekeken.** Een herbouw voor staging is element voor element, CSS en Yoast gelijk aan wat nu op staging 3292/3350 staat.
2. **Media.** De 13 foto's die de pagina's gebruiken (met 3312 en 3325 voor de kaarten):
   - geïmporteerd met `media-import.php` (`media_handle_sideload`), als **dezelfde bytes die staging serveert**;
   - titel, alt, slug, bijschrift en beschrijving zoals op staging;
   - meta `_zwl_staging_bron` = staging-id, zodat opnieuw draaien niets dubbel maakt;
   - **met `--skip-plugins=imagify`**. Anders stuurt Imagify elke foto naar zijn API en doet het een loopback naar `zwijsen.be`, en die naam wijst nog naar Flywheel. Gevolg: de subformaten zijn door WordPress gemaakt, niet door Imagify geoptimaliseerd.
   - Koppeling in `bron/media-prod.json` (sleutel → id/url/alt + `staging_id`); `media.json` (staging) is ongewijzigd.
3. **Statische map.** Op de server gekopieerd met `cp -a` van staging naar productie. Resultaat: 50 bestanden (48 filmfoto's + 2 JS), 3.191.755 bytes, md5-lijst gelijk aan staging én aan de lokale build.
4. **Formulier-backend.** `zwijsen-snelsollicitatie.php` 1.1.0, md5 `8b79a094…`, gelijk in repo, staging en productie. De rol `zwijsen_rapport` is aangemaakt door de eigen `init`-haak van de plug-in, met precies `read` + `zwijsen_bekijk_sollicitaties`; beheerders hebben die capability ook. De REST-route staat. **Jana's account is niet aangemaakt.**
5. **Pagina's.** `herplaats.sh --doel productie`: plaats.php op slug, Yoast-titel en -beschrijving, CSS van de pagina, LiteSpeed-purge en server-render (200, 0 PHP-meldingen). Teruggelezen: `_elementor_data`, `custom_css`, `hide_title` en Yoast zijn gelijk aan de build. De productiebuild is de stagingbuild met enkel de media-id's en -url's vervangen (in de code nagegaan), zonder verwijzing naar staging.
6. **Vacature 1500** → `draft`.
7. **`.htaccess`.** Het blok van staging is vóór `# BEGIN WordPress` geplaatst, met alle drie de regels en de commentaardatum aangepast. De diff ten opzichte van de backup bevat alleen die regels.
8. **Kaarten.** `zwl-vacaturekaarten.php - media-prod.json`. Daarna was de md5 van `.htaccess` ongewijzigd, wat bewijst dat de kaart-URL's op productie dezelfde zijn als op staging. Titel, datum, `profiel` (md5 `00bfa799…` / `af01409a…`), noindex en meta-sleutels zijn gelijk aan staging 3366/3367.
9. **Post 85 en term 16** met `links-plaats.php`. Het script controleert vooraf het origineel. Post 85 gaat via `wp_update_post` (er is een revisie bijgekomen), term 16 via `$wpdb->update`, met `clean_term_cache`. Teruggelezen md5 = staging.
10. **Caches geleegd:** `wp elementor flush-css`, `wp litespeed-purge all` en `wp cache flush` (Redis). Productie gebruikt db 0 en staging db 1, dus het flushen raakt staging niet. Daarna 8 sleutelpagina's opgewarmd, allemaal 200. Na de test nog eens purge + flush.

### Verificatie (echte HTTPS naar 188.208.37.145)
- Script `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/pw/prod5d.js`. Het rapport staat in `…/tmp/shots-prod/rapport-visueel.json`, samen met de schermafdrukken: `-top`, `-film`, `-kaart`, `-faq`, `-bedankt`, `-full`, en `e2e-*-bedankt`.
- In die visuele run kreeg de formulier-POST een nep-`{ok:true}`, zodat er niets verstuurd werd. Trackers zijn afgebroken (geen testbezoeken in Meta/GA) maar wel gelogd.
- Derde partij: alleen `analytics.uptodateconnect.com`, en dat is site-breed.
- Rich Results-velden: JobPosting en FAQPage zijn aanwezig; de Yoast-graph staat ernaast.
- Interne links in de geserveerde HTML:
  - post 85: "Dendermonde" en "Lier en de Antwerpse Zuidrand";
  - het archief `/category/diensten/algemene-elektriciteitswerken/`: dezelfde twee links;
  - op de regiopagina's: de regio-link naar de andere pagina.
- E2E-script `/tmp/zwl-5d/e2e.js`. Het antwoord van de POST droeg zelf de kopregel van de blokkade. Zo ziet het log eruit:
  - aan personeel@: "Nieuwe sollicitatie elektricien – {regio} – TEST 5d – negeren", met Reply-To de sollicitant;
  - aan de sollicitant: "We hebben je sollicitatie goed ontvangen", met Reply-To personeel@.
  - Van: `Electro Zwijsen <website@zwijsen.be>`.
- Let op: Elementor's actielog noteert die tegengehouden mails als "success", omdat `pre_wp_mail` `true` teruggeeft.
- Hoofdstuk 7, voor zover relevant:
  - `/zz-kanarie.php` geeft 403;
  - de JS en de nieuwe media geven 200;
  - `page-sitemap.xml` bevat de 2 regiopagina's;
  - `vacatures-sitemap.xml` heeft 6 URL's, zonder 1500 en zonder de kaarten (noindex);
  - de enige inactieve plug-in is `fluent-smtp`, en dat hoort zo tot 5a.
- Browsers: alleen eigen headless-shell 1243, telkens afgesloten (pgrep leeg). Er draaiden headless-shell **1234**-processen van een andere sessie; die heb ik niet aangeraakt.

### Op de server achtergelaten (buiten de webroot, niets gewist)
- In `~/tmp/zwl/`:
  - `build-productie/`, `media-5d/` (de 13 bronfoto's + `staging-media.jsonl`) en `links-5d/` (de staging-waarden);
  - `media-import.php`, `links-*.php`, `peil.php` (alleen lezen), `htaccess-blok.txt` en `media-prod.json`.
- Mag je opruimen. Ik heb het laten staan volgens de regel "niets wissen".

### Terugdraaien
- Pagina's 3291 en 3294 en kaarten 3298 en 3299 naar de prullenbak.
- Het `.htaccess`-blok weghalen, of de kopie `htaccess` terugzetten.
- `wp post update 1500 --post_status=publish`.
- De mu-plugin `zwijsen-snelsollicitatie.php` weghalen.
- Post 85 en term 16 uit de backupmap terugzetten (term 16 via de DB).
- Media 3278–3290 verwijderen.
- Volledig: de SQL-dump.

### Correcties voor OMSCHAKELING.md (voor jou om te verwerken)
1. **5d stap 2:** de nieuwe id's horen in `elementor/bron/media-prod.json`, niet in `media.json` (dat blijft staging). Importeer met `--skip-plugins=imagify` zolang de DNS nog naar Flywheel wijst.
2. **5d stap 3:** geen rsync nodig: `cp -a` op de server zelf, en vergelijk daarna aantal, grootte en md5-lijst.
3. **5d stap 4:** `herplaats.sh` hoeft niet meer aangepast te worden: `elementor/bron/herplaats.sh --doel productie`. In die modus vervangt het de statische map niet, maar controleert het dat ze gelijk is aan de build.
4. **5d stap 7 en 8:** het `.htaccess`-blok moet er **vóór** `zwl-vacaturekaarten.php` staan; het script vult het blok aan. Op productie: `wp eval-file zwl-vacaturekaarten.php - media-prod.json --user=breynsv`. Het script zoekt de regiopagina's op slug.
5. **5d stap 8, laatste punt:** de links tussen de landingspagina's gaan mee met de pipeline (stap 4), niet met "stap 4" van het draaiboek (de databank).
6. **5d stap 10 (homepage):** was al gedaan in 5g tijdens de verhuis. Op productie hoeft niets meer.
7. **5d stap 11:** de rol wordt aangemaakt bij elke WordPress-lading, ook WP-CLI. De beheerpagina openen is niet nodig.
8. **Redis:** `wp redis flush` bestaat niet. Gebruik `wp cache flush`; dat is veilig, want productie zit op db 0 en staging op db 1.
9. **Hoofdstuk 7:**
   - De sitemapgroottes (5009 / 1683 / 2439 / 13499) kloppen niet meer: twee pagina's erbij en een vacature eruit. Meet opnieuw.
   - "Geen enkele plug-in inactief" faalt tot 5a (fluent-smtp).
   - De **echte** testsollicitatie met mail aan personeel@ blijft nodig ná 5a. Vandaag zou een mail via PHP `mail()` van Combell vertrekken (spamrisico, zie 26 okt).
10. **5d, tabel "Wat er op staging staat":** voeg de kaarten en de pagina-id's van productie toe (3291 / 3294 / 3298 / 3299 / media 3278–3290).
11. **Indexering:** staging staat op noindex, productie op index. De pagina's komen in de sitemap zodra de DNS omgaat. Vermeld dat, of beslis anders.

### Gewijzigd in de repo
- `bron/bouw.py`: `ZWL_DOEL=productie` → `build-productie/`, `media-prod.json`, geen vaste id. De stagingbuild is byte-gelijk aan voorheen.
- `bron/herplaats.sh`: `--doel staging|productie` (staging blijft de standaard).
- `bron/server/zwl-render.php`: host via `ZWL_HOST`, standaard staging.
- `server/zwl-vacaturekaarten.php`: regiopagina's op slug, media uit een koppeling, en `-` slaat het terugzetten over.
- Nieuw: `bron/media-prod.json`, `bron/server/media-import.php`, `bron/server/links-plaats.php`, `bron/server/links-exporteer.php`; `.gitignore` uitgebreid met `bron/build-productie/`.
