# Originele foto's van Jana en optimalisatie voor PageSpeed — 30 okt 2026

## Summary

- **9 foto's vervangen** door de originelen van Jana; 15 andere opnieuw gemaakt uit de beste bron die er al was (8 uit de WordPress-bibliotheek op volle resolutie, 7 alleen uit de PDF). Geen enkele foto is van een pagina verdwenen.
- **6 van Jana's 16 bestanden zijn verbeterd met AI.** Ze staan nu wel op de pagina's, zoals gevraagd, maar Sven moet beslissen:
  - Duidelijk opnieuw gegenereerd: `Glimlachende elektricien met plannen en gereedschap-3.png` (plannen-tekenen) en `Elektricien met schroevendraaier en schakelkast-2.png` (verdeelbord). Allebei hebben ze een andere camerahoek en een ander decor dan de gsm-foto.
  - Verzonnen tekst of logo: `Elektricien met helm en veiligheidsuitrusting-1.png` (onleesbaar "suooller of genvo svstems" op de trui), `kurt scherperAI.png` (het Zwijsen-logo is vervormd).
  - Opnieuw getekend: `Samen aan het werk bij Electro Zwijsen.png` (deur, achtergrond en de hand van de collega zijn anders) en `Glimlachende elektriciens in de werkplaats.png` (2,8 keer opgeschaald uit een foto van 384 px).
  - Van 5 van de 6 staat de echte gsm-foto ook in de map. Terugwisselen is één regel in `gen.py`.
- **Niet gebruikt:** `IMG_3263.JPEG` (een ander moment op de werf in Brussel, geen vervanging van een bestaand beeld), `20200924_134756.JPG` (andere opname van de paarse gang) en de 5 echte gsm-foto's achter de AI-bestanden.
- **Geen origineel ontvangen** voor: deur van de bestelwagen, luchtfoto van het hoofdkantoor, kabelhaspels in het magazijn, werf Brussel, glazen dak, klassieke gevel en de kaart. Die komen nog uit de PDF; die beelden zijn wel groter dan de 800 px van vroeger.
- **Gewicht (beeldbytes, gsm 390 px @3x, hele pagina):** van 1,4 MB naar ongeveer 1,0 MB. **Op 1440:** van 1,8 MB naar 0,6 MB. Voorbeeldfilm: van 737 naar ongeveer 300 kB.
- **Lighthouse mobiel** (mediaan van 3 runs): score blijft 95 en 96, LCP blijft ongeveer 2,0–2,3 s, CLS blijft 0,09. Dat komt door een verschuiving van `<main>` die er al was, niet door de foto's. De totale pagina is 19 % lichter.
- **Gecontroleerd** in Playwright op 390 en 1440, voor alle 8 pagina's: geen 404, geen fouten in de console, niet horizontaal te scrollen, en het formulier komt telkens tot het bedankscherm (14 van 14). Alle 3 de films lopen al hun shots door (6, 6 en 8). Alle EXIF-, GPS-, XMP- en ICC-gegevens zijn weg (200 van 200 bestanden gecontroleerd). Op een echte iPhone en in Safari niet getest.

## Detail

### Welk origineel bij welk beeld

Gevonden met perceptuele hash (pHash + dHash + wHash), daarna met SIFT bekeken waar het beeld in de bron ligt. Elk paar is ook naast elkaar bekeken (`tmp/cmp/`).

| Beeld op de pagina | Bestand van Jana | Px | Opmerking |
|---|---|---|---|
| werf-gang-paarse-verlichting | `sbi 20200924_134756.JPG` | 1494×840 | Volledig beeld, identiek aan de PDF |
| werf-ronde-hal-bestelwagens | `IMG-20240208-WA0003.JPG` | 1599×1186 | Echt (WhatsApp) |
| steven-werfleider | `steven.jpeg` | 1030×1008 | Echt; ICC naar sRGB omgezet |
| selfie-in-de-bestelwagen | `Samen aan het werk bij Electro Zwijsen.png` | 1122×1402 | **AI**, origineel `20221212_113137.JPG` |
| selfie-in-de-werkplaats | `Glimlachende elektriciens in de werkplaats.png` | 1086×1448 | **AI-opschaling**, origineel `24715jpg.0.JPG` (384×512) |
| selfie-helm-en-baksteen | `Elektricien met helm en veiligheidsuitrusting-1.png` | 1239×1269 | **AI**, origineel `FB_IMG_1618044586995.JPG` (719×728); tekst op de trui verzonnen |
| verdeelbord-bekabelen | `Elektricien met schroevendraaier en schakelkast-2.png` | 1448×1086 | **AI, opnieuw gegenereerd**, origineel `IMG-20251121-WA0003.JPG` (staand) |
| plannen-tekenen-werkplaats | `Glimlachende elektricien met plannen en gereedschap-3.png` | 1448×1086 | **AI, opnieuw gegenereerd**, origineel `IMG-20260930-WA0000.JPG` (staand) |
| kurt-werfleider | `kurt scherperAI.png` | 1448×1086 | **AI**, geen origineel ontvangen; logo op borst en mouw vervormd |

