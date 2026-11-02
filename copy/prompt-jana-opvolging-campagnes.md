# Prompt voor Jana — campagnes Electro Zwijsen opvolgen (Meta + sollicitaties)

Gebruik: open de pagina "Sollicitaties Electro Zwijsen" op claude.ai, kies periode en regio,
klik **Kopieer voor Claude**, start een nieuw gesprek in Claude (met de Facebook-connector aan),
plak de prompt hieronder en plak de gekopieerde gegevens op de aangeduide plek.

---

Je helpt me de wervingscampagnes van Electro Zwijsen op Meta op te volgen. Electro Zwijsen zoekt
techniekers/elektriciens via twee landingspagina's op zwijsen.be: regio Dendermonde en regio Lier
en Antwerpen-Zuidrand. Een sollicitatie is een ingevuld snelsollicitatieformulier op die pagina's.

**Twee bronnen, die je niet door elkaar haalt:**
1. **Meta** (via de Facebook-connector): uitgaven, bereik, vertoningen, klikken, linkklikken,
   landingspaginaweergaven, en eventuele Lead-conversies die de pixel rapporteert.
2. **Echte sollicitaties** (hieronder geplakt, uit het rapport van zwijsen.be): die tellen.
   Meta's eigen Lead-cijfer is een schatting (consent, modellering) en kan afwijken. Toon beide
   naast elkaar, maar reken kosten per sollicitatie altijd op de echte sollicitaties.

**Koppelen:** de kolom `utm_content` in de sollicitaties is de advertentie, `utm_campaign` de
campagne. Zoek in Meta de advertenties en campagnes van Electro Zwijsen in dezelfde periode en
koppel op naam. Lukt een koppeling niet zeker, zeg dat en toon hem apart; gok niet.
Sollicitaties met utm_content "(geen)" kwamen niet via een advertentielink (rechtstreeks, Google,
organisch delen) of via een advertentie zonder URL-parameters.

**Wat ik wil zien, kort en in het Nederlands:**
1. Een tabel per campagne en per advertentie: uitgaven, vertoningen, CPM, linkklikken, CTR,
   landingspaginaweergaven, echte sollicitaties, kost per sollicitatie, en conversie
   landingspaginaweergave → sollicitatie. Plus Meta's eigen Lead-aantal ter vergelijking.
2. De evolutie per week (zelfde kolommen in het kort): wat gaat beter, wat slechter?
3. Per regio apart: Dendermonde en Lier/Antwerpen-Zuidrand.
4. De kwaliteit van de sollicitanten per advertentie: werkervaring (vooral 3 jaar of meer),
   rijbewijs, talen. Een goedkope advertentie met alleen sollicitanten zonder ervaring is geen winnaar.
5. Maximaal drie concrete aanbevelingen (budget verschuiven, advertentie pauzeren, nieuwe variant
   testen), elk met de cijfers die ze onderbouwen. Zeg het als de aantallen te klein zijn om
   conclusies te trekken (bv. minder dan 5 sollicitaties per advertentie).

**Regels:**
- Wijzig niets in Meta (geen budget, status of advertenties) zonder dat ik het eerst uitdrukkelijk vraag.
- Verzin geen cijfers. Ontbreekt iets, zeg welk gegeven en waar het vandaan zou moeten komen.
- Vermeld bovenaan de periode van beide bronnen en wanneer de sollicitatiegegevens bijgewerkt zijn
  (staat in de geplakte tekst). Valt de periode van de sollicitaties niet samen met de periode die
  je in Meta opvraagt, gebruik dan dezelfde datums.
- Controleer of de advertenties URL-parameters hebben. Ontbreken ze, meld dat als eerste
  aanbeveling. De aanbevolen instelling in Meta (veld "URL-parameters" van de advertentie) is:
  `utm_source=facebook&utm_medium=paid_social&utm_campaign={{campaign.name}}&utm_content={{ad.name}}`

**Sollicitatiegegevens (geplakt uit de pagina, knop "Kopieer voor Claude"):**

[PLAK HIER]
