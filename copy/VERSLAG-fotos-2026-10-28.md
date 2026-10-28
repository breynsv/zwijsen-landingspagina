# Foto's uit Jana's document in de vijf voorstellen — 28 okt 2026

## Summary

- **24 foto's** uit het document van Jana staan nu in de voorstellen. **8 komen uit het origineel** in de WordPress-mediabibliotheek, op volle resolutie. **16 komen uit de PDF**, omdat ze niet in de bibliotheek staan: 13 selfies, werfbeelden en Instagram-versies met logo, plus de kaart.
- **De bibliotheek is volledig doorzocht.** Dat zijn 537 originelen op staging, vergeleken met perceptuele hash en met beeldkenmerken. De lijst is ook vergeleken met de publieke medialijst van zwijsen.be: live heeft geen enkele foto die staging niet heeft. Jana's selfies en werffoto's zitten dus niet in WordPress. Ze komen van elders, waarschijnlijk van Instagram of een telefoon.
- **De kaart** "Regio Dendermonde in beeld" is uit de PDF genomen. Het is een OpenStreetMap-kaart en de bronvermelding staat in het beeld zelf.
- **Gewicht:** 52 bestanden in `assets/img/jana/` (WebP + JPEG-terugval). Dat is 1,8 MB WebP en 2,8 MB JPEG. Per pagina laadt ongeveer 1,6 MB WebP (voorstel 5: ongeveer 1,0 MB). Alles onder de vouw laadt pas bij het scrollen.
- **Voorstel 1:** foto onder de titel naast het formulier; op mobiel staat hij na het formulier. Stockachtergrond weg.
- **Voorstel 2:** foto bovenaan de testkaart. Stockfoto in "Hoe ziet je dag eruit?" vervangen door de selfie in de bestelwagen, die daarom niet nog eens in de fotoband staat.
- **Voorstel 3:** de foto vervangt de stockachtergrond van de hero.
- **Voorstel 4:** foto onder de knoppen in de hero.
- **Alle vier (1–4):** fotoband onder de voordelen, werfgalerij met knop, kaart bij "Ben jij dit?", portretten bij de citaten en de groepsfoto bij "Kom je in aanmerking? Doe de test."
- **Voorstel 5 (mobiel)** krijgt een lichtere set:
  - De fotoband is een veegstrook van 8 in plaats van 14 foto's.
  - De werfgalerij toont 3 van de 5 foto's; de glazen overkapping en de paarse gang zijn weggelaten.
  - Weggelaten uit de band: deur van de bestelwagen, luchtfoto van het hoofdkantoor, plannen aan de muur, materiaal klaarzetten, kabelhaspels in het magazijn en de selfie in de werkplaats.
- **Gecontroleerd** in headless Chromium op 1440 en 390 px, voor alle vijf de voorstellen: elke foto laadt, geen fout 404, geen fouten in de console, niet horizontaal te scrollen, en het formulier komt telkens tot het bedankscherm. De screenshots heb ik zelf bekeken.
- **Niet gedaan:**
  - De Elementor-JSON's (`elementor/`) en staging zijn niet bijgewerkt; WordPress is niet aangeraakt.
  - Het tweede Steven-citaat (open vraag aan de klant) heeft bewust geen portret.
  - Op een echte iPhone niet getest.

## Detail

### Herkomst per foto

Gevonden in de bibliotheek (`staging.zwijsen.be/wp-content/uploads/`):

