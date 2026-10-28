# Landingspagina's op staging — versie 2 (28 okt 2026)

## Summary

- **Reservekopie vooraf:** `~/data/verhuis-backups/staging-voor-landing-v2-20261028-1048.sql` (30,9 MB, "Dump completed").
- **In de prullenbak (niet definitief gewist):** 3278 (voorstel 1), 3286 (voorstel 3), 3289 (voorstel 4). Ze geven nu 404. Terugzetten kan met `wp post undelete <id>`.
- **Bijgewerkt, zelfde id en link:** voorstel 2 = post 3283, https://staging.zwijsen.be/vacature-technieker-2/ en voorstel 5 = post 3292, https://staging.zwijsen.be/vacature-technieker-5/. De inhoud is nu Jana's copy en foto's uit de lokale drafts.
- **24 nieuwe foto's** in de mediabibliotheek (id 3307–3330), met Nederlandse alt-tekst. Bestaande media zijn niet aangeraakt.
- **Native Elementor:** titels, teksten, lijsten, knoppen, alle foto's (Afbeelding-widget), de getuigenissen (Testimonial-widget) en de pictogramvakken in 5. **Een HTML-widget is er alleen voor** het formulier met zijn script (in 2 ook de kleefbalk bovenaan).
- **Formulier = de draft:** 4 vragen, geen vinkvakje, onder de knop de privacyzin met een link naar /privacy-policy/, en op het bedankscherm "We bellen je zo snel mogelijk". Er wordt niets verzonden (demomodus) en er is geen meting: geen pixel, geen gtag en geen dataLayer.
- **Verschillen met de drafts:**
  - De gele knop "Solliciteer Nu!" in de sitehoofding is de echte themaknop. In 2 opent hij niet de pop-up.
  - De pictogrammen in 5 zijn Font Awesome in plaats van de lijntekeningen.
  - De kleefbalk in 2 legt zich onder de kleverige Elementor-hoofding. Daarvoor is één regel in de JS aangepast.
- **Geverifieerd:**
  - Beide pagina's geven 200, met hoofding en voettekst, zonder PHP-meldingen, en hun CSS-bestand staat op schijf.
  - In headless Chromium op 1440 en 390 px gaat het formulier door alle 4 de vragen tot het bedankscherm. Er zijn geen JS-fouten, geen kapotte of alt-loze foto's en geen zijwaarts scrollen.
- **Niet geverifieerd:**
  - Geen echte browser met het staging-wachtwoord. Het is getest via een rendering op de server zelf.
  - Niet op een echte iPhone.
  - Niet in de Elementor-editor zelf geopend.

## Detail

### Wat er gebeurd is, in volgorde
1. `wp db export` naar de reservekopie hierboven.
2. Gecontroleerd dat 3278/3283/3286/3289/3292 echt `vacature-technieker-1…5` zijn en de meta `_zwl_landing` hebben.
3. 24 JPEG's uit `assets/img/jana/` (de 900-px-varianten niet) geïmporteerd met `wp media import --alt=…`, als gebruiker breynsv. Id, url en alt-tekst staan in `bron/media.json`. Ik heb bewust niet de 8 originelen hergebruikt die al in de bibliotheek staan. Elementor neemt de alt-tekst van de bijlage, en die bestaande bijlagen mocht ik niet wijzigen. Bovendien zijn Jana's versies exact de uitsneden uit haar document.
4. `bron/bouw.py` herschreven voor alleen 2 en 5 en `bron/plaats.php` (ongewijzigd) gedraaid. Die werkt de pagina in place bij via `Document::save` en leest daarna terug: 110 en 88 elementen.
5. 3278, 3286 en 3289 met `wp post delete` (zonder `--force`) naar de prullenbak.
6. `wp elementor flush-css`, daarna beide pagina's op de server gerenderd. Daarbij zijn `post-3283.css` en `post-3292.css` opnieuw aangemaakt.
7. Na de eerste browsertest twee CSS-correcties (zie hieronder), opnieuw geplaatst, geflusht en gerenderd.

