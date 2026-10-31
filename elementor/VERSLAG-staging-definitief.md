# Regiopagina's elektricien op staging — definitief (31 okt 2026)

## Summary

- **Backups vooraf:** DB `~/data/verhuis-backups/staging-voor-definitief-20261031-1946.sql` (32,6 MB, "Dump completed") en vlak voor het aanraken van pagina's `staging-voor-paginas-20261031-2001.sql` (32,5 MB); `.htaccess` → `staging-htaccess-voor-redirect-20261031-2006`; `_elementor_data` + instellingen van 1641 en 1145 → `staging-elementor-*-{1641,1145}-voor-links-20261031-2007.json`.
- **Pagina's:** Dendermonde = **3292** (in place, slug nu `elektricien-dendermonde`) https://staging.zwijsen.be/elektricien-dendermonde/ · Lier = **3350** (nieuw) https://staging.zwijsen.be/elektricien-lier-antwerpen-zuidrand/ · 3283 in de prullenbak (404). Oude slug `vacature-technieker-5` geeft **404** (WordPress bewaart geen oude slug voor pagina's), geen doorverwijzing.
- **Native vs HTML:** native = containers, titels, teksten, lijsten, knoppen, pictogramvakken, alle foto's (Afbeelding/Testimonial, hero als containerachtergrond) en de vragen (Elementor-accordeon, antwoord staat in de HTML). HTML-widget alleen voor quiz, film, kaart (inline SVG) en de JSON-LD.
- **Media:** geen nieuwe bijlagen. Alle 13 foto's bestonden al (3307–3330, zelfde uitsnede, pixelvergelijking ≈ 0); WordPress maakt de srcset. Filmfoto's (AVIF/WebP, 48 bestanden) + `js/apply-form.js` (zonder meting) + `js/film.js` staan in `wp-content/uploads/zwijsen-landing/` (3,1 MB). Lazy loading werkt; hero is niet lazy (eerste container, ook niet door Elementor's achtergrond-lazyload).
- **SEO:** Yoast-titel en -beschrijving per pagina gezet; robots ongemoeid (noindex). JobPosting + FAQPage (8 vragen) per pagina, gevalideerd tegen de schema.org-vocabulaire en de Google-velden: 0 fouten, FAQ-JSON-LD = zichtbare tekst. Geen pixel, geen tracker-verzoeken.
- **Oude vacature:** post **1500** "Technieker Algemene Elektriciteit" was `publish`, nu `draft`. 301 naar `/open-vacatures/` in `.htaccess` (blok `# BEGIN/END zwijsen-landing`). **Status en Location niet te meten**: elk verzoek krijgt eerst `HTTP/2 401` van het wachtwoord, en localhost luistert niet. WordPress zelf geeft nu 404 op die URL.
- **Interne links:** blok met 2 knoppen (stijl van de knop "Solliciteer Nu") op 1641 en 1145; de rest van beide pagina's is aantoonbaar ongewijzigd. Het overzicht is een Posts-lus (Ele Custom Skin) over Vacatures: nu 5 kaarten i.p.v. 6, de technieker is weg, geen fouten.
- **Snelheid:** HTTP-meting en Lighthouse kunnen niet door het wachtwoord. Server: 401-antwoord 27–47 ms (vloer); PHP-render via CLI 3,2–6,3 s, tegenover 2,9–3,4 s voor bestaande pagina's (koude CLI, serverbelasting 5–8). Gewicht eerste lading: 112 verzoeken, ±1,0 MB (gsm) / 1,2–1,3 MB (desktop); het grootste deel is site-brede Elementor-CSS/JS. Kost van de pixel zit er niet in.
- **Verschillen met de lokale HTML:** Font Awesome-pictogrammen; 4 foto's hebben de (iets andere) alt-tekst van de bibliotheek; geen preload van de herofoto; de eerste 2 stripfoto's niet lazy. Zichtbare tekst is regel voor regel gelijk op 320/393/1440.
- **Open vragen:** `validThrough` (einddatum vacature); `directApply: true` terwijl het formulier nog niets verstuurt; "Waar vertrek ik 's morgens?" staat niet op de pagina, dus werd het "Hoe kom ik op de werf?"; FAQ-rich results toont Google bijna niet meer (nog wel nuttig voor AI-antwoorden).
- **Niet geverifieerd:** echte browser met het wachtwoord, de 301 over HTTP, Lighthouse, echte iPhone, de pagina's openen in de Elementor-editor, Google Rich Results Test en Search Console (zie Detail).

## Detail

### Wat er op staging gebeurd is, in volgorde

1. `wp db export` → `staging-voor-definitief-20261031-1946.sql`.
2. Media vergeleken: de 13 foto's die als native beeld op de pagina's staan, bestaan al als 3307–3330. Bestandshashes verschillen (opnieuw geëncodeerd op 30 okt), maar verhouding en inhoud zijn gelijk: verschil op 64×64 grijswaarden 0,2–0,5 (de gang 1,7). Dus hergebruikt, niets geïmporteerd.
3. Tweede `wp db export` → `staging-voor-paginas-20261031-2001.sql`.
4. `bron/herplaats.sh`: bouwen, statische map vervangen, `plaats.php` voor 3292 (titel + slug + inhoud) en voor de nieuwe Lier-pagina (3350, meta `_zwl_landing`), Yoast-meta, CSS-bestand van de pagina opnieuw schrijven, LiteSpeed-cache van die pagina legen, renderen.
5. `.htaccess` gekopieerd, blok `zwijsen-landing` toegevoegd vóór `# BEGIN WordPress`, vacature 1500 → `draft`.
6. `_elementor_data` van 1641 en 1145 gekopieerd, knoppenblok geplaatst met `server/zwl-links.php`.
7. `wp post delete 3283` (zonder `--force`).
8. Na de eerste browsertest twee correcties (zie hieronder) en een tekstcorrectie in de JobPosting-beschrijving; opnieuw geplaatst en volledig opnieuw getest.

Niets aangeraakt aan plug-ins, kit, hoofding, voettekst, menu's of opties. Productie (`~/subsites/zwijsen.be`, `~/www`) niet aangeraakt.

### Terugdraaien

- Vacature: `wp post update 1500 --post_status=publish` en het blok `# BEGIN zwijsen-landing … # END zwijsen-landing` uit `.htaccess` halen (of de kopie terugzetten).
- Knoppenblok: de kopie van `_elementor_data` terugzetten, of het element met klasse `zwl-regio-links` in de editor wissen.
- 3283: `wp post undelete 3283`. Volledig: een van de twee SQL-dumps.

### Opbouw van de pagina's

- **Bron = de lokale HTML.** `bron/bouw.py` leest alle teksten, de lijst met foto's, de kaart, de filmshots, de vragen en de JSON-LD met bs4 uit de twee HTML-bestanden. Wie een tekst wijzigt, past de HTML aan en draait `bron/herplaats.sh`.
- **Opmaak:** per pagina in "Aangepaste CSS": `basis.css` + `fotos.css` + `pagina-5.css` + nieuw `pagina-film.css` + `film.css`, formulier-CSS. Alles staat onder `body.elementor-page-<id>` en in `.zwl`-containers, dus de hoofding en de voettekst blijven ongemoeid.
- **Quiz:** `<div id="apply">` + `apply-form.js?v=<hash>` (defer) + `ZwijsenApply.mount('#apply', …)` met de opties uit de lokale pagina (Lier: `regio` + placeholder "bv. 2640 Mortsel"). Een HTML-commentaar in de widget zegt waar de verzending komt (`endpoint`) en dat de bevestigingsmail nog gebouwd moet worden. Meting is eruit gehaald (geen fbq/gtag/dataLayer; bouw.py controleert dat).
- **Actiebalk onderaan:** verdwijnt zolang het formulier in beeld is (IntersectionObserver, zoals lokaal). Alleen tot 860 px.
- **Film:** dezelfde markup en shots als lokaal; `base` = `/wp-content/uploads/zwijsen-landing/`, `lazyStart`. Het punt in de lijst eronder licht mee op (`#punten > .elementor-widget`).
- **Vragen:** nested accordion, alles dicht, `<details>/<summary>` met het antwoord in de HTML.

### Correcties na de eerste test

- **Kaart op gsm:** de knop eronder schoof over de onderkant van de kaart. Oorzaak: de HTML-widget in de flexkolom werd gemeten alsof de SVG 300 px breed was. Opgelost met `width: 100%` op HTML-widgets. Daarnaast zette Elementor de marge boven de kaart op 0 met een sterkere selector; ook dat is opgelost.
- Ruimte onder de herotekst en boven de fotostrook gelijkgezet met lokaal.
- De achtergrond voor gsm (768-versie) is weggelaten: Elementor gebruikt toch het volle bestand, en dat is maar 50 kB.

### Verificatie (definitieve versie)

De pagina's zijn op de server zelf gerenderd met PHP (`server/zwl-render.php`, zonder webserver en dus zonder wachtwoord). Daarna zijn ze geladen in lokale headless Chromium. Elk stagingbestand kwam via ssh, en de lokale HTML stond ernaast via `http.server`. Script: `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/pw/def.js`.

| Controle | 320×568 | 393×659 | 1440×900 |
|---|---|---|---|
| Render 200, 0 PHP-meldingen, hoofding + voettekst | ja / ja | ja / ja | ja / ja |
| Consolefouten / 404 / trackers | 0 / 0 / 0 | 0 / 0 / 0 | 0 / 0 / 0 |
| Zijwaarts scrollen | nee | nee | nee |
| Film rand-tot-rand (links/rechts) | 0 / 0 px | 0 / 0 px | gecentreerd 600 px |
| Film wisselt van shot | ja | ja | ja |
| Quiz tot bedankscherm, placeholder | D: "bv. 9200 of Dendermonde", L: "bv. 2640 Mortsel" | idem | idem |
| Kaart (labels) | D 5, L 16 | idem | idem |
| JSON-LD parseert, FAQ = zichtbaar | ja | ja | ja |
| Zichtbare tekst = lokaal (regel per regel) | gelijk | gelijk | gelijk |
| Foto's geladen / zonder alt | 13/0 | 13/0 | 13/0 |

Beide pagina's (D/L) gaven hetzelfde resultaat. Er is geen pixel in de HTML. Robots blijft "noindex, nofollow". De Yoast-titel en -beschrijving staan in de `<head>`. De CSS-bestanden `post-3292.css` en `post-3350.css` staan op schijf.

- **3283:** `/vacature-technieker-2/` geeft 404. `?page_id=3283` toont de homepagina (gewoon WordPress-gedrag voor een id in de prullenbak).
- **Oude slug:** `/vacature-technieker-5/` geeft 404. WordPress houdt `_wp_old_slug` alleen bij voor niet-hiërarchische berichttypes, niet voor pagina's. Een 301 kan er nog bij in hetzelfde `.htaccess`-blok, als die link ergens gedeeld is.
- **Schermafdrukken** in `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-staging-def/`:
  - `st-*` is staging, `lo-*` lokaal, `cmp-*` beide naast elkaar.
  - Per pagina en breedte: `-film`, `-kaart`, `-faq` en `-bedankt`.
  - `links-*` toont het knoppenblok op open-vacatures en werken-bij.
  - Zelf bekeken, ook naast lokaal: zelfde blokken, zelfde volgorde, zelfde foto's.

### JSON-LD

- **Inhoud.**
  - JobPosting: titel "Elektricien – technieker algemene elektriciteit", een HTML-beschrijving uit de paginatekst (43 stukken, elk letterlijk zichtbaar op de pagina), `datePosted` 2026-10-31, `employmentType` FULL_TIME, `directApply` true.
  - Werkgever: Electro Zwijsen NV, met `sameAs` https://zwijsen.be en het logo van productie: `https://zwijsen.be/wp-content/uploads/2023/09/cropped-cropped-FRONNT-Combinatielogo-Elektro-Zwijsen-Kleur.png`. Die URL is publiek en geeft 200.
  - `jobLocation`: Dendermonde 9200 Oost-Vlaanderen BE, en Lier 2500 Antwerpen BE. `addressRegion` heb ik toegevoegd omdat Google het aanbeveelt.
  - Geen `baseSalary` (keuze van de klant) en geen `validThrough`.
- **Hoe gevalideerd.** `/tmp/zwl-def/scripts/valideer_ld.py` controleert de gerenderde stagingpagina's tegen de officiële schema.org-vocabulaire (`schemaorg-current-https.jsonld`):
  - Elk type bestaat en elke eigenschap hoort bij dat type (of een supertype). Resultaat: 0 fouten.
  - De vereiste Google-velden zijn aanwezig. Aanbevolen maar ontbrekend: `validThrough`, `baseSalary`, `identifier`, en `streetAddress` (bewust: het is een regio, geen adres). Google toont die als waarschuwing, niet als fout.
  - De datum- en enumwaarden kloppen.
  - De vragen en antwoorden in de FAQPage zijn exact gelijk aan de zichtbare tekst.
- **Yoast** zet zijn eigen graph (WebPage, Organization, …) ernaast. Er zit dus een tweede Organization in de pagina; dat is normaal en geen fout.
- **Google Rich Results Test (voor Sven, achter het wachtwoord):**
  1. Open de pagina in een browser.
  2. Bekijk de paginabron (Ctrl/Cmd+U), selecteer alles en kopieer.
  3. Ga naar https://search.google.com/test/rich-results, kies het tabblad "Code", plak de bron en klik op "Code testen".
  4. Verwacht: "Vacature" geldig, met waarschuwingen voor de ontbrekende aanbevolen velden.
  - Zelfde werkwijze kan op https://validator.schema.org ("Code-fragment").
  - Search Console pas als de pagina live staat.

### Veelgestelde vragen (lokaal eerst, commit d2f5edf)

De acht vragen staan in beide HTML-bestanden, voor het bel-blok. Ze zijn gemaakt met `bron/faq_jsonld.py`, dat ook controleert dat elk antwoord op de pagina staat:
- cv
- duur
- ervaring
- VCA
- talen
- contract
- "Hoe kom ik op de werf?" (met de regiozin van de pagina)
- werven

### Snelheid

- **Waarom geen HTTP-meting en geen Lighthouse:** het staging-wachtwoord zit ervoor. Een verzoek naar localhost op de server krijgt geen antwoord (gedeelde hosting) en de publieke naam geeft 401.
- **TTFB van het 401-antwoord** (zelfde server): 27–47 ms. Dat is de ondergrens van server en netwerk.
- **PHP-render (CLI, koud, belasting 5–8):** Dendermonde 3,2–4,7 s, Lier 3,4–6,3 s. Bestaande pagina's onder dezelfde omstandigheden:
  - `/`: 3,1–3,4 s
  - `/werken-bij/`: 3,0–3,2 s
  - `/open-vacatures/`: 2,9–3,1 s

  Dit zijn geen echte TTFB's. LSAPI houdt de opcache warm en LiteSpeed Cache bedient anonieme bezoekers uit de cache. Het zegt wel dat de nieuwe pagina's niet merkbaar zwaarder renderen dan de rest.
- **Gewicht** (`weight.js`, gzip geschat voor tekst):

  | | Eerste lading (cookiebalk zichtbaar) | Na volledig doorscrollen |
  |---|---|---|
  | gsm 393 @3x | 112 verzoeken, ±1,0 MB (tekst 505 kB, beelden 517 kB) | 118 verzoeken, ±1,6 MB |
  | desktop 1440 | 114–115 verzoeken, ±1,2–1,3 MB | 119 verzoeken, ±1,6 MB |

  - De tekst is grotendeels site-breed: Elementor, Pro en de plug-ins.
  - De lettertypes komen van **zwijsen.be** (productie), omdat Elementors lokale Google-fonts-CSS op staging naar productie wijst. Dat is al zo voor de hele staging en niet door dit werk.
  - De grootste beelden: de luchtfoto (1536 px, 237 kB, lazy) en de eerste filmshot (116 kB, pas bij nadering).
- **De Meta-pixel zit er niet in.** Op productie komt die er nog bij.

### Bekend en niet aangepast

- De eerste twee foto's van de fotostrook krijgen geen `loading="lazy"`. WordPress laat de eerste beelden van de inhoud altijd zonder lazy loading, en via de widget is dat niet te sturen. Kostprijs: ongeveer 130 kB bij de eerste lading.
- De portretten bij de getuigenissen gebruiken "medium" (300 px), maar de srcset laat de browser op gsm het volle bestand kiezen (Steven 107 kB). Ze laden lazy.
- **Alt-tekst uit de bibliotheek** (Elementor neemt de alt-tekst van de bijlage over), iets anders dan lokaal:

  | Foto | Alt-tekst in de bibliotheek |
  |---|---|
  | werf-gang-paarse-verlichting | "Afgewerkte gang met paarse en blauwe ledverlichting" |
  | plannen-bekijken | "Twee elektriciens bekijken samen een elektrisch plan aan de muur" |
  | selfie-in-de-werkplaats | zonder "lachende" |
  | magazijn-materiaal-klaarzetten | één alt-tekst, op beide plaatsen |

  Aanpassen kan in de mediabibliotheek. Bestaande bijlagen heb ik niet gewijzigd.
- De cookiebalk ligt op gsm over de actiebalk tot je hem wegklikt, zoals op de rest van de site.
- Het knoppenblok op `open-vacatures` staat op desktop 10 px meer naar rechts dan de titel erboven. Dat is de standaardpadding van een binnenkolom; ik heb het zo gelaten.

### Bestanden

- **Repo:**
  - `bron/bouw.py` (herschreven voor de twee regiopagina's)
  - `bron/pagina-film.css`
  - `bron/plaats.php` (werkt nu ook bij op id, inclusief de slug)
  - `bron/herplaats.sh`
  - `bron/faq_jsonld.py`
  - `bron/server/zwl-render.php`, `exporteer.php`, `zwl-links.php`
  - exports `elektricien-dendermonde.json` en `elektricien-lier-antwerpen-zuidrand.json` (Elementor-sjabloon 0.4)
  - De oude exports van voorstel 2 en 5 zijn weg; ze staan nog in de git-geschiedenis.
- **Server:**
  - `~/tmp/zwl/` bevat alleen nog de pipelinehulpen (`plaats.php`, `server/*.php`, `build/*.json`). Gerenderde HTML, logs en tijdelijke kopieën zijn opgeruimd.
  - De hulpen uit de vorige ronde waren er niet meer en zijn opnieuw gemaakt, nu ook in de repo.
- **Productie later:**
  - Uploadmap `zwijsen-landing/` meekopiëren.
  - De mediabibliotheek-id's 3307–3330 bestaan op productie niet. Importeren en `media.json` aanpassen; `bouw.py` + `plaats.php` doen de rest.
  - Het `.htaccess`-blok en het concept zetten van 1500 herhalen.
