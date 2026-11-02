#!/bin/bash
# Dagelijks (07:00, via launchd be.site-specialist.zwijsen-rapport): haalt de snelsollicitaties van
# zwijsen.be en laat een Claude-achtergrondsessie ze op de rapportpagina zetten.
# Alleen collectie "maanden" en doc "rapport/stand" worden geschreven; "meta" (Jana's Meta-cijfers)
# nooit — zie ververs-prompt.md. Een headless `claude -p` kan dit niet (geen ArtifactData), daarom --bg.
set -uo pipefail
export PATH="$HOME/.local/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin"
DIR="$HOME/Sites/zwijsen-landing/elementor/bron/rapport-artifact"
echo "== $(date '+%F %T')"

# Afgeronde sessies van vorige dagen opruimen, zodat de lijst met achtergrondsessies niet volloopt.
claude agents --json --all 2>/dev/null | python3 -c '
import json, sys
for a in json.load(sys.stdin):
    if str(a.get("name", "")).startswith("zwijsen-ververs") and a.get("state") == "done":
        print(a["id"])' | while read -r id; do claude rm "$id" >/dev/null 2>&1 && echo "opgeruimd: $id"; done

cd "$DIR" && python3 ververs.py || { echo "FOUT: export van zwijsen.be mislukt"; exit 1; }

# Eerst in de eigen map; is die (nog) niet vertrouwd door Claude Code, dan in een map die het wel is.
for cwd in "$HOME/Sites/zwijsen-landing" "$HOME/Sites/BETANGOCRM/betangocrm-laravel"; do
  uit=$(cd "$cwd" && claude --bg --name "zwijsen-ververs-$(date +%Y%m%d)" \
        --allowedTools "ArtifactData" "ToolSearch" "Read" "Bash(ls:*)" \
        -- "$(cat "$DIR/ververs-prompt.md")" 2>&1)
  echo "$uit" | tail -3
  echo "$uit" | grep -q "not trusted" || exit 0
done
echo "FOUT: geen vertrouwde map om de sessie te starten"; exit 1
