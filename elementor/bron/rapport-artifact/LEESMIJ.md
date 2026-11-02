# Rapportpagina "Sollicitaties Electro Zwijsen" (claude.ai)

Pagina: https://claude.ai/artifact/J3EzNE5Qcd8C4ghmuvqP93 (eigenaar Sven, gedeeld met Jana).
Bron van de pagina: `sollicitaties.html` (bevat geen data). Data staat in de opslag van de pagina:

- `rapport/stand` — `{bijgewerkt, aantal, maanden[]}`
- `maanden/<JJJJ-MM>` — `{rijen: [...]}`, dezelfde kolommen als het WP-rapport "Sollicitaties",
  woonplaats ingekort tot postcode + gemeente. Geen naam, e-mail, telefoon, IP.

Toegang: lezen = iedereen met wie de pagina gedeeld is; schrijven = alleen de eigenaar.

## Bijwerken — automatisch elke dag om 07:00 (sinds 2 nov 2026)

LaunchAgent `be.site-specialist.zwijsen-rapport` (bron: `be.site-specialist.zwijsen-rapport.plist`
hier, geïnstalleerd in ~/Library/LaunchAgents) draait `ververs-dagelijks.sh`: export via SSH, daarna
een `claude --bg`-sessie met `ververs-prompt.md` die ALLEEN `maanden/*` en `rapport/stand` schrijft —
nooit `meta`. Log: ~/Library/Logs/zwijsen-rapport.log. Sliep de Mac om 07:00, dan draait hij bij
ontwaken; stond hij uit, dan valt die dag weg. Getest 2 nov 2026 via `launchctl kickstart`.
Nu draaien: `launchctl kickstart gui/$(id -u)/be.site-specialist.zwijsen-rapport`.
Weghalen: `launchctl bootout gui/$(id -u)/be.site-specialist.zwijsen-rapport`.

## Bijwerken met de hand (dezelfde stappen)

Moet vanuit een Claude Code-sessie met de ArtifactData-tool: een interactieve of `--bg`-sessie.
Een headless `claude -p` heeft die tool niet (getest 2 nov 2026).

1. `python3 ververs.py` — haalt de rijen via `ssh zwijsen-combell`, schrijft `uit/stand.json` en
   `uit/maanden/*.json`. `uit/` staat in .gitignore: deze repo is publiek.
2. ArtifactData `list` op collectie `maanden` en `get` op `rapport/stand` → versies noteren.
3. ArtifactData `batch`: per maandbestand `set` op `maanden/<maand>` met `file_path`
   (`if_version` als het document al bestaat), `delete` voor maanden die niet meer in `uit/` staan,
   en `set` op `rapport/stand` met `uit/stand.json` (met `if_version`).

## Meta-cijfers (sinds 2 nov 2026, versie 4)

Jana's Claude haalt Meta-cijfers op met háár Facebook-connector (Sven heeft geen toegang tot het
Zwijsen-advertentieaccount) en schrijft ze in collectie `meta`, doc `<van>_<tot>`. Vorm en werkwijze
staan zichtbaar op de pagina, sectie "Voor Claude". Regels: `meta` schrijfbaar vanaf Editor (admin),
alles anders alleen eigenaar. Gecontroleerd met as_level: admin mag `meta` schrijven en `maanden`
niet; interact mag niets. Jana moet dus als **Editor** per e-mail uitgenodigd zijn, en de pagina
mag NIET ook via een openbare link gedeeld worden (dan verliest een externe Editor zijn schrijfrecht).
Lukt het haar Claude niet om in de database te schrijven, dan geeft hij een JSON-blok dat zij plakt
bij "Meta-cijfers plakken".
