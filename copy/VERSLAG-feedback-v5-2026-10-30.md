# Feedback van Jana en Katrien verwerkt in voorstel 5 met film — 30 okt 2026

## Summary

- **Klaar (alle punten behalve 2):** quizintro en grijze tekst, nieuwe vormgeving van de quiz (geen icoontjes, geen letters, stille voortgang), resultaattekst, bedankscherm zonder opsomming, taalvraag met meerdere keuzes, "technieker" op de twee plaatsen die Jana voorstelt plus de nieuwe `<title>`, de teksten van Katrien bij moeite waard, voordelen en profiel, bellen/mailen onderaan, de baksteenfoto weg, de foto van Steven met de collega weg, minder werffoto's met betere uitsnedes, de twee labels weg en een eigen kaart.
- **Niet gedaan (kan nog niet):** (1) Katriens foto's van installaties zijn nog niet binnen. Voorlopig staan er een gang met verlichting en een verdeelbord. (2) De bevestigingsmail die het bedankscherm belooft, bestaat nog niet; die wordt gebouwd in de WordPress-fase. Bij beide staat een HTML-commentaar in de pagina.
- **Vragen voor de klant:** (a) Het mailadres: Katrien schreef `personeelsdienst@zwijsen.be`, maar de formulieren op zwijsen.be sturen naar `personeel@zwijsen.be`. Welk adres is het juiste? (b) De twee zinnen over de hospitalisatieverzekering en de terugbetaling van medische kosten zijn nu één zin, in Katriens nieuwe formulering. Is dat zo bedoeld? (c) De kop "Ben jij dit?" is vervangen door "Word jij onze nieuwe technieker?". Of willen ze beide?
- **Bijgevangen:** de herofoto van pagina 5 stond sinds de fotocommit van vanochtend op volle sterkte, waardoor de titel onleesbaar was. De `opacity` stond buiten de CSS-regel; dat is hersteld. Voorstel 5 zonder film heeft dezelfde fout nog (de andere voorstellen zijn vervallen).
- **Controle (headless Chromium, 320/390/768/1440):** geen consolefouten, geen 404's, niet horizontaal scrollen. De eerste vraag staat in beeld op 390×844 (eerste antwoord eindigt op 734 px). De quiz loopt volledig door met NL + FR + "andere taal: Pools" (lege keuze en leeg tekstvak geven elk een foutmelding). Resultaat- en bedanktekst zijn letterlijk, met "Bedankt, Jan!". De film toont 6/6 ondertitels zonder overlap en zonder rand, en pauzeren werkt. Bij minder beweging staat er één still zonder animatie. De kaart: 15 labels zonder overlap, binnen de kaart. Alle andere pagina's laden zonder JS-fout. Screenshots: `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-v5/`.
- **Sven, 30 okt:** de AI-versies van het verdeelbord en van de plannen-aan-tafel blijven staan. Dat is ook zo gedaan.

## Detail

### Per feedbackpunt

