# Interne links naar de regiopagina's (staging, 31 okt 2026)

## Summary

- **Blogpost 85** `/elektricien-worden/`: één alinea toegevoegd aan het einde van de tekst. Er zijn twee links: "Dendermonde" → `/elektricien-dendermonde/` en "Lier en de Antwerpse Zuidrand" → `/elektricien-lier-antwerpen-zuidrand/`. De post is klassiek (de Classic Editor is actief, er is geen Elementor-data). Alles vóór de nieuwe alinea is byte-identiek.
- **Regiopagina's 3292 en 3350**: elk één zin boven het blok "Liever gewoon bellen?", dus vlak onder de vragen. De zin verwijst naar de andere regio. De wijziging is eerst in de lokale HTML gemaakt en daarna met `herplaats.sh` opnieuw geplaatst. Formulier-endpoint, alle element-id's en de JSON-LD van de vragen zijn ongewijzigd (commit 4ba107c).
- **Categorie "Algemene elektriciteitswerken"** (16): de zin staat aan het einde van de **termbeschrijving**. Het archief gebruikt sjabloon 747 ("category archive", ook voor andere categorieën). Dat sjabloon toont de beschrijving via de dynamische tag `archive-description`. Het gedeelde sjabloon is dus niet aangeraakt. In deze categorie zitten 37 realisaties, dus "zulke projecten" klopt.
- **Bijwerking:** Yoast neemt de termbeschrijving over als `og:description`. De zin staat daar nu ook in, en Yoast verwijdert er de links uit. Daarnaast veranderen `dateModified` en het aantal woorden in de Yoast-schema van post 85 en van de twee pagina's.
- **Incident, hersteld:** `wp_update_term` haalde via kses de twee `<h2>`-koppen uit de beschrijving. Ik heb dit meteen hersteld met een directe DB-update: het origineel uit de backup plus de zin. Gecontroleerd met md5: het origineel is weer byte-identiek. De twee koppen staan weer op de pagina.
- **Op productie herhalen:** (1) post 85: dezelfde alinea vóór `<!-- /wp:paragraph -->`. (2) Term 16: dezelfde zin achteraan de beschrijving, **niet via `wp term update` of `wp_update_term`** (kses), maar via een DB-update of in WP-admin als beheerder. (3) De regiopagina's gaan mee via de pijplijn.
- **Terugdraaien:** de originelen staan in `~/data/verhuis-backups/staging-interne-links-origineel-20261031-2302/` (post 85, term 16, `_elementor_data` en instellingen van 3292 en 3350). Volledige DB: `staging-voor-interne-links-20261031-2300.sql`. Post 85 heeft ook een WordPress-revisie (3373).
- **Niet geverifieerd:** een echte browser achter het staging-wachtwoord en een echte HTTP-200 via de webserver. Wel geverifieerd: de doel-URL's renderen met status 200 via PHP-CLI. Ook niet geverifieerd: de pagina openen in de Elementor-editor.

## Detail

### Wat er gebeurd is, in volgorde
1. Backup van de DB met `wp db export`: `staging-voor-interne-links-20261031-2300.sql` (36 MB, "Dump completed"). Daarna de originelen van alles wat ik zou aanraken naar bestanden geschreven. Daarvan staat ook een kopie in `/tmp/zwl-il/orig/`.
2. Gecontroleerd dat de vorige build exact gelijk was aan wat er op staging stond. Dat klopte voor de `_elementor_data` van 3292 en 3350, inclusief de id's.
3. Lokale HTML aangepast: een `<p class="m-regio-link">` in de bel-sectie, met CSS `.m-regio-link { margin: 0 0 1.35rem; color: grey-700 }`.
   - De zin staat niet in het blok `BEGIN faq … END faq`. `faq_jsonld.py` herschrijft dat blok en zou de zin dan wissen.
   - `faq_jsonld.py --check` slaagt op beide pagina's.
