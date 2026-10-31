#!/usr/bin/env python3
"""Zet het blok "Veelgestelde vragen" en de JSON-LD (JobPosting + FAQPage) in
de twee goedgekeurde regiopagina's. De lokale HTML blijft de bron; bouw.py
leest beide daarna uit die HTML.

Elk antwoord komt letterlijk uit tekst die al op de pagina staat (zie BRON per
vraag). De FAQPage-JSON-LD wordt uit dezelfde lijst gemaakt als de zichtbare
vragen, dus ze zijn per definitie gelijk. De JobPosting-beschrijving wordt uit
de zichtbare tekst van de pagina zelf gehaald (bs4).

    python3 elementor/bron/faq_jsonld.py           # schrijft beide HTML-bestanden
    python3 elementor/bron/faq_jsonld.py --check   # controleert alleen

Idempotent: een tweede keer draaien vervangt de blokken tussen de markeringen.
"""
import json
import os
import re
import sys

from bs4 import BeautifulSoup

HIER = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HIER))
DATUM = '2026-10-31'
LOGO = 'https://zwijsen.be/wp-content/uploads/2023/09/cropped-cropped-FRONNT-Combinatielogo-Elektro-Zwijsen-Kleur.png'

PAGINAS = {
    '5-snelsollicitatie-mobiel-film.html': {
        'duo': 'Je rijdt samen met een ervaren collega van Dendermonde naar de werf.',
        'plaats': {'addressLocality': 'Dendermonde', 'postalCode': '9200', 'addressRegion': 'Oost-Vlaanderen',
                   'addressCountry': 'BE'},
    },
    '5-snelsollicitatie-mobiel-film-lier-antwerpen-zuidrand.html': {
        'duo': 'Je rijdt samen met een ervaren collega uit jouw regio naar de werf.',
        'plaats': {'addressLocality': 'Lier', 'postalCode': '2500', 'addressRegion': 'Antwerpen',
                   'addressCountry': 'BE'},
    },
}


def vragen(p):
    """(vraag, antwoord, bron op de pagina). Antwoorden = paginatekst, niets nieuws."""
    return [
        ('Heb ik een cv nodig?',
         'Nee, geen CV nodig. Beantwoord 4 korte vragen.',
         'chip "Geen CV nodig", knop "Solliciteer direct zonder CV", "Beantwoord 4 korte vragen."'),
        ('Hoe lang duurt solliciteren?',
         'Ongeveer 30 seconden. Beantwoord 4 korte vragen.',
         '"Solliciteer in 30 seconden", "4 vragen · ±30 seconden"'),
        ('Hoeveel ervaring vraagt Electro Zwijsen?',
         'Minstens 3 jaar ervaring als elektricien. Ervaring op werven is mooi meegenomen.',
         'Jouw profiel, punt 1 en 6'),
        ('Moet ik een VCA-attest hebben?',
         'Nee. In het bezit zijn van een VCA-attest is een plus. Heb je het niet, dan voorzien wij een opleiding.',
         'Jouw profiel, punt 5'),
        ('Welke talen moet ik spreken?',
         'Taal is geen barrière. Je begrijpt en spreekt voldoende Nederlands, Frans of Engels om veilig samen te werken.',
         'Jouw profiel, punt 4'),
        ('Welk contract krijg ik?',
         'Een vast contract. Vanaf dag één ben je aan boord bij ons familiebedrijf met 150 collega’s.',
         'De job, "Vast contract"'),
        ('Hoe kom ik op de werf?',
         p['duo'] + ' Voor de verplaatsing krijg je een kilometervergoeding als chauffeur of bijrijder.',
         'De job, "In duo" + Je werkweek, "Kilometervergoeding"'),
        ('Waar liggen de werven?',
         'De werven zijn in heel België, met focus op Vlaanderen. Soms werk je dicht bij huis, soms ligt een project verder weg.',
         'Je werkweek, "Werven in heel België"'),
    ]


def faq_html(items):
    rij = '\n'.join(
        '        <details><summary>%s</summary><p>%s</p></details>' % (q, a) for q, a, _ in items)
    return ('  <!-- BEGIN faq (elementor/bron/faq_jsonld.py) — GEO/SEO, 31 okt 2026. Elk antwoord is tekst\n'
            '       die al op deze pagina staat; de FAQPage-JSON-LD in de head is uit dezelfde lijst gemaakt. -->\n'
            '  <section class="m-sec m-sec--grey m-faqsec" style="padding-bottom:0;">\n'
            '    <div class="wrap">\n'
            '      <span class="zw-eyebrow">Vragen</span>\n'
            '      <h2>Veelgestelde vragen</h2>\n'
            '      <div class="m-faq" style="margin-top:1.35rem;">\n'
            + rij + '\n'
            '      </div>\n'
            '    </div>\n'
            '  </section>\n'
            '  <!-- END faq -->\n')


def tekst(el):
    return re.sub(r'\s+', ' ', el.get_text(' ', strip=True)).replace(' ,', ',').replace(' .', '.').replace(' ?', '?').replace(' !', '!')


