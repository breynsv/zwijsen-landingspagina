# Verslag — tweede regiopagina "Lier en Antwerpen-Zuidrand" (31 oktober 2026)

## Summary

- Nieuwe pagina `5-snelsollicitatie-mobiel-film-lier-antwerpen-zuidrand.html`: een kopie van de goedgekeurde Dendermonde-pagina met alleen de regioteksten van Jana anders. Die teksten zijn de eyebrow, de H1 ("Ben je elektricien en woon je in Lier of de Antwerpse Zuidrand?"), `<title>`, meta description, "In duo" (in de lijst én in de film), het profielbolletje met de tien gemeenten, de lijn bij "Hoe ziet je dag eruit?", het postcodevoorbeeld "bv. 2640 Mortsel" en de kaart. Al de rest is identiek.
- Er staat nergens nog "Dendermonde" of "9200" in de pagina, behalve in twee HTML-commentaren die uitleggen waar ze vandaan komt.
- Gedeeld formulier (`assets/apply-form.js`): twee nieuwe opties per pagina, `placeholders` en `regio`, die standaard op de huidige Dendermonde-waarden staan. De Lier-pagina stuurt nu ook `regio_campagne: "Lier en Antwerpen-Zuidrand"` mee in de inzending, zodat de sollicitaties niet als Dendermonde binnenkomen. `film.js` en de CSS zijn niet aangeraakt, want de filmteksten staan in de pagina zelf.
- Kaart: een nieuwe inline SVG in dezelfde stijl als de Dendermonde-kaart. De tien gemeenten staan als stippen, met een zachte gele zone eromheen. Antwerpen staat klein bovenaan, ter oriëntatie. Duffel staat er als "Duffel (hoofdkantoor)", met de rood-gele markering.
- De middelpunten van de gemeenten komen van OpenStreetMap/Nominatim, de rivieren (Schelde, Nete, Kleine Nete en Grote Nete) van de echte OSM-lijnen. Ze zijn equirectangulair geprojecteerd op 12,5 px per km. Alle lat/lon-waarden staan in een HTML-commentaar. De ligging ten opzichte van elkaar is nagekeken en zelf bekeken: Duffel ligt op de Nete, de rivieren komen samen in Lier en de Schelde loopt langs Hoboken naar Antwerpen.
- Getest met Playwright op 320, 393, 768 en 1440 px, op beide pagina's. Er zijn geen consolefouten en geen 404's, en geen pagina scrolt horizontaal. Op gsm loopt de film van rand tot rand (0/0). De vragenlijst loopt telkens door tot het bedankscherm, met het juiste voorbeeld per pagina ("bv. 9200 of Dendermonde" / "bv. 2640 Mortsel"). De kaartlabels overlappen niet en blijven binnen de kaart. De kaart is even breed als de knop eronder.
- De Dendermonde-pagina is nagekeken tegen een `git archive HEAD`-kopie: de zichtbare tekst is identiek op alle vier de breedtes, met hetzelfde voorbeeld, hetzelfde bedankscherm en dezelfde `regio_campagne`. Het HTML-bestand zelf is niet gewijzigd.
- Verzonnen of niet nagekeken: niets aan de inhoud. Jana's "je rijdt…" is in het duo-punt met een hoofdletter begonnen. De regionaam volgt de opdracht ("Lier en Antwerpen-Zuidrand"), terwijl Jana zelf "Antwerpen-Zuidrand en Lier" schreef. Even nagaan bij haar welke volgorde ze wil.
- Al aanwezig en niet door deze wijziging: `film-voorbeelden.html` scrolt op 320 px 16 px horizontaal, ook in de versie van vóór deze wijziging. Niet aangepast, want het valt buiten de opdracht.

## Detail

### Tekstwijzigingen (Dendermonde → Lier en Antwerpen-Zuidrand)

