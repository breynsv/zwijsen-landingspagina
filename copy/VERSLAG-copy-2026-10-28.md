# Verslag — copy van Jana verwerkt in de vijf landingspagina's (28 okt 2026)

## Summary

- **Alle vijf de voorstellen hebben nu Jana's tekst** en houden hun eigen opbouw. Elk voorstel heeft dezelfde hero, de zes troeven, de voordelen, de zes taken, het profiel, "Wij zijn Electro Zwijsen", de werkweek en de drie getuigenissen. Alleen in lokale bestanden; niets op WordPress of staging.
- **Formulier (gedeeld door alle vijf):** Jana's 4 vragen ("andere taal" met een tekstvak, woonplaats verplicht). Daarna "Je komt in aanmerking!" met naam, telefoon, e-mail, een toestemmingsvakje dat standaard aan staat, en de knop "Neem contact met mij op". Het CV-veld is weg. De privacylink https://zwijsen.be/privacy-policy/ werkt (200); de oude link /privacy-verklaring/ gaf een 404.
- **Kleefbalk bovenaan:** voorstel 2 op elk scherm (opent de pop-up, vervangt de onderbalk). Voorstellen 1, 3 en 4 alleen op desktop, waar hij naar het formulier springt. Voorstel 5 heeft er geen: daar staat de knop onderaan voor de duim.
- **Geschrapt:** veelgestelde vragen (1 en 5), "Hoe het verder gaat" en "wél/niet" (2), het "eerlijk"-blok (3), "Na je sollicitatie" (5), PC 149.01, "VCA betalen wij", "eerste ervaring is een plus", "werven dicht bij huis / 's avonds thuis", de gemeentelijst, "binnen 1 werkdag" op de pagina's, en de kloktijden 7u30–16u30.
- **Correcties in de copy:** "Rijbwijs" → "Rijbewijs". "Opgericht in 1987 … al veertig jaar" (in 2026 is dat 39 jaar) → "Sinds 1987 zijn we expert in elektriciteit".
- **Ook opgelost:** op een telefoon raakte de hero-tekst de schermrand in 1–4 (een fout die er al was). De tabel in 4 scrolde zijwaarts op een telefoon en stapelt nu.
- **Open vragen voor de klant:** (1) Het toestemmingsvakje staat vooraf aangevinkt. Onder de AVG telt dat niet als toestemming, dus bespreken vóór livegang. (2) Het 2e citaat staat ook op naam van "Werfleider Steven, 22 jaar"; de juiste naam is nodig. (3) De opdracht sprak van 15 voordelen, maar Jana's tekst heeft er 11 (het aantal opsommingstekens klopt daarmee). Ontbreken er 4? (4) Het bedankscherm belooft nog "telefoontje binnen 1 werkdag" en "langskomen in Duffel". Klopt dat? (5) Iedereen krijgt "Je komt in aanmerking!", ook bij "geen werkervaring" of "geen rijbewijs".
- **Niet gebouwd:** de fotoband, "de selectie van de nieuwe werven" en "Regio Dendermonde in beeld" uit Jana's opmaak. Daar zijn geen foto's of werflijst voor; de bestaande afbeeldingen blijven.
- **Niet geverifieerd:** of de fotolijst van Jana uit de mediabibliotheek overeenkomt met wat de drafts gebruiken (de PDF kon hier niet als beeld geopend worden).

## Detail

### Per voorstel
1. **Vacature+**: hero met 4 feiten (contract, loon, werkweek, in duo naar de werf) en het formulier ernaast. Daaronder 6 troeven met iconen, 11 voordelen in twee kolommen, taken + "Ben jij dit?", werkweek + "Wij zijn", getuigenissen en een slotoproep.
2. **Kwalificatietest**: startkaart met de 4 vragen. Alle knoppen openen de pop-up. 6 troeven genummerd, voordelen, taken/profiel, werkweek met foto, "Wij zijn" + getuigenissen, rode slotstrook. Kleefbalk bovenaan; de onderbalk is weg.
3. **Dag op de werf**: tijdlijn met etiketten in plaats van uren (Vertrek, De werf, Het werk, Onderweg, Ma–vr), letterlijk naar Jana's tekst. De 6 taken staan als labels bij "Het werk". De voordelen zijn in drie kolommen gegroepeerd; de tussentitels zijn van ons.
4. **Wat je krijgt**: het pakket in het kort (8 regels), tabel 1 met alle 11 voordelen, tabel 2 over de job en het profiel, de 6 troeven, "Wij zijn" + getuigenissen, en het formulier met het profiel ernaast.
5. **Snelsollicitatie**: één kolom met het formulier bovenaan, alle blokken van Jana kort onder elkaar, de werkweek in 4 stappen.

### Formulier (`assets/apply-form.js`)
- Er zijn nu 5 stappen (4 vragen + contactstap). De geruststelling luidt "4 vragen · ±30 seconden".
- In de inzending staan: `werkervaring`, `moedertaal` (+ `moedertaal_andere`), `rijbewijs`, `woonplaats`, `naam`, `landcode`, `telefoon`, `email`. Velden die nu wegvallen: `voornaam`/`achternaam`/`cv`. Een toekomstige backend-koppeling moet die nieuwe veldnamen verwachten.
- Naar Meta en dataLayer gaan nog altijd alleen stapnummer en vraagnaam. Vrije tekst (taal, woonplaats) gaat er nooit naartoe.
- De nieuwe functie `ZwijsenApply.stickyBar()` plaatst de balk onder de kleverige hoofding. Hij verdwijnt zolang het formulier in beeld is.

### Verificatie
Gedraaid met headless Chromium (Playwright) op 1440 en 390 px, voor alle 5 voorstellen en index.html: 0 console- of paginafouten en geen horizontaal scrollen.

De volledige flow is telkens doorlopen:
- "Een andere taal" kiezen toont het tekstvak, blijft op de vraag staan en geeft een fout als het leeg is.
- "Terug" bewaart de ingevulde taal.
- Een lege woonplaats geeft een fout.
- Het contactscherm toont "Je komt in aanmerking!", het vinkje staat aan en de privacylink is juist.
- Versturen zonder gegevens geeft een fout; met gegevens verschijnt "Bedankt, Jan!" en het Lead-event.

Voorstel 2: de balk is verborgen bovenaan, staat na het scrollen net onder de hoofding en opent de pop-up. Voorstellen 1, 3 en 4: de balk verschijnt op desktop, verdwijnt bij het formulier en is afwezig op mobiel.

Grep op "8 vragen", "acht vragen", "60 sec", "eerste ervaring", "7u30", "PC 149" en "betalen wij" in de vijf pagina's: niets gevonden. "geen must" komt alleen voor in Jana's zin.

Al bestaand, niet aangepast: in de pop-up overlapt het sluitkruisje op 390 px het label "Stap 5 van 5". Foutmeldingen van velden verdwijnen pas bij de volgende verzendpoging.

### README
README.md heeft een notitie gekregen ("Copy van Jana, 28 okt 2026, verwerkt") en een nieuwe beschrijving van het formulier. Het bestand staat wel in `.gitignore`, dus het zit niet in de commit; de wijziging staat alleen lokaal.
