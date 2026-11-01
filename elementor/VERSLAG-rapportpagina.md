# Pagina "Sollicitaties" met CSV-download, zonder persoonsgegevens (staging, 1 nov 2026)

## Summary

- **Wat er gebouwd is.** In WP-admin staat nu een menu "Sollicitaties". Het toont de snelsollicitaties, de nieuwste eerst, met filters op periode en regio, telt ze per regio en per advertentie (utm_content), en heeft een knop "Download CSV" voor de filter die op dat moment aan staat. De code zit in de bestaande mu-plugin `zwijsen-snelsollicitatie.php`, die nu versie 1.1.0 is. Op staging staat ze, op productie nog niet.
- **Wat je ziet:** datum en uur (Belgische tijd), regio, utm_source / utm_medium / utm_campaign / utm_content / utm_term, werkervaring, talen, rijbewijs en de woonplaats of postcode zoals de sollicitant die intypte. **Nooit getoond of geëxporteerd:** naam, e-mail, telefoon, IP, browser, en alle andere parameters uit de URL (fbclid, gclid …). De code haalt die gegevens zelfs niet op uit de databank.
- **Wie erin kan.** Een nieuwe rol "Rapportage sollicitaties" (`zwijsen_rapport`) heeft alleen `read` plus `zwijsen_bekijk_sollicitaties`; de beheerders hebben die tweede ook. Wie alleen die rol heeft, komt na het inloggen meteen op de pagina en ziet in het menu alleen "Sollicitaties" en "Profiel" (daar kan je je wachtwoord wijzigen). De adminbalk is leeg op het account na. Elk ander beheerscherm stuurt terug naar de pagina. Applicatiewachtwoorden zijn voor deze rol uitgeschakeld. Er is geen REST-route voor deze gegevens; de CSV loopt via admin-post, met nonce en capability-controle.
- **Getest op staging**, met een tijdelijke gebruiker voor elke rol. Een echte HTTP-login kon niet, want het staging-wachtwoord (htpasswd) houdt alles tegen. Daarom liep elk verzoek als apart PHP-proces door de echte WordPress-ingang (`wp-login.php`, `wp-admin/admin.php`, `admin-post.php`) met echte inlogcookies. LiteSpeed, de Wordfence-WAF en het verzoekfilter kwamen daarbij **niet** aan bod.
  - Inloggen met het wachtwoord, met Hack Guard en Wordfence actief, kwam uit op de pagina Sollicitaties.
  - In de pagina en de CSV zaten 0 persoonsgegevens.
  - De CSV is ingelezen en klopt: BOM, UTF-8, 12 kolommen. Een waarde die met `=` begint, wordt voorafgegaan door een `'`, zodat Excel er geen formule van maakt.
  - De filters werken, ook rond middernacht in Belgische tijd.
  - 18 andere beheerschermen sturen terug naar de pagina: berichten, pagina's, Elementor, instellingen, gebruikers, plugins, Hack Guard en andere.
  - Een abonnee en iemand die niet ingelogd is, krijgen een 403 of de loginpagina.
- **Hack Guard schakelde de testgebruiker niet uit** (de rol is geen beheerder), en het inloggen werkte. Hack Guard heeft geen loginbescherming die gewone gebruikers raakt. Wordfence biedt deze rol geen tweestapsverificatie aan.
- **Het formulier zelf werkt zoals voorheen.** Opnieuw getest via `rest_do_request`: geldig, honeypot en ongeldig gaven dezelfde antwoorden als voorheen. Opgeruimd: de testinzendingen 3377–3379 en de testgebruikers 22 en 23 zijn gewist. Rij **3376** (31-10, 22:45) is niet van mij en staat er nog; vermoedelijk is dat Sven's eigen browsertest.
- **Naar productie:** (1) een DB-back-up maken; (2) `elementor/server/zwijsen-snelsollicitatie.php` naar `~/subsites/zwijsen.be/wp-content/mu-plugins/` kopiëren; (3) als beheerder één keer `/wp-admin/admin.php?page=zwijsen-sollicitaties` openen, wat de rol aanmaakt; (4) **na goedkeuring van de klant** Jana's account aanmaken: `wp user create <login> <mail> --role=zwijsen_rapport` en daarna een wachtwoord-resetlink sturen. Zet haar **niet** op de uitzonderingslijst van Hack Guard; dat is niet nodig.
- **Open vragen:**
  - Is "woonplaats" persoonsgegeven genoeg om Jana het te laten zien? Een postcode is grof, maar sommigen typen een volledig adres in.
  - Moet Jana tweestapsverificatie krijgen? Dan moet de rol in Wordfence Login Security toegelaten worden; de pagina laat het 2FA-scherm al door.
  - Wil HR een id-kolom om rijen terug te vinden in Elementor → Inzendingen? Die zit er nu bewust niet in.

## Detail

### Wat er op staging gebeurd is

