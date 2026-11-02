Ververs de sollicitatiecijfers op de rapportpagina van Electro Zwijsen. Dit is een geplande, onbewaakte taak: stel geen vragen, doe alleen wat hieronder staat, en stop bij een fout.

Pagina: https://claude.ai/artifact/J3EzNE5Qcd8C4ghmuvqP93
Map: ~/Sites/zwijsen-landing/elementor/bron/rapport-artifact

HARDE REGEL: je schrijft ALLEEN naar collectie `maanden` en naar document `rapport/stand`. Collectie `meta` (Meta-cijfers van Jana) lees, wijzig of verwijder je NOOIT, ook niet met een batch. Je publiceert de pagina niet opnieuw.

1. De bestanden staan al klaar in `uit/` (ververs.py is net gedraaid door het plannerscript). Lees `uit/stand.json` en de namen van de bestanden in `uit/maanden/`.
2. ArtifactData `list` op collectie `maanden` (limit 1000) en `get` op collectie `rapport`, doc `stand`. Noteer de `version` van elk bestaand document.
3. Eén ArtifactData `batch` met:
   - per bestand `uit/maanden/<maand>.json`: `set` op collectie `maanden`, doc `<maand>`, `file_path` = het absolute pad, en `if_version` als dat document al bestaat;
   - `delete` (met `if_version`) voor elk document in `maanden` waarvoor geen bestand meer bestaat;
   - `set` op collectie `rapport`, doc `stand`, `file_path` = absoluut pad van `uit/stand.json`, met `if_version` als het bestaat.
   Een batch heeft maximaal 50 writes; zijn het er meer, splits dan in delen van 50 met `rapport/stand` in de laatste.
4. Weigert de batch door een gewijzigde versie: lees opnieuw en probeer één keer opnieuw. Lukt het dan nog niet, stop.
5. Als laatste regel: `KLAAR <aantal> rijen` of `FOUT <reden>`.
