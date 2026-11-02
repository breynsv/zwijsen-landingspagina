#!/usr/bin/env bash
# Zet de twee regiopagina's (opnieuw) op staging of, met --doel productie, op productie.
# Zonder --doel is het STAGING: dan raakt het alleen ~/subsites/staging.zwijsen.be aan.
#
#   elementor/bron/herplaats.sh                          staging: bouwen + plaatsen + renderen
#   elementor/bron/herplaats.sh --render                 staging: alleen opnieuw renderen
#   elementor/bron/herplaats.sh --doel productie         productie (~/subsites/zwijsen.be)
#   elementor/bron/herplaats.sh --doel productie --render
#
# Wat het doet:
#   1. bouw.py (lokale HTML -> Elementor-JSON + statische bestanden)
#      productie: ZWL_DOEL=productie -> build-productie/, met media-prod.json, zonder vaste id's
#   2. statische bestanden -> wp-content/uploads/zwijsen-landing/ (film-foto's, js/)
#      productie: NIET vervangen, alleen nagaan dat de map op de server byte-gelijk is aan de
#      build (md5 per bestand). Verschilt ze, dan stopt het script.
#   3. plaats.php per pagina (Document::save, teruglezen, tellen; op productie op slug)
#   4. Yoast-titel en -beschrijving per pagina (wp post meta update)
#   5. CSS-bestand van de pagina opnieuw schrijven, LiteSpeed-cache van die pagina legen
#   6. beide pagina's op de server renderen (zwl-render.php) en ophalen naar $OUT
set -euo pipefail
export COPYFILE_DISABLE=1   # geen macOS-xattrs in de tar
HIER="$(cd "$(dirname "$0")" && pwd)"

DOEL=staging RENDER_ONLY=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --doel) DOEL="${2:-}"; shift 2 ;;
    --render) RENDER_ONLY=1; shift ;;
    *) echo "onbekende optie: $1" >&2; exit 2 ;;
  esac
done
case "$DOEL" in
  staging)   SITE='~/subsites/staging.zwijsen.be'; HOST=staging.zwijsen.be; BUILD=build;           OUT="${OUT:-/tmp/zwl-def/render}" ;;
  productie) SITE='~/subsites/zwijsen.be';         HOST=zwijsen.be;         BUILD=build-productie; OUT="${OUT:-/tmp/zwl-5d/render}" ;;
  *) echo "--doel moet staging of productie zijn" >&2; exit 2 ;;
esac
echo "doel: $DOEL ($SITE)" >&2

mkdir -p /tmp/zwl-def "$OUT"
SSH=(ssh -o ControlMaster=auto -o ControlPath=/tmp/zwl-def/cm-%r@%h -o ControlPersist=900 -o LogLevel=ERROR zwijsen-combell)
r() { "${SSH[@]}" "$@" 2> >(grep -v '^\*' >&2); }

if [[ "$RENDER_ONLY" != 1 ]]; then
  ZWL_DOEL="$DOEL" python3 "$HIER/bouw.py"
  r 'mkdir -p ~/tmp/zwl'
  tar -C "$HIER" -cf - plaats.php server/zwl-render.php server/exporteer.php \
      "$BUILD/pagina-dendermonde.json" "$BUILD/pagina-lier.json" | r 'tar -C ~/tmp/zwl -xf -'
  if [[ "$DOEL" == staging ]]; then
    # Statische map: eerst naast de echte uitpakken, dan in één keer omwisselen.
    tar -C "$HIER/$BUILD/static" -cf - . | r "cd $SITE/wp-content/uploads && rm -rf zwijsen-landing.nieuw && mkdir zwijsen-landing.nieuw && tar -C zwijsen-landing.nieuw -xf - && rm -rf zwijsen-landing && mv zwijsen-landing.nieuw zwijsen-landing && find zwijsen-landing -type f | wc -l"
  else
    lokaal=$(cd "$HIER/$BUILD/static" && find . -type f -exec md5 -r {} + | sed 's/ /  /' | sort -k2)
    server=$(r "cd $SITE/wp-content/uploads/zwijsen-landing && find . -type f -exec md5sum {} + | sort -k2")
    if [[ "$lokaal" != "$server" ]]; then
      diff <(echo "$lokaal") <(echo "$server") >&2 || true
      echo "statische map op productie verschilt van de build: eerst gelijkzetten" >&2; exit 1
    fi
    echo "statische map op productie = build ($(echo "$lokaal" | wc -l | tr -d ' ') bestanden)" >&2
  fi
  for p in dendermonde lier; do
    id=$(r "cd $SITE && wp eval-file ~/tmp/zwl/plaats.php ~/tmp/zwl/$BUILD/pagina-$p.json --user=breynsv" | tee /dev/stderr | sed -n 's/^ID=//p')
    [[ -n "$id" ]] || { echo "geen id voor $p" >&2; exit 1; }
    title=$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["yoast"]["title"])' "$HIER/$BUILD/pagina-$p.json")
    desc=$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["yoast"]["metadesc"])' "$HIER/$BUILD/pagina-$p.json")
    r "cd $SITE && wp post meta update $id _yoast_wpseo_title $(printf %q "$title") && wp post meta update $id _yoast_wpseo_metadesc $(printf %q "$desc")"
    r "cd $SITE && wp eval '(new \\Elementor\\Core\\Files\\CSS\\Post($id))->update(); echo \"css ok\n\";' && wp litespeed-purge post_id $id"
    echo "$DOEL $p=$id" >> "$OUT/ids.txt"
  done
fi

for slug in elektricien-dendermonde elektricien-lier-antwerpen-zuidrand; do
  r "cd $SITE && ZWL_HOST=$HOST php ~/tmp/zwl/server/zwl-render.php /$slug/ > ~/tmp/zwl/$slug.html 2> ~/tmp/zwl/$slug.log; tail -3 ~/tmp/zwl/$slug.log"
  r "cat ~/tmp/zwl/$slug.html" > "$OUT/$slug.html"
  r "cat ~/tmp/zwl/$slug.log" > "$OUT/$slug.log"
  echo "$slug: $(wc -c < "$OUT/$slug.html") bytes, $(grep -c '^PHP' "$OUT/$slug.log" || true) PHP-meldingen"
  r "rm -f ~/tmp/zwl/$slug.html ~/tmp/zwl/$slug.log"   # niets achterlaten buiten de pipelinehulpen
done