1. **Back-ups** (in `~/data/verhuis-backups/`):
   - `staging-voor-rapportpagina-20261101-1814.sql` (35,5 MB, "Dump completed")
   - `staging-zwijsen-snelsollicitatie-voor-rapportpagina-20261101-1814.php` (md5 `6817573a…`, versie 1.0.0)
2. **mu-plugin vervangen** door versie 1.1.0. Wat er nu op staging staat, is byte-gelijk aan de repokopie (md5 `8b79a094…`).
3. **Niet aangeraakt:** Hack Guard, Wordfence, het verzoekfilter, `.htaccess`, htpasswd, de andere mu-plugins en productie.

### Ontwerp

- **Rol.** Op `init` (prioriteit 5), idempotent: bestaat de rol niet, dan wordt ze aangemaakt. Bestaat ze wel, dan krijgt ze precies `read` + `zwijsen_bekijk_sollicitaties`; elke extra capability gaat eraf. Een beheerder krijgt de capability erbij als hij ze nog niet heeft. Er wordt alleen geschreven als er iets ontbreekt.
- **"Alleen rapport"** betekent dat de rollen van de gebruiker precies `[zwijsen_rapport]` zijn. Iemand die daarnaast ook editor of beheerder is, ziet het gewone beheer, met de pagina erbij.
- **Afscherming:**
  - `login_redirect` stuurt na het inloggen naar de pagina.
  - `admin_init` (prioriteit 1) laat alleen deze schermen door: `profile.php`, `admin.php?page=zwijsen-sollicitaties`, `page=WFLS` (de 2FA van Wordfence) en `admin-post.php?action=zwijsen_sollicitaties_csv`. Ajax gaat door; elk ajaxscherm controleert zelf zijn capabilities.
  - `admin_page_access_denied` stuurt door naar de pagina. Dat is nodig omdat WordPress schermen met een menu-item al in `menu.php` met een 403 stopt, nog vóór `admin_init`. Een eerste testronde liet daar nog een 403 zien in plaats van de doorverwijzing.
  - Het menu wordt opgekuist tot Sollicitaties en Profiel.
  - In de adminbalk blijven alleen het account en uitloggen over.
  - Aan de voorkant is er voor deze rol geen adminbalk.
  - `admin_notices` worden voor deze rol verwijderd.
  - `wp_is_application_passwords_available_for_user` geeft false.
- **Gegevens.**
  - Gelezen worden alleen `e_submissions` met `element_id = 'zwsnelsol'`, `form_name LIKE 'Snelsollicitatie%'` en een status die niet `trash` is.
  - Uit die tabel worden alleen `id`, `form_name` en `created_at_gmt` geselecteerd.
  - Uit `e_submissions_values` alleen de sleutels werkervaring, talen, rijbewijs, woonplaats, regio en bron.
- **Bron → UTM.** `bron` wordt bewaard als de urldecoded querystring met een `?` ervoor, of als "(geen)". Hij wordt gesplitst op `&` en daarna op het eerste `=`. Alleen de vijf `utm_*`-sleutels blijven over (de eerste waarde telt), opgekuist en ingekort tot 150 tekens. Een waarde met een `&` erin wordt afgekapt; dat is een gevolg van de urldecode bij het opslaan.
- **Tijd.** De WordPress-tijdzone van staging staat op UTC (`gmt_offset 0`, geen `timezone_string`). Daarom rekent het rapport zelf met `Europe/Brussels`, vanuit `created_at_gmt`. "Van" en "tot en met" zijn hele Belgische dagen. Een omgekeerde periode wordt rechtgezet, en een onbekende regio wordt genegeerd.
- **Tabel.** utm_medium en utm_term verschijnen alleen als minstens één rij ze heeft. Er staan maximaal 1000 rijen op het scherm, met een melding als het er meer zijn. De CSV heeft alle rijen en altijd alle 12 kolommen, zodat Jana's bestanden altijd dezelfde vorm hebben.
- **CSV.**
  - Formaat: BOM, `,` als scheidingsteken, `"` rond velden waar dat nodig is, en Nederlandse koppen. De koppen zijn: Datum, Uur, Regio, Bron (utm_source), Medium (utm_medium), Campagne (utm_campaign), Advertentie (utm_content), Term (utm_term), Werkervaring, Talen, Rijbewijs, Woonplaats of postcode. Datums staan als JJJJ-MM-DD, net als in een Meta-export.
  - Een cel die begint met `= + - @`, tab of CR krijgt een `'` ervoor, omdat de UTM-waarden van buiten komen.
  - Bestandsnaam: `sollicitaties-<van|begin>-tot-<tot|vandaag>[-<regio>].csv`.
  - Headers: `nocache`, `nosniff` en `noindex`.
  - Wie niet ingelogd is, krijgt via `admin_post_nopriv_…` uitdrukkelijk een 403.

### Testresultaten (staging, 1 nov 2026)