| Feedback | Wat er veranderd is |
|---|---|
| Jana: intro "Solliciteer in 30 seconden" / "Doe de test. Beantwoord 4 korte vragen." | Formulierkop op pagina 5 aangepast. |
| Jana: grijze lettertjes enkel "100% vertrouwelijk" | Onder elke vraag staat alleen nog "100% vertrouwelijk", in gewone kleine letters (geen hoofdletters met spatiëring meer). |
| Jana: vormgeving lijkt op Personeelsmagneet; icoontjes identiek | Icoontjes en de letters A/B/C/D zijn weg. De antwoorden staan nu in één lijst met haarlijnen ertussen, met een rond (één keuze) of vierkant (meerdere) vakje. Een gekozen antwoord krijgt een lichtgele achtergrond, een gele lijn links en een zwart vakje met een geel vinkje. Rood staat alleen nog op de knop. De voortgang is "Vraag 2 van 4" in gewone tekst met een dunne lijn eronder, in plaats van een pil. De labels van de contactvelden staan niet meer in hoofdletters. Het blijven echte radio's en selectievakjes, met labels, bediening met het toetsenbord (pijltjes, spatie) en een zichtbare focusrand. |
| Jana: "Je komt in aanmerking!" vervangen | De kop van de contactstap is nu "Bedankt voor je antwoorden! We horen graag meer van je". |
| Jana: geen bullets op het einde | Bedankscherm: "Bedankt, {voornaam}!" (zonder naam: "Bedankt!"), daarna de drie zinnen van Jana, geen stappen. Onderaan Katriens bel- en mailzin met werkende tel- en mailto-link. |
| Jana: "technieker" | In de hero: "…voor een job als technieker bij Electro Zwijsen!". Bij Jouw profiel is de kop "Word jij onze nieuwe technieker?". De `<title>` is nu "Vacature elektricien Dendermonde – technieker algemene elektriciteit \| Electro Zwijsen". "Elektricien" blijft in de H1 en het bovenschrift. |
| Jana: "De selectie van de nieuwe werven" weg | De titel is weg. Het fotoblok staat nog bij "Wat ga je doen?". |
| Jana: werffoto's slecht uitgesneden | De oorzaak was een panorama-uitsnede van 3:1. Nu is het 3:2 boven en 4:3 eronder. De werf in Brussel is opnieuw gemaakt uit Jana's origineel `IMG_3263.JPEG` (nieuw bestand `werf-brussel-ruwbouw-kraan`, AVIF/WebP/JPEG 480–1600, zonder EXIF/GPS/ICC, gecontroleerd). |
| Jana: kaart identiek aan andere campagnes | Vervangen door een eigen inline SVG van ongeveer 5 kB, zonder beeldbestand. Dendermonde staat in het midden (rood met geel), met een stippelcirkel van 20 km, een hulpcirkel van 10 km, een schaalbalk en een noordpijl. 12 gemeenten staan op hun echte ligging (zie onder). De Schelde en de Dender zijn getekend via de dorpen aan hun oever. Toegankelijk via `role="img"`, `<title>` en een `<desc>` die alle gemeenten opsomt. |
| Jana: "REGIO DENDERMONDE IN BEELD" weg | Het label is weg; er is geen zichtbaar onderschrift meer. Het bovenschrift in de hero ("Vacature elektricien · regio Dendermonde") is gebleven. |
| Jana: test vs. "Jouw profiel" | Opgelost met de nieuwe resultaattekst (zie boven). |
| Katrien: Afwisseling | "Geregeld nieuwe projecten, verspreid over heel België met zwaartepunt in Vlaanderen." Aangepast in de lijst en in de ondertitel van de film. |
| Katrien: Vrijheid in je vak | "Je werkt zelfstandig in team. Het resultaat telt." "Het resultaat telt" is de bestaande zin van Jana; "organiseert je werk zelf" is weg, omdat dat net het gevoel van alleen werken gaf. Aangepast in de lijst en in de film. |
| Katrien: 20 vakantiedagen en 12 ADV | In de voordelen, het hero-label en de meta description. "32 vakantiedagen" komt nergens meer voor. |
| Katrien: hospitalisatieverzekering | "Uitgebreide hospitalisatieverzekering voor jou én je gezin, inclusief tandzorg en ambulante medische kosten". De aparte regel "Extra terugbetaling van doktersbezoeken…" is daarin opgegaan (vraag b). |
| Katrien: vakantiegeld | Toegevoegd. |
| Katrien: taal, VCA en werven | De drie zinnen van Katrien, letterlijk. |
| Katrien: foto van Steven vervangen | Het gaat om `selfie-in-de-bestelwagen` (Steven met de collega achter het stuur); dat is visueel nagekeken. Op pagina 5 stond die in de fotostrook. Vervangen door `selfie-in-de-werkplaats`. |
| Katrien: taalvraag | "In welke talen kun je overleggen met collega's en veiligheidsinstructies begrijpen?" met de hint "Je mag meerdere talen aanduiden." De opties zijn selectievakjes: Nederlands, Frans, Engels en "Een andere taal:" met een tekstvak. Minstens één keuze is verplicht, en als "andere" is aangevinkt, ook het tekstvak. In het meetpunt heet de vraag nu `talen`. Naar Meta/GA gaan alleen het stapnummer en die naam, nooit de antwoorden. |
| Katrien: bellen of mailen | Het bel-blok onderaan: "Liever gewoon bellen?", de knop 015 305 305 en "of mailen personeelsdienst@zwijsen.be" als mailto-link. Dezelfde zin staat op het bedankscherm. |
| Katrien: minder bouwbedrijf | Film: werf Brussel (de enige werf), verdeelbord, gang met verlichting (Afwisseling), twee collega's bij een plan (Vrijheid in je vak, "in team"), ploeg bij de bestelwagen en de luchtfoto. Fotostrook: alleen nog terrein, ploeg, magazijn, kabelhaspel, plannen en werkplaats. Fotoblok "Wat ga je doen?": één werf en twee installatiebeelden. De klassieke gevel en de ronde hal zijn van pagina 5 verdwenen. |
| Sven: baksteen weg | Weg uit de strook (vervangen door "materiaal klaarzetten in het magazijn") en uit de film. |
| Cannot do yet | Er staan HTML-commentaren bij het fotoblok (installatiefoto's van Katrien) en bij `#apply` (bevestigingsmail), en een opmerking in `apply-form.js` bij het bedankscherm. |

### Kaart: controle van de ligging

De coördinaten zijn die van de gemeentecentra, nagekeken in OpenStreetMap (Nominatim). De projectie is equirectangulair rond Dendermonde (51,0286 N; 4,1006 O) met cos(φ) als correctie, op 7 px per km. Afstanden op de kaart: Aalst 10,9 km Z, Sint-Niklaas 15,4 km N, Lokeren 11,5 km NW, Wetteren 15,6 km W, Zele 6,2 km NW, Hamme 8,1 km NNO, Buggenhout 7,3 km O, Lebbeke 4,1 km ZO, Berlare 6,8 km W, Temse 13,3 km NO, Puurs 13,7 km O en Wichelen 9,1 km W. Alles ligt binnen de cirkel van 20 km. De rivieren gaan langs Wetteren, Schellebelle, Uitbergen, Wichelen, Berlare, Appels, Dendermonde, Baasrode, Sint-Amands, Mariekerke en Temse (de Schelde) en langs Aalst, Gijzegem en Denderbelle (de Dender). Ze zijn afgevlakt en dus bij benadering getekend. Het script is `tmp/scripts/map5.py`.

### Gedeelde bestanden

- `assets/apply-form.js` en `.css` zijn gewijzigd. De andere voorstellen krijgen dus ook de nieuwe quiz, met de nieuwe taalvraag en het nieuwe bedankscherm. Alle 10 andere pagina's laden zonder JS-fout. `assets/film.js` en `film.css` zijn niet aangeraakt.

### Controle in cijfers

| | 320×640 | 390×844 | 768×1024 | 1440×900 |
|---|---|---|---|---|
| Onderkant eerste antwoord | 903 (onder de vouw; was 839) | 734 (was 767) | 737 | 762 |
| Horizontaal scrollen, consolefouten, 404 | 0 / 0 / 0 | 0 / 0 / 0 | 0 / 0 / 0 | 0 / 0 / 0 |
| Film: ondertitels, overlap, rand | 6, geen, geen | 6, geen, geen | 6, geen, geen | 6, geen, geen |
| Kaart: labeloverlap, kleinste letter | geen, 10,9 px | geen, 13,7 px | geen, 16,4 px | geen, 16,4 px |

De quizflow is getest op 320, 390 en 1440. Minder beweging is getest op 390 en 1440: één shot ("In duo"), 0 animaties. Beeldbytes vóór de eerste actie: 176 kB (390) en 254 kB (1440).

Op 320 komt de eerste vraag 64 px lager uit dan voorheen, omdat de voortgangstekst nu boven de lijn staat. Op 320 stond die vraag ook voorheen al onder de vouw.

### Niet gedaan / buiten opdracht

- Geen WordPress, geen push. Niet getest op een echte iPhone of in Safari.
- De knop "Kom je in aanmerking? Doe de test." bij het profiel is ongewijzigd gebleven: daar staat niets over in de feedback.
