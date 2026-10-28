# Landingspagina's vacature technieker — op staging.zwijsen.be

## Summary

- **Vijf pagina's staan online op staging** (gepubliceerd, achter het staging-wachtwoord, standaardsjabloon met de echte sitehoofding en voettekst):
  1 Vacature+ — post 3278 — https://staging.zwijsen.be/vacature-technieker-1/
  2 Kwalificatietest — post 3283 — https://staging.zwijsen.be/vacature-technieker-2/
  3 Een dag op de werf — post 3286 — https://staging.zwijsen.be/vacature-technieker-3/
  4 Wat je krijgt — post 3289 — https://staging.zwijsen.be/vacature-technieker-4/
  5 Snelsollicitatie mobiel — post 3292 — https://staging.zwijsen.be/vacature-technieker-5/
- **Reservekopie vooraf:** `~/data/verhuis-backups/staging-voor-landing-20261027-1441.sql` (28,8 MB, dump volledig afgesloten).
- **Native Elementor (bewerkbaar in de editor):** alle containers, titels, teksten, lijsten, knoppen, foto's (ook de achtergrondfoto's van de hero's), de pictogramvakken en de veelgestelde vragen (Elementor-accordeon). Kleuren van titels en knoppen gebruiken de globale kitkleuren. **HTML-widget alleen voor:** het sollicitatieformulier (de JS uit de draft, ongewijzigd behalve de meting), de tijdlijn in #3 en de voorwaardentabel in #4.
- **Verschillen met de draft:** de pictogrammen in #1 en #5 zijn Font Awesome-iconen in plaats van de lijntekeningen; de kleurverlopen over de hero-foto's hebben twee in plaats van drie stappen (nauwelijks zichtbaar); de gele knop in de sitehoofding opent in #2 niet het venster (dat is de echte themaknop, die gaat naar zijn eigen pagina); de foto's zijn de bestaande bibliotheekfoto's (id 1230 en 1240), niet opnieuw geïmporteerd — het zijn dezelfde bestanden.
- **Meting staat uit:** geen Meta-pixel, en in de formuliercode zijn de meetfuncties leeggemaakt (ook geen dataLayer/gtag), want de site laadt Site Kit.
- **Geverifieerd:** alle vijf renderen zonder PHP-meldingen, met hoofding en voettekst, elk CSS-bestand bestaat op schijf; het formulier is op elke pagina tot het bedanktscherm doorlopen, op 1440 px en 390 px; schermafdrukken naast de draft vergeleken.
- **Niet geverifieerd:** het staging-wachtwoord had ik niet; alles is getest via een rendering op de server zelf, met de echte CSS/JS van staging. Een kijkje in een gewone browser met het wachtwoord blijft nodig.
- **Let op mobiel:** de cookiebalk ligt onderaan over de knoppen en over de rode actiebalk tot je hem wegklikt — op de live site is dat hetzelfde, maar het valt hier meer op.

## Detail

### Hoe het gebouwd is

- `bron/bouw.py` maakt de Elementor-data (containers + widgets) uit de vijf drafts; `bron/plaats.php` zet ze op de site via Elementor's eigen opslag (`Document::save`), leest ze terug en controleert het aantal elementen en de pagina-instellingen. Het script maakt alleen pagina's aan, of werkt pagina's bij die het zelf gemaakt heeft (meta `_zwl_landing`); een bestaande pagina met dezelfde slug wordt geweigerd.
- Uitgevoerd als gebruiker `breynsv` (nodig voor HTML-widget en CSS). Niets anders aangeraakt: geen sjablonen, kit, menu's, opties of plug-ins.
- **Opmaak:** één stylesheet per pagina in "Aangepaste CSS" van de pagina (Elementor Pro). Elke regel begint met `selector` (wordt `body.elementor-page-<id>`) en de inhoud zit in containers met klasse `.zwl`, dus niets lekt naar de sitehoofding of voettekst. Bronbestanden: `bron/basis.css` (gedeeld), `bron/pagina-N.css`, plus `assets/apply-form.css` dat automatisch voorafgegaan wordt.
- **Ankers:** `#solliciteer` is het CSS-ID van de formulierblok; in #2 opent elke knop met link `#check` het formuliervenster. Een knop toevoegen die het venster opent = gewoon link `#check` geven.
- **Formulier:** verstuurt nog niets (demomodus, zoals de draft). Koppelen kan later in de HTML-widget (`ZwijsenApply.mount('#zw-apply', { endpoint: … })`).
- Na het plaatsen: `wp elementor flush-css`. Tussen 27/10 en 28/10 waren de CSS-bestanden van de vijf pagina's weer gewist (door iets anders dan dit werk — waarschijnlijk een volgende flush); ze worden bij het eerste bezoek opnieuw aangemaakt en dat is gecontroleerd (zelfde grootte als de dag ervoor).

### Exports

`vacature-technieker-1.json` … `-5.json` in deze map zijn Elementor-sjablonen (formaat 0.4, type page, met de pagina-instellingen en CSS). Importeren: Templates → Saved Templates → Import, of opnieuw plaatsen met `bron/plaats.php`. Ze verwijzen naar de foto's met id 1230 en 1240 en de URL op staging.zwijsen.be — op productie bestaan dezelfde id's (staging is een kopie), maar de URL moet daar zwijsen.be zijn; Elementor neemt bij import het id over, de URL past zich niet vanzelf aan bij achtergrondfoto's.

### Verificatie, per pagina

| # | Render | Hoofding/voettekst | PHP-meldingen | Formulier 1440 | Formulier 390 | Pixel |
|---|---|---|---|---|---|---|
| 1 | 200 | ja | geen | bedankscherm | bedankscherm | nee |
| 2 | 200 | ja | geen | bedankscherm (venster) | bedankscherm* | nee |
| 3 | 200 | ja | geen | bedankscherm | bedankscherm* | nee |
| 4 | 200 | ja | geen | bedankscherm | bedankscherm | nee |
| 5 | 200 | ja | geen | bedankscherm | bedankscherm | nee |

\* De eerste poging faalde omdat de cookiebalk onderaan de klik opving; na wegklikken van de balk lukte het. Geen horizontale scroll op 390 px. Het enige "gebroken" beeld is een WordPress-emoji van s.w.org die in de testomgeving geblokkeerd werd.

Methode: de pagina wordt op de server zelf gerenderd (PHP, zonder Apache, dus zonder wachtwoord), en in een lokale Chromium geladen waarbij elk staging-bestand (CSS, JS, lettertypes, foto's) via ssh van de server komt.