def beschrijving(soup):
    """JobPosting.description uit de zichtbare paginatekst (HTML, zoals Google toelaat)."""
    def sectie(eyebrow):
        for s in soup.select('main section'):
            e = s.select_one('.zw-eyebrow')
            if e and tekst(e) == eyebrow:
                return s
        raise KeyError(eyebrow)

    def lijst(ul):
        return '<ul>' + ''.join('<li>%s</li>' % tekst(li) for li in ul.find_all('li', recursive=False)) + '</ul>'

    hero = soup.select_one('.m-hero')
    h1, hp = tekst(hero.h1), tekst(hero.p)
    job = sectie('De job')
    punten = ''.join('<li>%s: %s</li>' % (tekst(li.strong), tekst(li).replace(tekst(li.strong), '', 1).strip())
                     for li in job.select('#punten > li'))
    taken = sectie('Je taken')
    profiel = sectie('Jouw profiel')
    aanbod = sectie('Ons aanbod')
    dag = sectie('Je werkweek')
    stappen = ''.join('<li>%s</li>' % tekst(li.span) for li in dag.select('.m-steps > li'))
    over = sectie('Over ons')
    return (
        '<p><strong>%s</strong></p><p>%s</p>' % (h1, hp) +
        '<p><strong>Wat maakt deze job de moeite waard?</strong></p><ul>%s</ul>' % punten +
        '<p><strong>Wat ga je doen?</strong></p><p>%s</p>%s' % (tekst(taken.p), lijst(taken.select_one('ul.zw-checks'))) +
        '<p><strong>Jouw profiel</strong></p>%s' % lijst(profiel.select_one('ul.zw-checks')) +
        '<p><strong>Wat zijn jouw voordelen?</strong></p>%s' % lijst(aanbod.select_one('ul.zw-checks')) +
        '<p><strong>Je werkweek</strong></p><ul>%s</ul>' % stappen +
        '<p><strong>%s</strong></p><p>%s</p>' % (tekst(over.h2), tekst(over.select_one('p')))
    )


def jsonld(soup, p, items):
    job = {
        '@context': 'https://schema.org',
        '@type': 'JobPosting',
        'title': 'Elektricien – technieker algemene elektriciteit',
        'description': beschrijving(soup),
        'datePosted': DATUM,
        # validThrough bewust niet gezet: open vraag voor de klant (zie elementor/VERSLAG-staging-definitief.md).
        'employmentType': 'FULL_TIME',
        'directApply': True,
        'hiringOrganization': {'@type': 'Organization', 'name': 'Electro Zwijsen NV',
                               'sameAs': 'https://zwijsen.be', 'logo': LOGO},
        'jobLocation': {'@type': 'Place', 'address': dict({'@type': 'PostalAddress'}, **p['plaats'])},
    }
    faq = {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        'mainEntity': [{'@type': 'Question', 'name': q,
                        'acceptedAnswer': {'@type': 'Answer', 'text': a}} for q, a, _ in items],
    }
    dump = lambda o: json.dumps(o, ensure_ascii=False, indent=1).replace('</', '<\\/')
    return ('<!-- BEGIN jsonld (elementor/bron/faq_jsonld.py): JobPosting zonder loon (keuze van de klant) en\n'
            '     zonder validThrough (open vraag); FAQPage = de zichtbare vragen onderaan. -->\n'
            '<script type="application/ld+json">\n' + dump(job) + '\n</script>\n'
            '<script type="application/ld+json">\n' + dump(faq) + '\n</script>\n'
            '<!-- END jsonld -->\n')


def vervang(html, begin, end, nieuw, anker):
    pat = re.compile(r'[ \t]*<!-- BEGIN %s.*?<!-- END %s -->\n' % (begin, end), re.S)
    if pat.search(html):
        return pat.sub(lambda m: nieuw, html)
    assert anker in html, anker
    return html.replace(anker, nieuw + anker, 1)


def main(check=False):
    for naam, p in PAGINAS.items():
        pad = os.path.join(ROOT, naam)
        html = open(pad, encoding='utf-8').read()
        items = vragen(p)
        # Bronzin moet echt op de pagina staan (zonder het FAQ-blok zelf meegeteld).
        zonder = re.sub(r'<!-- BEGIN faq.*?<!-- END faq -->', '', html, flags=re.S)
        body = tekst(BeautifulSoup(zonder, 'html.parser').body)
        for q, a, _ in items:
            for zin in re.split(r'(?<=[.:]) ', a):
                kern = re.sub(r'^(Nee,? ?|Ongeveer |Een )', '', zin).strip(' .:')
                if kern and kern.lower() not in body.lower():
                    raise SystemExit('%s: "%s" staat niet op de pagina' % (naam, kern))
        html = vervang(html, 'faq', 'faq', faq_html(items),
                       '  <!-- ====================== BELLEN ====================== -->')
        soup = BeautifulSoup(re.sub(r'<!-- BEGIN faq.*?<!-- END faq -->', '', html, flags=re.S), 'html.parser')
        html = vervang(html, 'jsonld', 'jsonld', jsonld(soup, p, items), '\n<link rel="preconnect"'.lstrip('\n'))
        if check:
            cur = open(pad, encoding='utf-8').read()
            print(naam, 'OK' if cur == html else 'VERSCHILT')
        else:
            open(pad, 'w', encoding='utf-8').write(html)
            print(naam, len(items), 'vragen')


if __name__ == '__main__':
    main('--check' in sys.argv)
