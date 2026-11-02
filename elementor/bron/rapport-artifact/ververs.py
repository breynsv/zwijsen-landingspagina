#!/usr/bin/env python3
"""Haalt de snelsollicitaties van productie en maakt er de documenten van voor
de gedeelde rapportpagina op claude.ai ("Sollicitaties Electro Zwijsen").

Geen naam, e-mail, telefoon of IP: export.php roept dezelfde functie aan als
de beheerpagina "Sollicitaties", die die velden niet eens ophaalt. Daarbovenop
wordt de woonplaats hier ingekort tot postcode + gemeente; een straat of
huisnummer gaat nooit mee.

Gebruik:  python3 ververs.py [uitmap]      (standaard: ./uit)
Daarna schrijft Claude uit/maanden/*.json en uit/stand.json met ArtifactData
naar collectie "maanden" en doc "rapport/stand" (zie LEESMIJ.md).
"""
import json, re, subprocess, sys, datetime, pathlib, collections

HIER = pathlib.Path(__file__).resolve().parent
UIT = pathlib.Path(sys.argv[1]) if len(sys.argv) > 1 else HIER / "uit"

STRAAT = re.compile(r"(straat|laan|weg|plein|lei|dreef|baan|hof|ring|kaai|markt|dijk|veld|park|steeg|pad)\b", re.I)


def kort_woonplaats(v: str) -> str:
    v = (v or "").strip()
    if not v:
        return ""
    m = re.search(r"\b([1-9]\d{3})\b\s*([A-Za-zÀ-ÿ' -]{2,40})?", v)
    if m:
        gemeente = (m.group(2) or "").strip(" -,")
        gemeente = re.split(r"[,;]", gemeente)[0].strip()
        if gemeente and STRAAT.search(gemeente):
            gemeente = ""
        return (m.group(1) + " " + gemeente.title()).strip()
    deel = re.split(r"[,;]", v)[-1].strip()
    deel = re.sub(r"\d+\w*", "", deel).strip(" -")
    if not deel or STRAAT.search(deel):
        return "(ingekort)"
    return deel[:40].title()


def main() -> None:
    php = (HIER / "export.php").read_bytes()
    subprocess.run(["ssh", "zwijsen-combell", "mkdir -p ~/tmp/zwl && cat > ~/tmp/zwl/rapport-export.php"],
                   input=php, check=True, stderr=subprocess.DEVNULL)
    uit = subprocess.run(["ssh", "zwijsen-combell", "cd ~/subsites/zwijsen.be && wp eval-file ~/tmp/zwl/rapport-export.php 2>/dev/null"],
                         capture_output=True, check=True).stdout.decode()
    rijen = json.loads(uit[uit.index("["):])  # het SSH-welkomstbord staat ervoor

    per_maand = collections.defaultdict(list)
    for r in rijen:
        r["woonplaats"] = kort_woonplaats(r.get("woonplaats", ""))
        per_maand[r["datum"][:7]].append(r)

    (UIT / "maanden").mkdir(parents=True, exist_ok=True)
    for oud in (UIT / "maanden").glob("*.json"):
        oud.unlink()
    for maand, lijst in per_maand.items():
        (UIT / "maanden" / f"{maand}.json").write_text(json.dumps({"rijen": lijst}, ensure_ascii=False))
    nu = datetime.datetime.now().astimezone().isoformat(timespec="minutes")
    (UIT / "stand.json").write_text(json.dumps({"bijgewerkt": nu, "aantal": len(rijen), "maanden": sorted(per_maand)}, ensure_ascii=False))
    print(f"{len(rijen)} rijen, maanden: {', '.join(sorted(per_maand)) or '(geen)'} -> {UIT}")


if __name__ == "__main__":
    main()