4. `bouw.py` aangepast:
   - `WP_LINKS` koppelt elk draftbestand aan de WordPress-slug, afgeleid van `PAGINAS`.
   - `wp_links()` zet de href om. Een onbekende `.html`-link laat de build mislukken.
   - De widget krijgt een vast id (sha1 van de slug) en de stand van de rng wordt teruggezet. Zo houden alle bestaande elementen hun id.
   - In de eerste plaatsing schoven de id's wel op. Daardoor hoorde de "voor"-render niet meer bij de nieuwe CSS: de belknop werd rood. Ik heb dit opgelost en opnieuw geplaatst.
   - `pagina-5.css` kreeg dezelfde regel als de lokale HTML.
5. `herplaats.sh`: build, plaatsen, CSS opnieuw aangemaakt, LiteSpeed-cache van de pagina geleegd.
6. Post 85 via `wp_update_post`:
   - de alinea staat tussen `aan de slag.</p>` en `<!-- /wp:paragraph -->`, met CRLF zoals de rest;
   - teruggelezen inhoud = verwacht (1524 → 1750 bytes).
7. Term 16: zie het incident in de Summary. De eindwaarde heeft md5 `6cc78460…`, 1506 bytes, en het originele begin is ongewijzigd.
   - Ik heb ook `wp litespeed-purge all` gedraaid. Dat is ruimer dan nodig, maar onschuldig op staging.

### Verificatie
- **Server-side render** (`zwl-render.php`) van de 4 URL's vóór en na: allemaal STATUS 200 en 0 PHP-meldingen. De doel-URL's van alle links (`/elektricien-dendermonde/`, `/elektricien-lier-antwerpen-zuidrand/`) renderen met 200.
- **Zichtbare tekst** (bs4, regel per regel): op elke URL is het enige verschil de nieuwe zin.
- **HTML-diff** (volgorde-ongevoelig, zonder `?ver=`): op de regiopagina's alleen
  - de nieuwe widget;
  - `article:modified_time`, `dateModified`;
  - op Lier ook "4 → 5 minuten" leestijd.

  Formulier: `data-za-endpoint="/wp-json/zwijsen/v1/sollicitatie"` met `dendermonde` en `lier`, ongewijzigd.
- **Schermafdrukken op 393 en 1440** (headless Chromium, assets via ssh) staan in `/Users/svenbreynaert/.claude/jobs/48920f76/tmp/shots-links/v2/`:
  - `voor-*`, `na-*`, en `zin-*` (uitsnede rond de zin).
  - Boven de zin pixel-identiek. Alleen op Dendermonde wijkt de film af, en die is geanimeerd.
  - Onder de zin hetzelfde, verschoven met de hoogte van de zin (38–84 px). Wat nog verschilt is subpixel-antialiasing; naast elkaar is het visueel identiek.
  - Geen JS-fouten en geen zijwaarts scrollen.
  - Stijl: dezelfde letter, grootte en kleur als de alinea erboven. De links zijn rood, zoals de bestaande links van de site.
- **Lokale drafts** onder een GitHub-Pages-pad (`/zwijsen-landingspagina/…`, op 393 en 1440): de link klikt door naar het andere bestand met 200. Beide richtingen zijn OK. De schermafdrukken heten `draft-*.png`. Server en browsers zijn gestopt.

### Opmerkingen
- Tussen mijn vóór- en na-render is de Elementor-CSS van de hele site opnieuw aangemaakt, rond 22:05 UTC. Om 22:04 heeft iemand anders de Home-pagina (8) bewaard. Daarna zijn `post-1028.css` en `post-1462.css` (loops) niet meer geladen op deze pagina's. Die loops staan niet op deze pagina's, dus visueel verandert er niets. De oorzaak ligt niet bij deze wijziging.
- Niet aangeraakt: 1641, 1145, vacatures, `.htaccess`, sjabloon 747, productie.
