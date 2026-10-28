# De fotofilm in voorstel 2 en 5 — 28 okt 2026

## Summary

- **Voorstel 2: de film is de hero.** De knop "Doe de test" opent een venster en staat dus gewoon in de film; zo ziet ook wie niet scrolt (op gsm de meesten) de zes redenen. Eerste shot = de foto van de gewone hero, dus even snel. De lijst met de vier vragen staat direct onder de film. → `2-kwalificatietest-film.html`
- **Voorstel 5: de film staat bij "Wat maakt deze job de moeite waard?", direct onder het formulier.** Het formulier blijft exact waar het stond (gemeten op 320 en 390 px: op de pixel gelijk aan het origineel). De film laadt pas als je ernaartoe scrolt; het punt in de lijst eronder licht mee op. → `5-snelsollicitatie-mobiel-film.html`
- **Afgewezen:** hero in 5 (duwt het formulier onder de vouw) · band onder de hero in 2 (de helft scrolt er niet) · "moeite waard" in 2 (dubbel met het raster, pas na scroll) · slotscène in beide (komt te laat voor koud verkeer).
- **Controle (headless Chromium, 320/390/768/1440):** geen consolefouten, geen 404, geen horizontaal scrollen, ondertitels raken titel/knop/balk nooit over een volledige ronde van 6 shots, nooit een rand zichtbaar bij zoom/pan, formulier tot het bedankscherm (390 en 1440), pauze werkt, film staat stil terwijl het testvenster open is (2), "minder beweging" = één still. Elke knop in 2 opent het venster. Screenshots bekeken.
- **Gewicht vóór de eerste actie:** 5 = 0 KB extra (film laadt pas bij scrollen). 2 op gsm = 0 KB extra bij het openen (zelfde 25 KB-foto); de tweede shot (126 KB) laadt pas na 2,75 s. 2 op desktop = +24 KB (scherpere 1600 px-versie van dezelfde foto). Alle filmfoto's staan elders op de pagina, dus wie doorscrolt haalt niets dubbel.
- **Gedeelde code:** `assets/film.css` + `assets/film.js`. Origineel 2, 5, keuze.html en index.html zijn niet aangeraakt. Overzicht: `film-voorbeelden.html`.
- **Open vragen voor Sven:** (1) welke van de twee wil je naar Jana/Katrien sturen, of beide? (2) De PDF-foto's (verdeelbord, selfie met helm) zijn op een gsm met scherp scherm zichtbaar zacht; de originelen van Jana zouden dat oplossen. (3) Niet getest op een echte iPhone.

## Detail

### De vraag

Elektriciens komen koud binnen via een Meta-advertentie, ongeveer 80% op een gsm. Het doel is
één handeling: de test van vier vragen starten (voorstel 2) of het formulier invullen
(voorstel 5). De film moet daarbij helpen en mag die handeling niet vertragen of verbergen.

### Voorstel 2 — "De check" (test in een venster)

| Plaats | Tijd tot eerste actie | Knop boven de vouw | Gewicht vóór interactie | Afleiding/emotie | Leesbaarheid | Past bij het concept |
|---|---|---|---|---|---|---|
| (a) film als hero | gelijk: de knop "Doe de test" staat in de film, meteen in beeld | ja, op elk scherm | gelijk: shot 1 is dezelfde foto als de huidige hero; volgende shots laden pas vlak voor ze verschijnen | hoogste emotie, en iedereen ziet de zes redenen zonder te scrollen | ondertitels bovenaan, titel en knop onderaan: ze raken elkaar nooit | de knop opent een venster, dus hij kan overal staan, ook in de film |
| (b) band onder de hero | ongewijzigd | ja | +1 beeld zodra je scrolt | gemiddeld; de helft van de bezoekers scrolt er niet | goed | voegt een blok toe tussen hero en "de moeite waard" |
| (c) bij "de moeite waard" | ongewijzigd | ja | idem | goed, de ondertitels zijn de zes punten | goed | dubbelop met het raster eronder, en pas na een scroll |
| (d) slotscène | ongewijzigd | ja | niets vóór interactie | sterk, maar op gsm komt maar een klein deel tot onderaan | goed | mooi slot, maar de emotie komt te laat voor koud verkeer |