### Opbouw
- Per pagina staat de opmaak in "Aangepaste CSS": `basis.css` + het nieuwe `fotos.css` + `pagina-N.css` + het formulier (`formulier-extra.css` + `assets/apply-form.css`). Alles staat onder `body.elementor-page-<id>` en de inhoud zit in `.zwl`-containers, dus de hoofding en de voettekst worden niet geraakt. De kleefbalk en de pop-up hangen aan `body` en vallen nog onder hetzelfde voorvoegsel.
- **Voorstel 2:** elke knop heeft de link `#check` en opent de pop-up. De kleefbalk bovenaan (`ZwijsenApply.stickyBar`) verschijnt na de hero, op elk scherm. Er is geen onderbalk, zoals in de draft.
- **Voorstel 5:** het formulier staat inline bovenaan (`#solliciteer`, met `data-za-scroll` via Elementor-attributen). Onderaan staat de rode onderbalk "Solliciteer in 30 seconden" (≤ 860 px).
- De fotoband is metselwerk op desktop. In 5, en op mobiel in 2, is het een veegstrook. De werfgalerij is een raster. "Regio Dendermonde in beeld" staat naast "Ben jij dit?" of erboven in een smalle kolom, zoals in de draft.
- **Opgeruimd in de repo:** de bronnen en exports van 1/3/4 (`pagina-1/3/4.css`, `vacature-technieker-1/3/4.json`) en de functies `pagina_1/3/4` in `bouw.py`. De git-geschiedenis houdt ze bij.

### Correcties na de eerste test
- **De veegstrook was zo breed als al zijn foto's samen (2400–3700 px).** Je zag het niet, omdat het thema `overflow-x: clip` op body zet, maar daardoor kon je ook niet vegen. Nu is de strook kolombreed en scrollt hij zelf. De pagina zelf is 390 px breed.
- Wat ruimte tussen de ondertitel van de hero en de knop "Doe de test" (1,5rem, zoals in de draft).

### Verificatie

| | 2 @1440 | 2 @390 | 5 @1440 | 5 @390 |
|---|---|---|---|---|
| Render 200, hoofding + voettekst, 0 PHP-meldingen | ja | ja | ja | ja |
| Formulier 4 vragen → contact → bedankscherm | ja (pop-up) | ja (pop-up) | ja | ja |
| "zo snel mogelijk" op bedankscherm | ja | ja | ja | ja |
| Vinkvakje in formulier | geen | geen | geen | geen |
| Privacyzin + link /privacy-policy/ | ja | ja | ja | ja |
| Foto's geladen / zonder alt | 24/0 | 24/0 | 16/0 | 16/0 |
| JS-fouten / zijwaarts scrollen | 0/nee | 0/nee | 0/nee | 0/nee |
| Kleefbalk na scrollen | aan, onder hoofding | aan, onder hoofding | n.v.t. | n.v.t. |

- 1, 3 en 4 renderen als de 404-pagina van WordPress; in `wp post list --post_status=trash` staan ze met slug `…__trashed`.
- De gerenderde HTML bevat geen `fbq`/`fbevents`. De zes vinkvakjes in de bron zijn die van de cookieplug-in, niet van het formulier.
- **Methode:** de pagina is gerenderd met PHP op de server, dus zonder Apache en zonder wachtwoord. Daarna is ze geladen in lokale headless Chromium, waarbij elk stagingbestand via ssh kwam. Het formulier is daarna met Playwright doorlopen.
- **Screenshots:** `/tmp/zwl2/shots/` (st2-*, st5-*). De drafts staan als draft-2-*/draft-5-* ernaast. De vergelijking draft ↔ staging op volledige paginahoogte toont dezelfde blokken, in dezelfde volgorde en met dezelfde foto's.

### Bekend en niet aangepast
- **In de pop-up van 2 overlapt het sluitkruisje op 390 px het label "Stap 5 van 5".** Dat staat zo ook in de draft (zie het copyverslag).
- **De cookiebalk ligt op mobiel over de onderbalk** tot je hem wegklikt. Dat is hetzelfde als vorige keer en op de live site.
- **Op staging blijven hulpbestanden achter in `~/tmp`, buiten de site:** `zwl-render.php`, `zwl-import.sh`, `plaats.php`, `exporteer.php`, `herplaats.sh`, de JSON's, de map `zwl-jana/` en twee gerenderde HTML's. Ze staan buiten de webroot en niets ervan staat in de site.

### Exports
`vacature-technieker-2.json` en `-5.json` zijn opnieuw uit de database gehaald (Elementor-sjabloon, formaat 0.4, type page, met pagina-instellingen en CSS). Ze verwijzen naar de media-id's 3307–3330 met URL's op staging.zwijsen.be. **Op productie bestaan die id's niet**, want ze zijn alleen op staging aangemaakt. Bij de overstap naar productie moeten de foto's daar dus ook geïmporteerd worden en moet `media.json` aangepast worden; `bouw.py` + `plaats.php` doen de rest.
