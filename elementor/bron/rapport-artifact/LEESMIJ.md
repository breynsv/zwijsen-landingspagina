# Rapportpagina "Sollicitaties Electro Zwijsen" (claude.ai)

Pagina: https://claude.ai/artifact/J3EzNE5Qcd8C4ghmuvqP93 (eigenaar Sven, gedeeld met Jana).
Bron van de pagina: `sollicitaties.html` (bevat geen data). Data staat in de opslag van de pagina:

- `rapport/stand` — `{bijgewerkt, aantal, maanden[]}`
- `maanden/<JJJJ-MM>` — `{rijen: [...]}`, dezelfde kolommen als het WP-rapport "Sollicitaties",
  woonplaats ingekort tot postcode + gemeente. Geen naam, e-mail, telefoon, IP.

Toegang: lezen = iedereen met wie de pagina gedeeld is; schrijven = alleen de eigenaar.

## Bijwerken (op aanvraag — "ververs het sollicitatierapport van Zwijsen")

Moet vanuit een Claude Code-sessie met de ArtifactData-tool (een headless `claude -p` heeft die
tool niet; getest 2 nov 2026).

1. `python3 ververs.py` — haalt de rijen via `ssh zwijsen-combell`, schrijft `uit/stand.json` en
   `uit/maanden/*.json`. `uit/` staat in .gitignore: deze repo is publiek.
2. ArtifactData `list` op collectie `maanden` en `get` op `rapport/stand` → versies noteren.
3. ArtifactData `batch`: per maandbestand `set` op `maanden/<maand>` met `file_path`
   (`if_version` als het document al bestaat), `delete` voor maanden die niet meer in `uit/` staan,
   en `set` op `rapport/stand` met `uit/stand.json` (met `if_version`).