Alle negen beelden komen exact overeen met het beeld in de PDF. Jana heeft de AI-versies dus zelf in haar document gezet. Een vergelijking met de oude PDF-uitsnede toont daarom geen verschil. Pas de vergelijking met de echte gsm-foto laat het zien.

Wat in de AI-versies anders is dan in de gsm-foto:

- **plannen-tekenen:** de gsm-foto is staand, van verder weg genomen, met rode mappen en een toiletdeur. De AI-versie is liggend, dichtbij, met studiolicht, een nieuw logo op het T-shirt en een ander plan.
- **verdeelbord:** de gsm-foto is staand en van bovenaf genomen. De AI-versie is liggend en van dichtbij, met een andere achtergrond: planten en een bak in plaats van een inkomhal.
- **helm:** het gezicht is gladder en de kleur verzadigd. "member of group zwijsen" is "suooller of genvo svstems" geworden.
- **bestelwagen:** de lenscorrectie is anders, de deurstijl en de container erachter zijn anders, en de hand en de gsm van de collega zijn opnieuw getekend.
- **werkplaats:** opgeschaald van 384 px; de gezichten zijn licht gladgestreken.
- **kurt:** het Zwijsen-logo op borst en mouw is een vervormde vlek.

Beeldbestanden zonder plaats op een pagina: `IMG_3263.JPEG` (Brussel van bovenaf, op een ander moment; dit bestand bevatte GPS en is niet gebruikt) en `20200924_134756.JPG` (een staande opname van de gang).

### Bron van de overige 15

- **WordPress, volle resolutie (8):** technieker-bij-bestelwagen, collega-lacht-op-de-werf, ploeg-bij-de-bestelwagen, plannen-bekijken, magazijn-materiaal-klaarzetten, magazijn-overleg, kabelhaspel-buiten en ploeg-electro-zwijsen-luchtfoto. Die zijn 2048 tot 2560 px groot; we gebruiken ze tot 1600 px.
- **Alleen uit de PDF (7):** technieker-opent-bestelwagen, hoofdkantoor-luchtfoto en kabelhaspels-magazijn (elk 1080 px), werf-brussel-hoogbouw-kraan (1634), werf-glazen-dak-toren (842), werf-klassieke-gevel (1536) en kaart-regio-dendermonde (1200).

### Optimalisatie

- **Per foto:** de breedtes 480 / 800 / 1200 / 1600, nooit breder dan de bron. Als de bron minstens 150 px breder is dan de laatste stap, komt de bronbreedte erbij. De portretten krijgen ook een versie van 240 px.
- **Formaten:** AVIF q50 (de kaart q62), WebP q75 (de kaart q82) en één progressieve JPEG q75 op 800 px als terugval. Alles is in sRGB, met de EXIF-oriëntatie toegepast, en zonder EXIF, GPS, XMP of ICC. Uitsnedes op ware grootte heb ik naast elkaar bekeken: geen zichtbaar verlies.
- **Map:** `assets/img/jana/` telt nu 200 bestanden (AVIF 4,1 MB, WebP 6,1 MB, JPEG 2,1 MB). Een browser haalt per beeld er maar één. De 52 oude bestanden zijn verwijderd. Alleen twee verslagen (`.md`) noemen hun namen nog.
- **HTML:** elke foto is een `<picture>` met AVIF, WebP en een JPEG-`<img>`. De `width` en `height` geven de echte beeldverhouding. De `sizes` is per plek gemeten in Chromium, op 19 schermbreedtes. Bij `object-fit: cover` telt de breedte waarop het beeld werkelijk getoond wordt. Alles onder de vouw laadt met `loading="lazy"` en `decoding="async"`.
- **LCP per pagina:**
  - Voorstel 2 en 4: de herofoto. Hij krijgt `fetchpriority="high"`, geen lazy loading, en een `<link rel="preload" type="image/avif" imagesrcset imagesizes>`.
  - Voorstel 3: de CSS-achtergrond van de hero, met een preload per mediaquery (1200 op gsm, 1600 op groot scherm).
  - Voorstel 5 en 5-film: de vervaagde heroachtergrond, op dezelfde manier (800 en 1200).
  - Voorstel 2-film en de voorbeeldfilm: de eerste shot, met een preload.
  - Voorstel 1: de LCP is de titel. De herofoto staat daar op gsm onder het formulier en op 1440 onder de vouw, en laadt nu lazy.
- **Film:**
  - `assets/film.js` bouwt per shot een `<picture>` met AVIF en WebP, plus een `srcset` en `sizes` per shot. Daarbij is rekening gehouden met `cover`: op gsm bepaalt de hoogte van de film de breedte.
  - Alleen de volgende shot laadt vooruit, zoals voorheen.
  - De voorbeeldfilm (`voorbeeld-film.html`) heeft een eigen speler en kreeg hetzelfde. De filmstrook kreeg vaste afmetingen, zodat ze nu echt lazy laadt: eerst laadden alle 14 beelden meteen.
  - `soft` (de zoom beperkt tot 8 %) is weg bij het verdeelbord (nu 1448 px), maar blijft bij de helm (1239 px, opgeschaald).

