# Vacaturekaarten voor de regiopagina's (staging, 31 okt 2026)

## Summary

- **Gedaan:** het blok met de twee rode knoppen is weg van `open-vacatures` (1641) en `werken-bij` (1145). Beide pagina's zijn **byte voor byte** gelijk aan de kopie van vóór het blok, ook de pagina-instellingen (md5 gelijk). Tussendoor was er aan die pagina's niets anders gewijzigd.
- **Twee echte vacatures in het overzicht:** de berichten **3366** "Elektricien – regio Dendermonde" (foto 3325, elektricien bekabelt een verdeelbord) en **3367** "Elektricien – Lier en Antwerpen-Zuidrand" (foto 3312, Kurt aan een stopcontact). Ze staan als eerste rij in het raster, met dezelfde kaart als de andere vacatures. De tekst op de kaart is de lijst "Jouw profiel", letterlijk overgenomen van de regiopagina.
- **Waarom zo:** de kaart (lussjabloon 1595) linkt alleen via de knop "Solliciteer Nu", met de dynamische tag *Post URL*. Die tag kent geen eigen URL-veld en geen terugvaloptie. De optie van ECS om de hele kaart klikbaar te maken staat aan, maar volgt dezelfde *Post URL* en geeft geen link in de HTML. Een eigen link zou dus een aanpassing van sjabloon of widget vragen, en die geldt dan voor alle kaarten. Daarom is het route (a) met een 301 geworden.
- **Hoe de link werkt:** de kaart wijst naar `/Vacatures/elektricien-dendermonde/` en `/Vacatures/elektricien-lier-antwerpen-zuidrand/`. Het blok `# BEGIN zwijsen-landing` in `.htaccess` stuurt die met een **301** door naar `https://%{HTTP_HOST}/elektricien-dendermonde/` en `…/elektricien-lier-antwerpen-zuidrand/`. **Over echte HTTP gemeten** (de 301 komt vóór het wachtwoord), en de oude regel voor 1500 (→ `/open-vacatures/`) nu ook.
- **Bestaande kaarten zijn ongewijzigd** op 393 en 1440: zelfde titel, link, foto, tekst en breedte. Ze schuiven alleen een rij op. Er zijn nu 7 kaarten in plaats van 5. De kop en het raster staan op dezelfde x en breedte als voordien.
- **Elders:** de lus "related vacatures" (1462) op elke vacaturepagina toont de twee nieuwe titels onderaan (oplopend op datum), en die links volgen dezelfde 301. De homepagina toont geen vacatures. `werken-bij` heeft geen vacaturelus, dus daar komt niets bij. Die pagina linkt al naar het overzicht met de knop "Vacatures bekijken", maar die wijst naar **productie** (`https://zwijsen.be/open-vacatures/`). Daarnaast staat het menu-item "Werken bij › Open vacatures" in hoofding en voettekst.
- **Op productie herhalen:** `server/zwl-vacaturekaarten.php` draaien. Stap 1 (terugzetten) overslaan als daar geen knoppenblok staat. Vooraf de bijlage-id's (3325/3312 bestaan op productie niet) en de id's van de regiopagina's aanpassen.
- **Terugdraaien:** `wp post delete 3366 3367 --force` en de 4 regels "Vacaturekaarten" uit het `.htaccess`-blok halen (kopie: `staging-htaccess-voor-vacaturekaarten-20261031-2258`). Volledig: `staging-voor-vacaturekaarten-20261031-2258.sql`.
- **Niet geverifieerd:** de doelpagina's in een echte browser met het wachtwoord, en de Elementor-editor.
- **Let op:** tegelijk werkte een andere sessie op staging ("interne links" en "taalfout" op 3292/3350, home, post 85, term 16). Die legde om 23:04 de Elementor-CSS-cache leeg. Mijn pagina's en berichten raakte ze niet; de CSS is opnieuw opgebouwd en de test is daarna opnieuw gedraaid.

## Detail

### Backups (vooraf, `~/data/verhuis-backups/`)
- `staging-voor-vacaturekaarten-20261031-2258.sql` (36 MB, "Dump completed")
- `staging-htaccess-voor-vacaturekaarten-20261031-2258`
- `staging-elementor-data-{1641,1145}-voor-vacaturekaarten-20261031-2258.json`: de toestand mét knoppenblok

### Vergelijking vóór het terugzetten
`_elementor_data` van 1641 en 1145, met het element `zwl-regio-links` eruit, was gelijk aan de kopie `…-voor-links-20261031-2007.json`. De pagina-instellingen waren gelijk aan `…settings…-voor-links`. Er was dus niets anders veranderd, en de kopie is integraal teruggezet. Achteraf: md5 van de data en van de instellingen is gelijk aan de kopie (1641 `61f37a05…`, 1145 `010a5ab6…`).