**Testgegevens.** Drie inzendingen via `rest_do_request`, met een TEST-NET-IP en naam en e-mail als herkenbare merkers:

- Dendermonde, met utm_source, utm_medium, utm_campaign en utm_content, plus `fbclid` en `gclid`;
- Lier, met utm_term en een utm_content `=HYPERLINK("…")`;
- Lier zonder bron. Daarvan is het tijdstip daarna op 31-10 23:30 UTC gezet, dat is 1-11 00:30 in Belgische tijd.

Daarnaast stond rij 3376 er al.

| Endpointtest | Uitkomst |
|---|---|
| 3× geldig | 200 `{ok:true}`, 1 rij, 2 mails (tegengehouden door de mailblokkade van staging) |
| honeypot | 200 `{ok:true}`, niets bewaard, 0 mails |
| antwoord "99" + nummer "1234" | 422, dezelfde tekst als in VERSLAG-formulier.md |
| ongeldig e-mailadres | 422 |

| Toegang (testgebruiker `zwijsen_rapport`, id 22) | Uitkomst |
|---|---|
| `wp-login.php` POST met het juiste wachtwoord | 302 → `admin.php?page=zwijsen-sollicitaties` |
| `wp-login.php` POST met een verkeerd wachtwoord | gewone foutmelding van WordPress |
| pagina, zonder filter / Lier / van 01-11 / tot 31-10 | 4 / 2 / 3 / 1 rijen, zoals verwacht (de rij van 00:30 valt in november) |
| PII-merkers in de pagina's en de CSV's (naam, e-mail, telefoon, IP, UA, fbclid, gclid) | 0 treffers |
| menu / adminbalk | Sollicitaties, Profiel / alleen account en uitloggen |
| CSV, alles / Lier + 01-11 | 5 / 3 regels (kop meegeteld), elk 12 velden; `'=HYPERLINK(…)` |
| CSV zonder nonce of met een valse nonce | 403 |
| index, berichten, pagina's, nieuw bericht, pagina bewerken, Elementor-editor, Elementor-menu, Elementor-inzendingen, Elementor-bibliotheek, instellingen, gebruikers, gebruiker 1 bewerken, plugins, extra, Hack Guard, media, reacties, een andere admin-post-actie | alle 302 → de pagina |
| profiel | rendert, met een knop om het wachtwoord te wijzigen, zonder applicatiewachtwoorden |
| WFLS (2FA) | 302 → de pagina, omdat Wordfence deze rol geen 2FA aanbiedt |
| REST: `elementor/v1/form-submissions`, `/…/3377`, `wp/v2/users`, `wp/v2/pages/3292?context=edit` | alle 403 |
| Hack Guard `zhg_user_is_disabled(22)` | false, voor en na het inloggen |

| Andere gebruikers | Uitkomst |
|---|---|
| abonnee (id 23): pagina / CSV | 403 "Je hebt geen toestemming" / 403 |
| niet ingelogd: pagina / CSV | 302 naar wp-login / 403 |

**Hoe dit getest is, en wat niet.** `bron/server/rapporttest/harness.php` laadt per geval in een apart PHP-proces de echte ingang van WordPress. Zo'n proces krijgt `$_SERVER`, `$_GET`, `$_POST` en echte auth-cookies mee; de cookies maakt `cookies.php` met een echte sessietoken. Daarna vangt het `wp_redirect`, de statuscode en de body op. Hack Guard (`determine_current_user`, `authenticate`) en Wordfence liepen dus gewoon mee. De webserver, LiteSpeed, de Wordfence-WAF (auto_prepend), het verzoekfilter en htpasswd niet. De verkeerde login staat als één mislukte poging voor 192.0.2.77 in de teller van Wordfence; dat is onschuldig.

### Opgeruimd

- **Inzendingen 3377, 3378, 3379:** gewist met `Query::delete_submission()`, na controle van de user-agent `zwijsen-rapporttest`. Daarna 0 rijen boven 3376 in de drie tabellen; de hoogste id is 3376.
- **Gebruikers 22 `zwl-rapport-test` en 23 `zwl-abonnee-test`:** verwijderd met `wp user delete`. Hun sessietokens gingen mee.
- **Bestanden en tellers:** `zwsnel_*`-transients: 0. `~/tmp/zwl/rapport/` met de cookies, het wachtwoordbestand en de opgevangen pagina's is van de server verwijderd. De rol `zwijsen_rapport` blijft bestaan; die hoort erbij.

### Bestanden

- `elementor/server/zwijsen-snelsollicitatie.php`: versie 1.1.0. De oude code is ongewijzigd; alleen de versie en de beschrijving in de kop zijn aangepast. Nieuwe inzendingen krijgen daardoor `"bron":"zwijsen-snelsollicitatie 1.1.0"` in hun meta.
- `elementor/bron/server/rapporttest/`: de testscripts. Ze staan niet op de server.