| Plaats | Wordt |
| --- | --- |
| `<title>` | Vacature elektricien Lier en Antwerpen-Zuidrand – technieker algemene elektriciteit \| Electro Zwijsen |
| meta description | Ben je elektricien en woon je in Lier of de Antwerpse Zuidrand? Vast contract en dertiende maand. Solliciteren duurt 30 seconden, zonder CV. |
| eyebrow | Vacature elektricien · regio Lier en Antwerpen-Zuidrand |
| H1 | Ben je elektricien en woon je in Lier of de Antwerpse Zuidrand? (regiodeel in geel, zoals op Dendermonde) |
| In duo (lijst + film) | Je rijdt samen met een ervaren collega uit jouw regio naar de werf. |
| Profiel | Je woont in (of vlakbij) Hoboken, Wilrijk, Edegem, Mortsel, Hove, Boechout, Lint, Kontich, Duffel of Lier |
| Je dag | Je rijdt samen met je collega uit de regio naar de werf. |
| Vraag "Waar woon je?" | voorbeeld: bv. 2640 Mortsel |
| Kaart | nieuw (zie hieronder); `<title>`/`<desc>` van de SVG aangepast |

De alt-teksten bevatten geen "Dendermonde" en zijn ongewijzigd.

### Formuliermotor

`ZwijsenApply.mount('#apply', { regio: 'Lier en Antwerpen-Zuidrand', placeholders: { woonplaats: 'bv. 2640 Mortsel' } })`.
Zonder opties blijft alles zoals het was (`QUESTIONS[3].placeholder` en `regio_campagne: 'Dendermonde'`). De andere voorstellen roepen `mount` zonder deze opties aan en veranderen dus niet.

### Kaart

- Projectie: middelpunt 51.1584 N / 4.4657 O, x-schaal cos(51.16°), 12,5 px/km, viewBox 320×240 (liggend, zoals Dendermonde).
- Gemeenten (Nominatim, 31 okt 2026), lat / lon: Antwerpen 51.2211/4.3997 · Hoboken 51.1795/4.3618 · Wilrijk 51.1701/4.3943 · Edegem 51.1548/4.4458 · Mortsel 51.1704/4.4567 · Hove 51.1486/4.4774 · Boechout 51.1636/4.4964 · Lint 51.1268/4.4922 · Kontich 51.1353/4.4455 · Lier 51.1311/4.5697 · Duffel 51.0957/4.5062.
- Zone: de convexe omhullende van een cirkel van 15 px rond elke gemeente, tweemaal afgerond (Chaikin). Geel op 22 % dekking met een gele rand.
- Rivieren: OSM `waterway=river` via Overpass, vereenvoudigd met Douglas-Peucker (0,5 px). De Schelde komt van de Rupelmonding naar Antwerpen, de Nete loopt van Lier via Duffel naar Rumst, en de Kleine en Grote Nete komen in Lier samen. Labels staan bij Schelde, Nete en Kleine Nete; de Grote Nete heeft geen label.
- Klassen: `zk`, `zk-town`, `zk-river`, `zk-scale` zoals op Dendermonde. Enkel op deze pagina komen `zk-ref` (Antwerpen) en `zk-hq` (vet voor Duffel) erbij.
- Kanttekening: het middelpunt van Hoboken ligt ongeveer 2 km landinwaarts van de Schelde, omdat Nominatim het districtsmiddelpunt geeft. Op de kaart is dat correct.

### Verificatie

- Script: `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/pw/lier.js`. "Voor" is geserveerd vanuit een `git archive HEAD`-kopie, "na" vanuit de repo, beide met `python3 -m http.server`.
- Per pagina en breedte gemeten: consolefouten, 4xx/5xx, `scrollWidth` tegenover `clientWidth`, de linker- en rechterrand van de film, de breedte van de kaart tegenover de knop, de bounding boxes van alle kaartlabels (paarsgewijs en tegenover de kaartrand), de volledige vragenlijst met placeholder en `regio_campagne` uit de payload, en de tekst van het bedankscherm.
- Resultaat: alles in orde op 12 van de 12 runs. Dendermonde voor en na geeft `innerText` IDENTICAL op 320, 393, 768 en 1440.
- Screenshots: `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-lier/` (kaart, hero, volledige pagina, bedankscherm, film-voorbeelden).
- Servers en browsers gestopt; `pgrep` vindt geen http.server en geen chrome-headless-shell meer.

### film-voorbeelden.html

Bij voorstel 5 staan nu twee knoppen: "… · regio Dendermonde" en "… · regio Lier en Antwerpen-Zuidrand". Beide links geven 200.

### Open

- De volgorde van de regionaam bij Jana nagaan (zie Summary).
- URL zwijsen.be/elektricien-lier-antwerpen-zuidrand/ is een voorstel van Sven. De vraag van Jana ("Idem voor regio Antwerpen?") staat nog open.