**Keuze: (a), de film als hero.** In voorstel 2 is elke knop een knop die het venster opent, dus
de knop kan in de film zelf staan: de bezoeker ziet in de eerste seconde de titel, de knop en
bewegend beeld van de job. Wie niet scrolt (op gsm de meerderheid) krijgt toch de zes redenen
uit "Wat maakt deze job de moeite waard?" te zien. De eerste shot is dezelfde foto als de
huidige hero, zodat de pagina even snel opent. De testkaart met de vier vragen ("vooraf weten wat
komt") staat direct onder de film en blijft het hart van het concept; alleen haar foto is eruit,
want die zit nu in de film. De film pauzeert vanzelf zodra het testvenster opent.

### Voorstel 5 — "Snelsollicitatie" (gebouwd voor de duim)

| Plaats | Tijd tot eerste actie | Formulier boven de vouw (390×844) | Gewicht vóór interactie | Afleiding/emotie | Leesbaarheid | Past bij het concept |
|---|---|---|---|---|---|---|
| (a) film als hero | trager: titel, film en knop vóór het formulier | **nee**: een film van fatsoenlijke hoogte duwt de eerste vraag onder de vouw | +1 groot beeld vooraan | hoog, maar concurreert met het formulier om de eerste blik | ondertitels botsen met titel en chips | breekt de regel "formulier meteen in beeld" — afgewezen |
| (b) band direct onder het formulier | ongewijzigd | ja | niets, de film laadt pas als hij nadert | goed: precies wie twijfelt en voorbij het formulier scrolt | goed | op deze pagina is dat dezelfde plek als (c) |
| (c) bij "de moeite waard", met de zes punten als ondertitels | ongewijzigd | ja | niets vóór interactie | sterk: zes punten, zes beelden, telkens het juiste beeld bij de juiste zin | goed, korte ondertitels onderaan op een donkere voet | toont waarom het de moeite is, op het moment dat iemand twijfelt |
| (d) slotscène | ongewijzigd | ja | niets | sterk, maar weinigen scrollen tot daar | goed | de lichtste pagina eindigt al met een groepsfoto en een knop |

**Keuze: (c), bij "Wat maakt deze job de moeite waard?", direct onder het formulier.** Het
formulier blijft onaangeroerd bovenaan. Wie voorbij het formulier scrolt, twijfelt nog, en net
dan toont de film de zes redenen, elk met een eigen beeld en Jana's eigen zin als ondertitel.
De lijst eronder blijft staan (voor wie snel leest, en voor schermlezers) en licht mee op met de
film. De film laadt pas als hij in de buurt komt, dus de pagina opent even snel als nu; en
alle zes de foto's staan verderop op de pagina al, zodat wie helemaal scrolt geen extra
bestanden binnenhaalt.

### Wat er gebouwd is

- `assets/film.js` — de speler uit `voorbeeld-film.html`, herbruikbaar gemaakt (`ZwijsenFilm.mount`). Nieuw t.o.v. het voorbeeld:
  - `hold(reden)`: de pagina kan de film stilzetten (gebruikt in 2 terwijl het testvenster open is).
  - `lazyStart`: niets laden tot de film binnen 300 px van beeld komt (gebruikt in 5).
  - De volgende shot laadt halverwege de huidige, niet meteen.
  - `soft: true` voor de 800 px-foto's uit de PDF: zoom hoogstens 8%.
  - De pan wordt automatisch begrensd zodat er nooit een donkere rand verschijnt (in het voorbeeld kon dat bij schaal 1,04 met 3% pan).
  - Pauze stopt ook de filmkorrel (alle beweging, WCAG 2.2.2).
  - Een `<img>` die al in de HTML staat wordt de eerste shot (in 2, voor een snelle LCP, met preload).
- `assets/film.css` — filmlook (vignet, korrel, breedbeeldbalken), tekstlaag als één kolom: ondertitel en vaste inhoud kunnen elkaar niet overlappen. Kicker in gewone schrijfwijze in plaats van hoofdletters.
- Ondertitels: de zes punten uit "Wat maakt deze job de moeite waard?" van Jana, woord voor woord (titel als kicker, uitleg als zin). Niets bijverzonnen; "Kom je erbij?" uit het voorbeeld is weggelaten omdat het niet in Jana's tekst staat.
- Beeld per punt: In duo → technieker bij de bestelwagen (2) / ploeg bij de bestelwagen (5); Grote werven → werf Brussel met kraan; Breed takenpakket → verdeelbord bekabelen; Afwisseling → ronde hal met bestelwagens; Vrijheid in je vak → selfie met helm; Vast contract (150 collega's) → luchtfoto van de ploeg.
- Volgorde: in 2 begint de film met "In duo", omdat dat de foto van de gewone hero is (snel, scherp, een mens in beeld). In 5 volgt de film de volgorde van de lijst, zodat het oplichtende punt van boven naar onder loopt.
- Voorstel 2: op gsm (≤520 px) is het bovenschrift "Vacature elektricien · regio Dendermonde" in de film weggelaten; het zegt hetzelfde als de titel en kost twee regels. De kleefbalk verschijnt nu na de film. De testkaart staat op desktop in twee kolommen (tekst + knop | de vier vragen).
- Voorstel 5: verder niets gewijzigd; enkel de film tussen de titel en de lijst, en een rode rand op het punt dat in beeld is.
- Minder beweging: 2 toont de eerste shot, 5 de ploeg bij de bestelwagen ("In duo"), telkens met ondertitel, zonder pauzeknop of voortgang.

### Controle in cijfers

Script: `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/pw/filmcheck.js` (elke 400 ms over een volledige ronde van ~35 s de vakken gemeten). Screenshots: `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-film/`.

| | 320×640 | 390×844 | 768×1024 | 1440×900 |
|---|---|---|---|---|
| 2: 6/6 ondertitels gezien, overlap, rand | 6, geen, geen | 6, geen, geen | 6, geen, geen | 6, geen, geen |
| 5: idem | 6, geen, geen | 6, geen, geen | 6, geen, geen | 6, geen, geen |
| Pauze: beeld en shot staan stil 6,5 s, hervat daarna | ja | ja | ja | ja |
| 2: film stil zolang het venster open is | ja | ja | ja | ja |
| 5: eerste vraag (onderkant 1e antwoord) | 839 px (= origineel) | 767 px van 844 (= origineel) | 673 | 698 |

Op 320×640 valt de eerste vraag van 5 onder de vouw, maar dat is in het origineel exact hetzelfde.

Beeldbytes geladen zonder scroll of klik, 4 s na laden (totaal is ruis door de lazy fotobanden, die Chrome tot ~2500 px vooruit laadt; het verschil dat de film veroorzaakt staat apart):

| Pagina | gsm 390 | desktop 1440 | toe te schrijven aan de film |
|---|---|---|---|
| 2 origineel | 445 KB | 1387 KB | — |
| 2 film | 571 KB | 1285 KB | gsm: 0 KB bij openen, +126 KB (shot 2) na 2,75 s; desktop: +24 KB |
| 5 origineel | 745 KB | 745 KB | — |
| 5 film | 412 KB | 538 KB | 0 KB (de film duwt de lazy foto's verder weg, daarom zelfs minder) |

### Niet gedaan

- Geen WordPress, geen staging, geen push. De Elementor-JSON's zijn niet bijgewerkt.
- Niet getest op een echte iPhone of in Safari.