### Hoe het overzicht gebouwd is
- 1641 bevat een Posts-widget `4a0d4b3`: skin `custom` (Ele Custom Skin), lussjabloon **1595** "werken bij kaarten", type `vacatures`, 12 per pagina, 2 kolommen, standaardvolgorde (nieuwste eerst). De optie "hele post een link" staat aan met `custom_postlink` = *Post URL*, maar zet geen link in de HTML (0 × `data-href`).
- Kaart 1595: afbeelding = *Uitgelichte afbeelding*, kop = *Berichttitel*, tekst = ACF `profiel` (wysiwyg), knop "Solliciteer Nu" = *Post URL*. De afbeelding heeft een vaste hoogte van 251 px met object-fit cover, dus de verhouding van de foto speelt geen rol.
- Sjabloon 1844 "Vacature" (titel via ACF `vacature_titel`) wordt door geen enkele lus gebruikt. 1374 "Vacture single" bevat de lus 1462 (titel + *Post URL*, oplopend).
- Geen kaartsjabloon of widget aangepast.

### Wat het script doet (`server/zwl-vacaturekaarten.php`, ook op de server in `~/tmp/zwl/server/`)
1. Zet 1641 en 1145 terug naar de kopie, controleert dat door terug te lezen, maakt de pagina-CSS opnieuw en leegt de LiteSpeed-cache van die pagina's.
2. Maakt de twee vacatures aan of werkt ze bij. Ze worden herkend aan meta `_zwl_landing_kaart` = 3292 / 3350, dus opnieuw draaien maakt niets dubbel. Telkens: status `publish`, datum 31-10 21:00:01 / 21:00:00 (Dendermonde eerst), uitgelichte afbeelding, ACF `profiel` = de zes punten van "Jouw profiel" als `<ul>` (zoals bij 3108), en Yoast `noindex`, zodat de doorverwijs-URL niet in de sitemap komt.
3. Schrijft de twee RewriteRules in het bestaande `zwijsen-landing`-blok, dat vóór `# BEGIN WordPress` staat, en leest het bestand terug.

`.htaccess` verschilt van de kopie alleen in die 4 regels (2 commentaar, 2 regels).

### Verificatie
De pagina's zijn op de server gerenderd met `zwl-render.php`: status 200, 0 PHP-meldingen. Daarna geladen in headless Chromium met `pw/kaarten.js`, op 393×659 en 1440×900. Schermafdrukken in `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-kaarten/` (`voor-*` / `na-*`, `-full` en `-raster`); het rapport staat in `*-rapport.json`.

| | voor | na |
|---|---|---|
| Kaarten op open-vacatures | 5 | 7 (3366, 3367 bovenaan) |
| Knoppenblok (`.zwl-regio-links`) open-vacatures / werken-bij | 1 / 1 | 0 / 0 |
| 5 bestaande kaarten: titel, link, foto, tekst, breedte | – | identiek |
| Kop en raster: x / breedte (1440) | 150 / 1072 | 150 / 1072 |
| Nieuwe kaarten 1440: positie, breedte, fotohoogte | – | x 150 en 701, 521 breed, 251 hoog, zelfde rij (y 602) |
| Zijwaarts scrollen, ontbrekende bestanden | nee, 0 | nee, 0 |
| Consolefouten open-vacatures / werken-bij | 0 / 4 | 0 / 4 (dezelfde externe `ERR_FAILED`, bestond al) |

Zelf bekeken: op desktop staan twee nieuwe kaarten naast elkaar boven Projectleider/Hoofd Calculator, en op gsm komen ze bovenaan in de kolom. Zelfde schaduw, afronding, kop, opsomming en rode knop als de andere kaarten. De twee foto's verschillen. Op werken-bij volgt na het intro meteen "Wanneer heb jij een vast contract te pakken?".

HTTP (zonder wachtwoord, de 301 komt vóór de 401):
- `/Vacatures/elektricien-dendermonde/` → 301 `https://staging.zwijsen.be/elektricien-dendermonde/`
- `/Vacatures/elektricien-lier-antwerpen-zuidrand` → 301 `https://staging.zwijsen.be/elektricien-lier-antwerpen-zuidrand/`
- `/Vacatures/technieker-algemene-elektriciteit/` → 301 `https://staging.zwijsen.be/open-vacatures/`
- `/Vacatures/calculator/` → 401 (geen regel, zoals bedoeld)

### Kanttekeningen
- Iemand die de 301 negeert en WordPress rechtstreeks aanspreekt, krijgt de gewone vacature-detailpagina (sjabloon 1374) met lege functieomschrijving en lege velden voor het aanbod. Via de webserver gebeurt dat niet.
- Alternatief (niet gebouwd): een kleine mu-plugin met een `post_type_link`-filter voor deze twee berichten. Dan linkt de kaart rechtstreeks naar de regiopagina, zonder 301-sprong. Dat is code bovenop de site, dus niet gekozen.
- "Vacatures bekijken" op werken-bij en "Solliciteer Nu!" in de hoofding wijzen naar productie (`https://zwijsen.be/open-vacatures/`). Dat bestond al en is niet aangepast.
- Productie: de knoppen zijn daar nooit geplaatst (alleen staging), dus stap 1 van het script daar niet draaien.