### Metingen

Beeldbytes per pagina in headless Chromium, voor en na. "Eerste beeld" is na het laden plus 2,5 s, zonder te scrollen. "Volledig" is na scrollen door de hele pagina en de veegstroken.

| Pagina | 390@3x eerste beeld | 390@3x volledig | 1440 eerste beeld | 1440 volledig |
|---|---|---|---|---|
| 1-vacature-plus | 138 → 112 kB | 1396 → 959 kB | 951 → 374 kB | 1774 → 610 kB |
| 2-kwalificatietest | 470 → 407 | 1459 → 1029 | 1388 → 466 | 1774 → 626 |
| 2-kwalificatietest-film | 470 → 416 | 1459 → 1152 | 1286 → 459 | 1798 → 763 |
| 3-dag-op-de-werf | 615 → 374 | 1372 → 959 | 1275 → 432 | 1798 → 627 |
| 4-wat-je-krijgt | 138 → 112 | 1396 → 959 | 848 → 330 | 1774 → 610 |
| 5-snelsollicitatie-mobiel | 746 → 545 | 1170 → 834 | 746 → 288 | 1170 → 416 |
| 5-snelsollicitatie-mobiel-film | 413 → 373 | 1170 → 950 | 539 → 245 | 1170 → 450 |
| voorbeeld-film | 737 → 296 | 737 → 310 | 737 → 268 | 737 → 268 |

De winst op gsm is kleiner dan op 1440. Een scherm met pixeldichtheid 3 haalt terecht 1200 px voor een strookfoto van 360 px breed: dat kost meer bytes, maar het beeld is scherper. Elk getal bevat ook het WordPress-logo (89 kB PNG).

Lighthouse 13.5 mobiel (gesimuleerde throttling), mediaan van 3 runs per kant:

| Pagina | Score | LCP | CLS | Totaal gewicht |
|---|---|---|---|---|
| 2-kwalificatietest-film, voor | 95 | 2,15 s | 0,094 | 837 kB |
| 2-kwalificatietest-film, na | 95 | 2,30 s | 0,094 | 679 kB |
| 5-snelsollicitatie-mobiel-film, voor | 96 | 2,03 s | 0,092 | 852 kB |
| 5-snelsollicitatie-mobiel-film, na | 96 | 2,03 s | 0,092 | 709 kB |

- **LCP:** het verschil van 0,15 s op 2-film valt binnen de spreiding. Op gsm haalt de herofoto nu 1600 px AVIF (31 kB) in plaats van 900 px WebP (26 kB), omdat de liggende foto de hele hoogte van de film vult.
- **CLS van 0,09:** dat is `<main>`, die verschuift. Dat gebeurde voor de wijziging net zo, en het heeft niets met de foto's te maken.
- **Grootste resterende besparing volgens Lighthouse:** het WordPress-logo, een PNG van 2558×1095 px en 90 kB. Het valt buiten deze opdracht, maar het is een snelle winst.
- **"Ongeschaalde" beelden:** de twee keurmerkbeelden (klasse 8, VCA). Ook die vallen buiten deze opdracht.

### Verificatie

- **Script:** `tmp/pw/verify3.js` en `form3.js`, op 390@3x en 1440@1x, voor alle 8 pagina's.
  - Geen 404 en geen fouten in de console.
  - De pagina is niet breder dan het scherm.
  - Elk beeld laadt; de browser kiest overal AVIF.
  - Het formulier komt telkens tot het bedankscherm.
  - De films lopen alle shots door.
- **Screenshots:** `~/.claude/jobs/48920f76/tmp/shots3/`.
- **Film op 1440:** zelf bekeken, vlak voor het einde van elke zoom. Het verdeelbord is nu scherp, de werf in Brussel ook. De helm en de luchtfoto van het hoofdkantoor zijn aanvaardbaar, maar licht zacht: hun bron is 1239 en 1080 px.
- **Geen achtergebleven processen:** na elke Lighthouse-run gecontroleerd. Geen headless Chrome meer actief; beide `http.server`-processen zijn gestopt.
- **Testscript aangepast:** het oude testscript zocht nog naar het toestemmingsvakje. Dat vakje is bewust weg sinds commit 5ff5195, dus het script slaat die stap nu over als het vakje er niet is.

### Scripts

In `~/.claude/jobs/48920f76/tmp/`, niet in de repo:

- `scripts/match2.py` (hash) en `scripts/locate.py` (SIFT): koppelen de originelen aan de beelden op de pagina's.
- `scripts/gen.py`: maakt alle varianten. Een bron terugwisselen is één regel in `SRC`.
- `scripts/rewrite.py`: herschrijft de HTML.
- `pw/sizes.js`: meet de getoonde breedtes en de LCP.
- `pw/bytes.js`, `pw/verify3.js` en `lh.sh`: de metingen en controles.