| Bestand | Origineel |
|---|---|
| technieker-bij-bestelwagen (hero; 1600 + 900) | 2022/05/ZWIJSEN-82-scaled.jpg (2560×1708) |
| collega-lacht-op-de-werf | 2022/05/ZWIJSEN-85-scaled-e1652778231319.jpg (de gespiegelde WP-bewerking, zoals in het document) |
| ploeg-bij-de-bestelwagen | 2022/05/ZWIJSEN-144-scaled.jpg |
| plannen-bekijken | 2022/01/13316839_1013449352025834_6739207425941184502_o.jpg (2048×1366) |
| magazijn-materiaal-klaarzetten | 2022/05/ZWIJSEN-17-scaled.jpg |
| magazijn-overleg | 2022/01/ZWIJSEN-118-scaled.jpg |
| kabelhaspel-buiten | 2022/05/ZWIJSEN-81-scaled.jpg |
| ploeg-electro-zwijsen-luchtfoto (groep; 1600 + 900) | 2022/05/ZWIJSENdrone-6-scaled-e1656668971938.jpg (2560×1074, exact dezelfde uitsnede) |

Uit de PDF, omdat er geen origineel is: technieker-opent-bestelwagen, hoofdkantoor-luchtfoto, kabelhaspels-magazijn, selfie-helm-en-baksteen, verdeelbord-bekabelen, plannen-tekenen-werkplaats, selfie-in-de-bestelwagen, selfie-in-de-werkplaats, de vijf werf-*-foto's, kaart-regio-dendermonde, steven-werfleider en kurt-werfleider. De ingesloten beelden zijn 840 tot 2048 px groot. Dat is ruim genoeg voor de 800 tot 1200 px waarop ze getoond worden.

Twee bijna-treffers zijn **afgewezen**, omdat ze uit dezelfde fotoshoot komen maar een ander beeld zijn:

- de technieker aan de zwarte bestelwagen ≈ ZWIJSEN-100: ander moment, dichterbij genomen;
- de luchtfoto van het hoofdkantoor ≈ ZWIJSENdrone-1: andere hoek.

De Instagram-versies met logo zijn dus uit de PDF genomen, met het logo erin zoals Jana ze koos.

### Werkwijze

1. De 24 ingesloten beelden uit de PDF gehaald met pypdf.
2. Alle originelen zonder `-WxH` van staging gedownload, alleen lezend (rsync, 425 MB, 537 bestanden).
3. Eerst gezocht met pHash + dHash. Voor bijgesneden beelden daarna ORB-kenmerken met RANSAC-homografie. Echte treffers halen meer dan 260 inliers, valse onder de 65.
4. Elke twijfelgeval naast elkaar bekeken.
5. Verkleind met Lanczos, zonder EXIF of GPS. WebP q74 en progressieve JPEG q78.

Scripts en tussenbestanden staan in `~/.claude/jobs/48920f76/tmp/` (niet in de repo).

### Opmaak

- Alles staat in het nieuwe `assets/photos.css`, gedeeld door de vijf pagina's.
- Elke foto is een `<picture>` met WebP en JPEG-terugval, een Nederlandse alt-tekst, `width`/`height` en `loading="lazy"` (behalve de hero).
- De fotoband is metselwerk op groot scherm en een veegstrook op mobiel (≤700 px).
- De kaart staat naast "Ben jij dit?". In smalle kolommen, zoals de profielkaart van voorstel 1, staat hij erboven (container query).
- De portretten staan rond in het onderschrift van het citaat.

### Kleine afwijkingen van het document

- **Voorstel 3:** "Wat ga je doen?" zit in de tijdlijn, dus de werfgalerij staat na de tijdlijn.
- **Voorstel 4:** de taken staan in de tabel, dus de galerij staat bij "Wat maakt deze job de moeite waard?" (onder "Grote werven"). De band staat onder de voordelentabel.
- **Alt-teksten:** de alt-teksten beschrijven wat je ziet. Werfnamen en plaatsen zijn niet ingevuld, omdat Jana ze niet geeft. Laat ze door Zwijsen aanvullen als ze die willen tonen.

### Verificatie

Script: `tmp/pw/verify.js`. Screenshots: `~/.claude/jobs/48920f76/tmp/shots/` (volledige pagina, bovenkant en bedankscherm per voorstel en breedte). 10 van de 10 combinaties zijn in orde.
