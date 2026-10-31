#!/usr/bin/env bash
# Zet de twee regiopagina's (opnieuw) op STAGING. Raakt alleen
# ~/subsites/staging.zwijsen.be aan, nooit de productiekopie.
#
#   elementor/bron/herplaats.sh            bouwen + plaatsen + renderen
#   elementor/bron/herplaats.sh --render   alleen opnieuw renderen
#
# Wat het doet:
#   1. bouw.py (lokale HTML -> Elementor-JSON + statische bestanden)
#   2. statische bestanden -> wp-content/uploads/zwijsen-landing/ (film-foto's, js/)
#   3. plaats.php per pagina (Document::save, teruglezen, tellen)
#   4. Yoast-titel en -beschrijving per pagina (wp post meta update)
#   5. CSS-bestand van de pagina opnieuw schrijven, LiteSpeed-cache van die pagina legen
#   6. beide pagina's op de server renderen (zwl-render.php) en ophalen naar $OUT
set -euo pipefail
export COPYFILE_DISABLE=1   # geen macOS-xattrs in de tar
HIER="$(cd "$(dirname "$0")" && pwd)"
SITE='~/subsites/staging.zwijsen.be'
OUT="${OUT:-/tmp/zwl-def/render}"
SSH=(ssh -o ControlMaster=auto -o ControlPath=/tmp/zwl-def/cm-%r@%h -o ControlPersist=900 -o LogLevel=ERROR zwijsen-combell)
mkdir -p "$OUT"
r() { "${SSH[@]}" "$@" 2> >(grep -v '^\*' >&2); }

if [[ "${1:-}" != "--render" ]]; then
  python3 "$HIER/bouw.py"
  r 'mkdir -p ~/tmp/zwl'
  tar -C "$HIER" -cf - plaats.php server/zwl-render.php server/exporteer.php \
      build/pagina-dendermonde.json build/pagina-lier.json | r 'tar -C ~/tmp/zwl -xf -'
  # Statische map: eerst naast de echte uitpakken, dan in één keer omwisselen.
  tar -C "$HIER/build/static" -cf - . | r "cd $SITE/wp-content/uploads && rm -rf zwijsen-landing.nieuw && mkdir zwijsen-landing.nieuw && tar -C zwijsen-landing.nieuw -xf - && rm -rf zwijsen-landing && mv zwijsen-landing.nieuw zwijsen-landing && find zwijsen-landing -type f | wc -l"
  for p in dendermonde lier; do
    id=$(r "cd $SITE && wp eval-file ~/tmp/zwl/plaats.php ~/tmp/zwl/build/pagina-$p.json --user=breynsv" | tee /dev/stderr | sed -n 's/^ID=//p')
    [[ -n "$id" ]] || { echo "geen id voor $p" >&2; exit 1; }
    title=$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["yoast"]["title"])' "$HIER/build/pagina-$p.json")
    desc=$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["yoast"]["metadesc"])' "$HIER/build/pagina-$p.json")
    r "cd $SITE && wp post meta update $id _yoast_wpseo_title $(printf %q "$title") && wp post meta update $id _yoast_wpseo_metadesc $(printf %q "$desc")"
    r "cd $SITE && wp eval '(new \\Elementor\\Core\\Files\\CSS\\Post($id))->update(); echo \"css ok\n\";' && wp litespeed-purge post_id $id"
    echo "$p=$id" >> "$OUT/ids.txt"
  done
fi

for slug in elektricien-dendermonde elektricien-lier-antwerpen-zuidrand; do
  r "cd $SITE && php ~/tmp/zwl/server/zwl-render.php /$slug/ > ~/tmp/zwl/$slug.html 2> ~/tmp/zwl/$slug.log; tail -3 ~/tmp/zwl/$slug.log"
  r "cat ~/tmp/zwl/$slug.html" > "$OUT/$slug.html"
  r "cat ~/tmp/zwl/$slug.log" > "$OUT/$slug.log"
  echo "$slug: $(wc -c < "$OUT/$slug.html") bytes, $(grep -c '^PHP' "$OUT/$slug.log" || true) PHP-meldingen"
  r "rm -f ~/tmp/zwl/$slug.html ~/tmp/zwl/$slug.log"   # niets achterlaten buiten de pipelinehulpen
done
