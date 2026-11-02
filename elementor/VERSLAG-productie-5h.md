# Tracking en cookietoestemming op productie (Combell) — 5h uitgevoerd (2 nov 2026, 10:19–11:16)

## Summary

- **Wat veranderde.** Alleen de Elementor-code **"Pixel Meta" (post 3233)** en het formulierscript `apply-form.js` van de twee regiopagina's. De **oude pixel `2386668898494686`** staat in HTML-commentaar: gedateerd, met uitleg en hoe je hem terugzet. Hij is niet gewist. De **nieuwe pixel `1170263142231501`** laadt pas na toestemming voor "Advertenties". Dat gebeurt via de eigen blokkering van CookieYes, die hier in de oude "legacy"-modus draait. Er is geen `<noscript>`. **Google Consent Mode v2** zet standaard alles op "geweigerd", staat in de HTML vóór GTM en schakelt per categorie om. DNS, wp-config, plug-ins, Hack Guard, Wordfence en post 8 zijn niet aangeraakt.
- **Gemeten, na de wijziging** (`/` en `/elektricien-dendermonde/`, verse browser per toestand):

  | Toestand | Meta | `_fbp` | Google | `_ga` |
  |---|---|---|---|---|
  | (a) eerste bezoek | 0 verzoeken, pixel niet geladen | nee | 1 cookieloze ping `gcs=G100` | nee |
  | (b) alles weigeren | 0 | nee | `G100`-pings, ook op de volgende pagina | nee |
  | (d) alles accepteren | PageView **alleen `1170263142231501`**, direct en op de volgende pagina | ja | `gcs=G111` (GA + doubleclick + google.be/ads) | ja |

  De oude id kwam in geen enkel verzoek voor. (c) "alleen Analytics" is op de eerste meetronde ook gemeten (drie pagina's): `G101`, wel `_ga`, geen Meta.
- **Lead.** Met "alles accepteren" vertrok na de inzending één **Lead** naar `1170263142231501`, met enkel `content_name`, `content_category`, `value=1` en `EUR`. Er zat geen `ud[...]` in en geen naam, e-mail, telefoon of woonplaats. Met "weigeren" vertrok **geen enkel** Meta-verzoek. De mailblokkade stond er van 11:14:06 tot 11:15:42 en is weg (0 van 5 webverzoeken dragen nog de kopregel). De 4 mails zijn tegengehouden en de 2 testinzendingen (3370, 3371) zijn gewist: terug op 382 inzendingen, hoogste id 3367.
- **Beslissing nodig: advanced of basic Consent Mode.** Vóór toestemming en na weigeren stuurt GA4 nog **cookieloze pings** (`gcs=G100`, geen cookies). Dat is "advanced". Zie "Advanced of basic" onder Detail.
- **Nog twee dingen meten vóór toestemming, buiten deze opdracht:**
  1. De **YouTube-video op de homepage** (post 8) zet YouTube-cookies (`YSC`, `VISITOR_INFO1_LIVE`, …) en roept `googleads.g.doubleclick.net` op.
  2. **`analytics.uptodateconnect.com`** (het script van de webbouwer, in WPCode-footer) laadt altijd, maar zet geen cookie.
- **De banner heeft geen weigerknop.** Weigeren kan alleen via "Cookie instellingen" en dan "Opslaan & accepteren" met niets aangevinkt. De knoptekst is misleidend. Dat is een aanpassing aan CookieYes-instellingen, die bewust niet gedaan is.
- **Terugdraaien:** `wp post meta update 3233 _elementor_code --user=breynsv < ~/data/verhuis-backups/prod-voor-5h-snippet-3233-20261102-1029.txt`. Het oude `apply-form.js` staat in dezelfde map, als `prod-voor-5h-apply-form-50adb6feec.js`. Daarna caches legen (zie Detail, het purge-addertje).
- **Niet geverifieerd:** of Meta de events ook echt toont in Events Manager (geen toegang). Ook niet nagegaan: of een inzending verder gaat naar GSheetConnector (die plug-in staat aan).

## Detail

### Wat er stond (nulmeting, 10:25)

Gemeten op productie via `--resolve`. In alle toestanden, ook zonder interactie, vertrokken: de oude pixel (`facebook.com/tr` PageView `2386668898494686`, `_fbp`), GA4 `G-GBEZVH2PYF` met `_ga`, `stats.g.doubleclick.net`, `google.be/ads` en een Meta CAPI-gateway (`mpc-prod-23-…a.run.app`). Die gateway hoort bij de oude pixel: de config van de oude pixel bevat een `openbridge`-endpoint en **automatische advanced matching** (em, fn, ln, ph, …). Die van de nieuwe pixel heeft geen van beide. Ruwe data: `…/tmp/shots-5h/nulmeting.json`, `nulmeting-dend.json`.

### Hoe CookieYes hier blokkeert

- Plugin `cookie-law-info` **3.5.8**, niet 3.5.6. Er bestaat geen optie `cky_cookie_consent_lite_db_version`, dus `cky_is_legacy()` is waar. De site draait de **legacy**-code (`legacy/public/js/cookie-law-info-public.js`). Daar zijn `data-cookieyes="cookieyes-advertisement"` en `cookieyes_consent_update` dus **niet** van toepassing. Er bestaat ook **geen ingebouwde Google Consent Mode** (geen `cky_gcm_settings`).
- **Blokkering in legacy.** Een script krijgt `type="text/plain" data-cli-class="cli-blocker-script" data-cli-script-type="<categorie>" data-cli-block="true" data-cli-element-position="head"`. `cliBlocker.runScripts` zet het om naar uitvoerbaar wanneer `viewed_cookie_policy=yes` en de categorie in `CLI.allowedCategories` zit. Dat gebeurt bij het laden (jQuery ready) én na een keuze in de banner, zonder herladen.
- **Cookies.** `viewed_cookie_policy` (yes/no) en per categorie `cookielawinfo-checkbox-<cat>` (yes/no). Die laatste wordt al bij het eerste bezoek op "no" gezet en bij het aanvinken meteen bijgewerkt, nog vóór "Opslaan". Daarom eist alle eigen code **beide**: `viewed_cookie_policy=yes` én de categorie op yes. Dat is dezelfde regel die de plug-in zelf toepast.
- **Categorieën in de modal:** necessary, functional, performance, analytics, **advertisement**, others. Alle staan standaard uit.
- De opties zijn vóór de wijziging bewaard in `prod-voor-5h-cookie-opties-20261102-1029.json`. Ze zijn **niet** gewijzigd.

### De nieuwe code in post 3233

- Bron: `…/tmp/5h/snippet-3233-nieuw.html`, md5 `3c585951…`. Teruggelezen uit de databank: byte-gelijk.
- **Valkuil.** `wp post meta update` zonder `--user` liet kses de code verminken (`<head>` weg, `<!--` werd `&lt;!--`). Met `--user=breynsv` klopt de code. Dat is meteen rechtgezet.
- **Volgorde in de HTML** (de pagina zelf, niet de DOM):
  1. `cookie-law-info-public.js` (r. 124)
  2. `zw-consent-mode`, de consent-default (r. 180)
  3. de geblokkeerde scripts (r. 207, 210)
  4. de uitgeschakelde oude pixel (r. 240)
  5. GTM van Site Kit (r. 288)

  De default staat dus vóór GTM. Elementor-prioriteit 1 is ongewijzigd. LiteSpeed doet geen JS-optimalisatie (min, comb, defer staan uit), dus de volgorde en de `text/plain`-scripts blijven zoals ze zijn.
- **Consent-default:** `ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage`, `functionality_storage` en `personalization_storage` staan op denied, `security_storage` op granted. `ads_data_redaction` staat aan. Een terugkerende bezoeker met toestemming krijgt meteen, synchroon en nog vóór GTM, een `update` op basis van de cookies.
- **Toestemming midden in het bezoek.** Twee geblokkeerde scripts, één voor `analytics` en één voor `advertisement`, roepen `zwConsentUpdate()` op. Het advertentiescript laadt ook de pixel, met een vlag tegen dubbel laden.
- **Intrekken midden in het bezoek** zet de pixel niet uit tot de volgende pagina. Dat is een beperking van legacy. Het formulier controleert de cookies wel zelf (zie hieronder).
- **Site Kit.** Site Kit's eigen "Consent Mode" (`googlesitekit_consent_mode.enabled=false`) is niet aangezet. Dat zou een tweede default geven die op de WP Consent API wacht, en die API staat er niet.
- **GTM-container `GTM-PK8MTPM`.** De publieke `gtm.js` bevat alleen GA4 `G-GBEZVH2PYF` (`__googtag` + één `__gaawe`-event). Er zit geen Ads-conversietag en geen Meta-tag in. De doubleclick- en google.be/ads-verzoeken komen van GA4 (Google Signals). Het GTM-account van Sven (sven@trouvee.be) heeft geen toegang tot deze container: niet in de accountlijst.

### `apply-form.js` op productie

- Tot nu toe was de productiebuild gelijk aan staging: **zonder meting**, `track()` en `trackStandard()` deden niets. Er vuurde dus nooit een Lead.
- **Wijziging in `elementor/bron/bouw.py`.** Met `ZWL_DOEL=productie` krijgt de build `METING_PRODUCTIE`:
  - alleen `fbq`, en alleen als `fbq` bestaat (de pixel is geladen) **én** `viewed_cookie_policy=yes` **én** `cookielawinfo-checkbox-advertisement=yes`;
  - geen gtag en geen dataLayer, want die zouden naar een GTM-container gaan die niemand beheert.
- **Wat ongewijzigd bleef.** De params komen uit `assets/apply-form.js` en dat bestand is niet aangeraakt: de lokale drafts houden dus hun demogedrag. De stagingbuild is byte-gelijk aan voorheen (`diff -r`). De asserts: staging zonder `fbq`; productie met precies 2 × `global.fbq(`, beide achter `&& adConsent()`.
- **Uitrol.**
  - Het nieuwe bestand (md5 `53cdfc6f…`) staat in `uploads/zwijsen-landing/js/`. Het oude is bewaard.
  - Daarna `herplaats.sh --doel productie`, omdat de `?v=`-versie in de paginadata zit: van `50adb6feec` naar `79637da523`.
  - Vooraf nagegaan: de nieuwe paginabuild verschilt van de vorige **alleen** in die `?v=`.
  - Pagina's 3291 en 3294 zijn opnieuw geplaatst: 200, 0 PHP-meldingen. Hun `_elementor_data` van vóór de wijziging staat in `prod-voor-5h-elementor-data-3291.json` / `-3294.json`. Post 8 is niet aangeraakt.
- **Events met toestemming:**
  - PageView, 4 × SollicitatieStap (`stap`, `vraag`), Lead en SollicitatieVoltooid;
  - SollicitatieGestart vuurde niet: dat gebeurt bij het eerste tonen, vóór de keuze in de banner;
  - in geen enkel Meta-verzoek zat testdata (naam, e-mail, telefoon, postcode) of `ud[`.

### Advanced of basic Consent Mode — beslissing voor Sven

| | Advanced (zoals het nu staat) | Basic (GTM pas laden na toestemming) |
|---|---|---|
| Vóór toestemming of na weigeren | GTM en gtag laden; GA4 stuurt cookieloze pings (`gcs=G100`): paginaweergave, geen cookies, geen client-id | niets naar Google |
| Wat Google ermee doet | modelleert conversies en gebruikers voor wie weigert (GA4 "behavioral modeling", vanaf voldoende volume) | geen modellering; wie weigert, bestaat niet in GA4 |
| Juridisch | Google noemt het conform; de Belgische GBA en een deel van de EU-toezichthouders beschouwen ook een cookieloze ping als verwerking zonder toestemming (IP en user agent gaan naar Google). Grijze zone. | het veiligst |
| Werk | geen | Site Kit's GTM-snippet laten blokkeren. In legacy-CookieYes kan dat niet via de instellingen (de script-blocker kent alleen 4 vaste plug-ins). Dus: Site Kit-snippet uit (`useSnippet=false`) en GTM zelf in post 3233 zetten, als geblokkeerd script (`data-cli-script-type="analytics"`). Een kleine wijziging, maar ze raakt Site Kit's instellingen. |
| Wat het kost | niets extra | minder GA4-data (alleen wie toestemming geeft), geen modellering |

Niet zelf beslist. Advanced is wat de opdracht vroeg en wat nu draait.

### Andere bevindingen

- **Homepage-video (post 8):** YouTube-embed zonder blokkering. Vóór toestemming staan er cookies `YSC`, `VISITOR_INFO1_LIVE`, `VISITOR_PRIVACY_METADATA`, `__Secure-YNID` en `__Secure-ROLLOUT_TOKEN`, en zijn er verzoeken naar `googleads.g.doubleclick.net/pagead/`. Post 8 was in bewerking door een andere agent, dus niet aangeraakt. Mogelijke oplossing: `youtube-nocookie.com` of een klik-om-te-laden-afbeelding.
- **uptodateconnect:** `analytics.uptodateconnect.com/script.js` (WPCode-footer, `ihaf_insert_footer`) laadt in elke toestand. Hij zet geen cookie, maar stuurt wel een verzoek met paginagegevens. Navragen bij de webbouwer, of achter `analytics` zetten.
- **Weigerknop:** de bar toont alleen "Cookie instellingen" en "Alles accepteren". De modal heeft enkel "Opslaan & accepteren". GDPR-praktijk (GBA): weigeren moet even makkelijk zijn als aanvaarden. Op te lossen met `[cookie_reject]` in de bartekst (optie `CookieLawInfo-0.9`). Niet gedaan.
- **Meta bot-filter:** de pixel stuurt niets als de user agent "HeadlessChrome" bevat. Daarom ging de eerste meting na de wijziging met een gewone Chrome-UA. Combell's nginx gaf **429** op een verzonnen UA (`Chrome/141.0.0.0`) na een reeks verzoeken. Met de echte versie (`Chrome/153.0.8010.12`) lukte het meteen.
- **Purge-addertje (belangrijk tot de DNS omgaat).**
  - `wp litespeed-purge` "kickt" de wachtrij via een webverzoek naar `admin-ajax.php` op `zwijsen.be`. Vanaf de Combell-server zelf wijst die naam nog naar Flywheel (151.101.66.159). De purge komt dus pas aan bij het **volgende ongecachte webverzoek op Combell**.
  - Doe na een purge daarom één `curl --resolve zwijsen.be:443:188.208.37.145 "https://zwijsen.be/?nc=$RANDOM"`.
  - Zo gedaan; daarna gaven alle drie de pagina's `x-litespeed-cache: miss` met de nieuwe code.

### Back-ups (server, `~/data/verhuis-backups/`)

- `prod-voor-5h-20261102-1019.sql` (28.325.793 bytes, "Dump completed")
- `prod-voor-5h-snippet-3233-20261102-1029.txt` (md5 `fb9f3337…`), `-post-…json`, `-meta-…json`
- `prod-voor-5h-cookie-opties-20261102-1029.json` (alle `CookieLawInfo`/`cli`/`wt_cli`-opties + `googlesitekit_tagmanager_settings`)
- `prod-voor-5h-apply-form-50adb6feec.js`, `prod-voor-5h-elementor-data-3291.json`, `-3294.json`
- `tijdelijk-5h-tegengehouden-mail.log` (4 regels: 2 × personeel@, 2 × bevestiging aan test-5h@example.com)

### Meetbestanden

`/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-5h/`:
- `nulmeting*.json`: vóór de wijziging
- `na5h.json` + `na5h-tabel.txt`: na de wijziging, `/` en Dendermonde, a/b/d
- `lead5h.json` + `lead-accept-bedankt.jpg` / `lead-reject-bedankt.jpg`
- de screenshots per toestand

Scripts: `…/tmp/pw/consent5h.js`, `lead5h.js`. De eerste meetronde na de wijziging (drie pagina's, a–d, met de headless-UA) bevestigde dezelfde Google- en cookiecijfers, ook voor Lier en (c). De json daarvan is overschreven door de verkorte ronde; de cijfers staan in deze tekst.

### Caches

`wp elementor flush-css`, `wp litespeed-purge all` en `wp cache flush` zijn uitgevoerd, plus het kick-verzoek. Let op: `wp redis flush` bestaat niet, het is `wp cache flush`; productie zit op db 0.
