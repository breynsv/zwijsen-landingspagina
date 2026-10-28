#!/usr/bin/env python3
"""Bouwt de landingspagina's als Elementor-data (containers + native widgets).

Sinds 28 okt 2026 (v2) staan alleen voorstel 2 en 5 nog op staging; 1, 3 en 4
zijn weggehaald (zie VERSLAG-staging-v2.md, de vorige versie zit in git).
Inhoud = de lokale HTML-drafts 2-kwalificatietest.html en
5-snelsollicitatie-mobiel.html (copy en foto's van Jana).

Uitvoer: elementor/bron/build/pagina-N.json met titel, slug, elements en
page settings. bron/plaats.php zet ze op staging; het eindresultaat wordt
daarna als importeerbare Elementor-sjabloon in elementor/ bewaard.

    python3 elementor/bron/bouw.py
"""
import json
import os
import random
import re

HIER = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HIER))
BUILD = os.path.join(HIER, 'build')

# Foto's van Jana, in de mediabibliotheek van staging gezet met `wp media import`
# (id + url + Nederlandse alt-tekst). Zie media.json.
MEDIA = json.load(open(os.path.join(HIER, 'media.json'), encoding='utf-8'))

G = lambda c: 'globals/colors?id=' + c
INK, RED, YELLOW, GREY, WHITE = 'primary', 'accent', '78f9a77', '9b6502c', '2c5975e'

_rng = random.Random()


def _id():
    return format(_rng.getrandbits(28), '07x')


# ---------------------------------------------------------------- elementen

def C(cls='', *kids, id=None, boxed=False, **extra):
    s = {'content_width': 'boxed' if boxed else 'full'}
    if cls:
        s['css_classes'] = cls
    if id:
        s['_element_id'] = id
    s.update(extra)
    return {'id': _id(), 'elType': 'container', 'settings': s, 'elements': [k for k in kids if k], 'isInner': False}


def SEC(cls, *kids, id=None, **extra):
    """Sectie over de volle breedte: boxed container met de klasse zwl."""
    return C('zwl zw-sec ' + cls, *kids, id=id, boxed=True, **extra)


def W(kind, settings):
    return {'id': _id(), 'elType': 'widget', 'settings': settings, 'elements': [], 'widgetType': kind}


def H(text, tag='h2', cls='', color=None):
    s = {'title': text, 'header_size': tag}
    if cls:
        s['_css_classes'] = cls
    if color:
        s['__globals__'] = {'title_color': G(color)}
    return W('heading', s)


def EYE(text, color=RED, cls=''):
    return H(text, 'div', ('zw-eyebrow ' + cls).strip(), color)


def T(html, cls=''):
    s = {'editor': html.strip()}
    if cls:
        s['_css_classes'] = cls
    return W('text-editor', s)


def UL(items, cls='zw-checks'):
    return T('<ul>' + ''.join('<li>%s</li>' % i for i in items) + '</ul>', cls)


VARIANTS = {
    # achtergrond, tekst, hover-achtergrond, hover-tekst
    'red': ({'background_color': G(RED), 'button_text_color': G(WHITE)}, '#B60A0E', '#FFFFFF'),
    'yellow': ({'background_color': G(YELLOW), 'button_text_color': G(INK)}, '#E0AC00', '#121413'),
    'light': ({'background_color': G(WHITE), 'button_text_color': G(INK)}, '#EFEDEE', '#121413'),
}


def B(text, href, variant='red', cls=''):
    s = {'text': text, 'link': {'url': href, 'is_external': '', 'nofollow': '', 'custom_attributes': ''}}
    g, hb, ht = VARIANTS[variant]
    s['__globals__'] = dict(g)
    s['button_background_hover_color'] = hb
    s['hover_color'] = ht
    if cls:
        s['_css_classes'] = cls
    return W('button', s)


def FOTO(naam):
    m = MEDIA[naam]
    return {'id': m['id'], 'url': m['url']}


def IMG(naam, cls='', size='large', caption=None):
    """Native afbeelding uit de mediabibliotheek. De alt-tekst staat op de bijlage zelf."""
    m = MEDIA[naam]
    s = {'image': {'id': m['id'], 'url': m['url'], 'alt': m['alt'], 'source': 'library'},
         'image_size': size, 'align': 'left'}
    if caption:
        s['caption_source'] = 'custom'
        s['caption'] = caption
    s['_css_classes'] = ('zp-pic ' + cls).strip()
    return W('image', s)


def IB(icon, title, desc, cls):
    return W('icon-box', {
        'selected_icon': {'value': icon, 'library': 'fa-solid'},
        'title_text': title, 'description_text': desc, 'title_size': 'h3',
        '_css_classes': cls,
    })


def QUOTE(tekst, naam, functie, foto, cls):
    """Getuigenis als native Elementor-widget (Testimonial): tekst, portret, naam, functie."""
    m = MEDIA[foto]
    return W('testimonial', {
        'testimonial_content': tekst,
        'testimonial_image': {'id': m['id'], 'url': m['url'], 'alt': m['alt'], 'source': 'library'},
        'testimonial_image_size': 'medium',
        'testimonial_name': naam, 'testimonial_job': functie,
        'testimonial_image_position': 'aside', 'testimonial_alignment': 'left',
        '_css_classes': 'zw-quote ' + cls,
    })


def HTML(html, cls=''):
    s = {'html': html.strip()}
    if cls:
        s['_css_classes'] = cls
    return W('html', s)


def BG(foto, overlay, pos='center center', xy=None):
    """Achtergrondfoto met een kleurlaag erover, als native containerinstelling."""
    s = {'background_background': 'classic', 'background_image': dict(foto, source='library', alt=''),
         'background_position': pos, 'background_repeat': 'no-repeat', 'background_size': 'cover',
         'background_color': '#121413',
         'background_overlay_opacity': {'unit': 'px', 'size': 1, 'sizes': []}}
    if xy:
        s['background_position'] = 'initial'
        s['background_xpos'] = {'unit': '%', 'size': xy[0], 'sizes': []}
        s['background_ypos'] = {'unit': '%', 'size': xy[1], 'sizes': []}
    s.update(overlay)
    return s


def MOBILEBAR(text, href):
    return C('zwl zw-mobilebar', B(text, href, cls='zw-btn--block'))


# ---------------------------------------------------------------- gedeelde copy (Jana, 28 okt 2026)

MOEITE = [
    ('fas fa-building', 'Grote werven', 'Werk aan ziekenhuizen, scholen, kantoorgebouwen, winkelcentra,…'),
    ('fas fa-bolt', 'Breed takenpakket', 'Bouw mee aan elektrische installaties van a tot z.'),
    ('fas fa-sync-alt', 'Afwisseling', 'Geregeld nieuwe projecten, vooral in Vlaanderen.'),
    ('fas fa-compass', 'Vrijheid in je vak', 'Je werkt zelfstandig en organiseert je werk zelf. Het resultaat telt.'),
    ('fas fa-users', 'In duo', 'Je rijdt samen met een ervaren collega van Dendermonde naar de werf.'),
    ('fas fa-shield-alt', 'Vast contract', 'Vanaf dag één ben je aan boord bij ons familiebedrijf met 150 collega’s.'),
]
VOORDELEN = [
    'Goed loon, volgens ervaring', '32 vakantiedagen', 'Hospitalisatieverzekering voor jou én je gezin',
    'Extra terugbetaling van doktersbezoeken, medicatie en andere medische kosten', 'Telefoon met abonnement',
    'Groepsverzekering', 'Maaltijdcheques 9 euro per gewerkte dag', 'Dertiende maand',
    'Kilometervergoeding tot de werf', 'Werkkledij', 'Beschermingsmateriaal',
]
TAKEN_INTRO = '<p>Je bouwt nieuwe elektrische installaties van a tot z op grote werven, samen met je collega’s.</p>'
TAKEN = ['Kabelgoten plaatsen', 'Kabels trekken', 'Schakelmateriaal plaatsen', 'Verlichtingstoestellen aansluiten',
         'Aansluitingen maken in verdeelborden', 'Zorgen dat het volledige project goed wordt opgeleverd']
PROFIEL = ['Minstens 3 jaar ervaring als elektricien', 'Je woont in (of vlakbij) Dendermonde',
           'Je hebt minstens rijbewijs B', 'Je spreekt Nederlands', 'Je hebt een VCA-attest',
           'Ervaring op werven is een plus, geen must']
OVER_ONS = ('<p>Wij zijn een hecht familiebedrijf van 150 collega’s. Het hoofdkantoor is in Duffel en we hebben '
            'ook een filiaal in Zonhoven. Sinds 1987 zijn we expert in elektriciteit. Naast algemene '
            'elektriciteitswerken bouwden we doorheen de jaren expertise op in midden- en hoogspanning, '
            'datanetwerken, elektrische laadinstallaties en beveiliging, van branddetectie tot inbraakbeveiliging. '
            'Kansen genoeg voor wie wil doorgroeien. Wij zijn een werkgever die expertise naar waarde schat.</p>')
SWIPE = '<p>Veeg opzij voor meer foto’s →</p>'


def getuigenissen():
    return C('zw-quotes',
        QUOTE('“Ik hou van de afwisseling in mijn job. Geen twee werven zijn hetzelfde. Zelfs na al die jaren kom '
              'ik nog geregeld iets tegen waarvan ik denk: dat wist ik nog niet.”',
              'Steven', 'Werfleider, 22 jaar bij Electro Zwijsen', 'steven-werfleider', 'zp-steven'),
        QUOTE('“Ik weet wat ik aan Zwijsen heb: een correcte werkgever die altijd stipt betaalt en toffe collega’s '
              'met wie ik graag samenwerk. Dat is belangrijk, want je brengt veel tijd met elkaar door. Doorheen de '
              'jaren heb ik ook veel opleidingen kunnen volgen: voor de hoogtewerker, schaarlift, heftruck en VCA.”',
              'Kurt', 'Werfleider, 30 jaar bij Electro Zwijsen', 'kurt-werfleider', 'zp-kurt'))


def regio(lijstcls):
    """'Ben jij dit?' met de kaart 'Regio Dendermonde in beeld' ernaast (of erboven in een smalle kolom)."""
    return C('zp-region-c',
        C('zp-region',
          UL(PROFIEL, lijstcls),
          IMG('kaart-regio-dendermonde', 'zp-map', 'medium_large', caption='Regio Dendermonde in beeld')))


# ---------------------------------------------------------------- formulier

def formulier_js():
    js = open(os.path.join(ROOT, 'assets', 'apply-form.js'), encoding='utf-8').read()
    start = js.index('  /* Meta Pixel / GA4')
    end = js.index('  /* ----------------------------------------------------------------------\n     3. De motor')
    stub = ("  /* Meting staat UIT op staging: er is nog geen echte pixel-ID en geen\n"
            "     toestemmingslaag. Zie README, hoofdstuk \"Meta Pixel en events\". */\n"
            "  function track() {}\n  function trackStandard() {}\n\n")
    js = js[:start] + stub + js[end:]
    # De kleefbalk legt zich onder de sitehoofding. In de draft is dat .zw-header;
    # op WordPress is het de kleverige sectie van het Elementor-hoofdingsjabloon.
    oud = "var header = document.querySelector('.zw-header');"
    assert oud in js, 'kleefbalk: hoofdingselector niet gevonden'
    js = js.replace(oud, "var header = document.querySelector('.zw-header')"
                         " || document.querySelector('.elementor-location-header .elementor-sticky--active:not(.elementor-sticky__spacer)')"
                         " || document.querySelector('.elementor-location-header');")
    assert 'fbq' not in js and 'gtag' not in js and 'dataLayer' not in js, 'meetcode niet volledig verwijderd'
    assert '</script' not in js
    return js


def formulier(mount):
    """HTML-widget: het stapsgewijze formulier uit de draft, ongewijzigd behalve de meting."""
    if mount == 'modal':
        init = ("var m = ZwijsenApply.mountModal();\n"
                "  /* Elke Elementor-knop met link #check opent het venster */\n"
                "  document.addEventListener('click', function (e) {\n"
                "    var a = e.target.closest('a[href$=\"#check\"]');\n"
                "    if (a) { e.preventDefault(); m.open(a); }\n"
                "  });\n"
                "  /* Kleefbalk bovenaan (Jana: \"bij scrollen bovenaan vast\"), opent het venster */\n"
                "  ZwijsenApply.stickyBar({ after: '.hero2', modal: true });")
        host = ''
    else:
        init = "ZwijsenApply.mount('#zw-apply');"
        host = '<div id="zw-apply"></div>\n'
    return host + '<script>\n' + formulier_js() + '\n(function () {\n  ' + init + '\n})();\n</script>'


# ---------------------------------------------------------------- css

def prefix_css(css, prefix='selector '):
    """Zet `selector ` voor elke selector van een stylesheet (ook binnen @media/@container)."""
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    out, i, n = [], 0, len(css)

    def block(start):
        depth, j = 0, start
        while j < n:
            if css[j] == '{':
                depth += 1
            elif css[j] == '}':
                depth -= 1
                if depth == 0:
                    return j
            j += 1
        raise ValueError('ongebalanceerde accolades')

    while i < n:
        b = css.find('{', i)
        if b == -1:
            break
        head = css[i:b].strip()
        end = block(b)
        body = css[b + 1:end]
        if head.startswith('@keyframes'):
            out.append(head + '{' + body + '}')
        elif head.startswith('@'):
            out.append(head + '{' + prefix_css(body, prefix) + '}')
        else:
            sels = [s.strip() for s in head.split(',')]
            out.append(', '.join(prefix + s for s in sels) + ' {' + body.strip() + '}')
        i = end + 1
    return '\n'.join(out)


def lees(naam):
    return open(os.path.join(HIER, naam), encoding='utf-8').read()


def pagina_css(n, form=True):
    parts = [lees('basis.css'), lees('fotos.css'), lees('pagina-%d.css' % n)]
    if form:
        parts.append(prefix_css(lees('formulier-extra.css')))
        parts.append(prefix_css(open(os.path.join(ROOT, 'assets', 'apply-form.css'), encoding='utf-8').read()))
    css = '\n\n'.join(parts)
    # het woord dat Elementor vervangt, mag alleen als voorvoegsel voorkomen
    assert 'selectors' not in css
    return css


# ---------------------------------------------------------------- pagina's

def page(n, title, slug, elements):
    return {'title': title, 'slug': slug, 'elements': elements,
            'settings': {'hide_title': 'yes', 'template': 'default', 'custom_css': pagina_css(n)}}


def pagina_2():
    """Voorstel 2 — Kwalificatietest: elke knop opent het formulier in een venster."""
    knop = lambda: B('Solliciteer direct zonder CV', '#check', cls='zw-after')

    hero = SEC('hero2',
        C('hero2__inner',
          C('zw-rise',
            EYE('Vacature elektricien · regio Dendermonde'),
            H('Ben je <em>elektricien</em> en woon je in (de buurt van) Dendermonde?', 'h1', color=INK),
            T('<p>Ontdek of jij in aanmerking komt voor een job bij Electro Zwijsen!</p>', 'hero2__sub'),
            B('Doe de test', '#check', cls='zw-btn--lg hero2__btn'),
            UL(['4 vragen · ±30 seconden', 'Geen CV nodig', 'Volledig vertrouwelijk'], 'zw-trust')),
          C('starter zw-rise zw-rise-2',
            IMG('technieker-bij-bestelwagen', 'zp-hero-photo', 'large'),
            H('Kom je in aanmerking?', 'h2', 'starter-h', INK),
            T('<p>Vooraf weten wat komt, scheelt een drempel. Dit is de volledige lijst:</p>', 'starter-p'),
            T('<p>De vier vragen</p><ol>' + ''.join('<li>%s</li>' % q for q in [
                'Hoeveel relevante werkervaring heb je?', 'Wat is je moedertaal?', 'Heb je een rijbewijs?',
                'Waar woon je?']) + '</ol>', 'starter__preview'),
            T('<p>Ongeveer 30 sec</p>', 'meter'),
            B('Begin met vraag 1', '#check', cls='zw-btn--block'))))

    moeite = SEC('zw-section',
        C('zw-intro', EYE('De job'), H('Wat maakt deze job de moeite waard?', 'h2', color=INK)),
        C('how', *[C('', H(t, 'h3', 'how-h', INK), T('<p>%s</p>' % d, 'how-p')) for _, t, d in MOEITE]),
        knop())

    band = ['collega-lacht-op-de-werf', 'ploeg-bij-de-bestelwagen', 'technieker-opent-bestelwagen',
            'hoofdkantoor-luchtfoto', 'plannen-bekijken', 'magazijn-materiaal-klaarzetten', 'magazijn-overleg',
            'kabelhaspel-buiten', 'kabelhaspels-magazijn', 'selfie-helm-en-baksteen', 'verdeelbord-bekabelen',
            'plannen-tekenen-werkplaats', 'selfie-in-de-werkplaats']
    voordelen = SEC('zw-section zw-section--grey',
        C('zw-intro', EYE('Ons aanbod'), H('Wat zijn jouw voordelen?', 'h2', color=INK)),
        UL(VOORDELEN, 'zw-checks zw-checks--2col'),
        C('zp-band-wrap',
          C('zp-band', *[IMG(f, '', 'medium_large') for f in band]),
          T(SWIPE, 'zp-swipe-hint')),
        knop())

    werven = ['werf-brussel-hoogbouw-kraan', 'werf-glazen-dak-toren', 'werf-ronde-hal-bestelwagens',
              'werf-klassieke-gevel', 'werf-gang-paarse-verlichting']
    taken = SEC('zw-section',
        C('benefits',
          C('', H('Wat ga je doen?', 'h3', 'benefits-h', INK), T(TAKEN_INTRO, 'benefits-intro'), UL(TAKEN)),
          C('', H('Ben jij dit?', 'h3', 'benefits-h', INK), regio('zw-checks'))),
        C('zp-werven-wrap',
          H('De selectie van de nieuwe werven', 'h3', 'zp-head', INK),
          C('zp-werven', *[IMG(f, '', 'large') for f in werven])),
        knop())

    dag = SEC('zw-section zw-section--grey',
        C('region',
          C('',
            EYE('Je werkweek'),
            H('Hoe ziet je dag eruit?', 'h2', color=INK),
            UL(['Je rijdt samen met je collega uit de regio Dendermonde naar de werf.',
                'De werven zijn in heel België, met focus op Vlaanderen.',
                'Soms werk je dicht bij huis, soms ligt een project verder weg.',
                'Voor de verplaatsing krijg je een kilometervergoeding als chauffeur of bijrijder.',
                'Je werkt van maandag tot vrijdag, 40 uur per week.',
                'Overuren of zaterdagwerk zijn uitzonderlijk.'], 'zw-checks dag-list')),
          C('', IMG('selfie-in-de-bestelwagen', 'zp-day', 'large'))))

    over = SEC('zw-section',
        C('zw-about', EYE('Over ons'), H('Wij zijn Electro Zwijsen', 'h2', color=INK), T(OVER_ONS)),
        C('zw-intro', H('Waarom kies je voor Electro Zwijsen?', 'h2', color=INK)),
        getuigenissen())

    slot = SEC('zw-section zw-slot2',
        IMG('ploeg-electro-zwijsen-luchtfoto', 'zp-group', '1536x1536'),
        C('cta-strip',
          C('', H('Kom je in aanmerking? Doe de test.', 'h3', 'cta-strip-h', WHITE),
            T('<p>Vier korte vragen, ongeveer 30 seconden. Solliciteer direct zonder CV.</p>', 'cta-strip-p')),
          B('Solliciteer in 30 seconden', '#check', 'light', 'zw-btn--lg')),
        T('<p>Liever gewoon bellen? <a href="tel:+3215305305">015 305 305</a> — vraag naar de personeelsdienst.</p>',
          'call-note'))

    host = C('zwl zw-formhost', HTML(formulier('modal'), 'zw-form'))
    return page(2, 'Vacature technieker — voorstel 2 (Kwalificatietest)', 'vacature-technieker-2',
                [hero, moeite, voordelen, taken, dag, over, slot, host])


def pagina_5():
    """Voorstel 5 — Snelsollicitatie: één kolom, formulier bovenaan, knop onderaan voor de duim."""
    def M(cls, *kids, **kw):
        return SEC('m-col ' + cls, *kids, **kw)

    def cta(text, note=None):
        return C('m-cta', B(text, '#solliciteer', cls='zw-btn--block'),
                 T('<p>%s</p>' % note, 'm-cta-note') if note else None)

    hero = M('m-hero',
        EYE('Vacature elektricien · regio Dendermonde', YELLOW),
        H('Ben je elektricien en woon je in (de buurt van) <span>Dendermonde?</span>', 'h1', 'zw-rise', WHITE),
        T('<p>Ontdek of jij in aanmerking komt voor een job bij Electro Zwijsen!</p>', 'm-hero-p zw-rise zw-rise-2'),
        UL(['Vast contract', '32 vakantiedagen', 'Dertiende maand', 'Geen CV nodig'], 'chips'),
        **BG(FOTO('technieker-bij-bestelwagen'),
             {'background_overlay_background': 'classic', 'background_overlay_color': 'rgba(18,20,19,0.66)'},
             xy=(72, 30)))

    form = M('m-formsec',
        C('m-form',
          H('Solliciteer in 30 seconden', 'h2', 'm-form-h', INK),
          T('<p>Kom je in aanmerking? Doe de test. Vier korte vragen, geen CV.</p>', 'm-form-p'),
          HTML(formulier('inline'), 'zw-form'),
          id='solliciteer', _attributes='data-za-scroll|1'))

    job = M('m-sec',
        EYE('De job'),
        H('Wat maakt deze job de moeite waard?', 'h2', color=INK),
        C('m-list', *[IB(i, t, d, 'm-item') for i, t, d in MOEITE]),
        cta('Solliciteer direct zonder CV', '4 vragen · ±30 seconden'))

    band = ['collega-lacht-op-de-werf', 'ploeg-bij-de-bestelwagen', 'magazijn-overleg', 'kabelhaspel-buiten',
            'selfie-helm-en-baksteen', 'verdeelbord-bekabelen', 'plannen-tekenen-werkplaats',
            'selfie-in-de-bestelwagen']
    voordelen = M('m-sec m-sec--grey',
        EYE('Ons aanbod'),
        H('Wat zijn jouw voordelen?', 'h2', color=INK),
        UL(VOORDELEN, 'zw-checks m-gap m-15'),
        C('zp-band-wrap',
          C('zp-band zp-band--strip', *[IMG(f, '', 'medium_large') for f in band]),
          T(SWIPE, 'zp-swipe-hint')),
        cta('Solliciteer direct zonder CV'))

    taken = M('m-sec',
        EYE('Je taken'),
        H('Wat ga je doen?', 'h2', color=INK),
        T(TAKEN_INTRO, 'm-intro'),
        UL(TAKEN, 'zw-checks m-15'),
        C('zp-werven-wrap',
          H('De selectie van de nieuwe werven', 'h3', 'zp-head', INK),
          C('zp-werven zp-werven--3', *[IMG(f, '', 'large') for f in
                                        ['werf-brussel-hoogbouw-kraan', 'werf-klassieke-gevel',
                                         'werf-ronde-hal-bestelwagens']])),
        cta('Solliciteer direct zonder CV'))

    profiel = M('m-sec m-sec--grey',
        EYE('Jouw profiel'),
        H('Ben jij dit?', 'h2', color=INK),
        regio('zw-checks m-gap m-15'),
        cta('Kom je in aanmerking? Doe de test.', 'Solliciteer direct zonder CV'))

    dag = M('m-sec',
        EYE('Je werkweek'),
        H('Hoe ziet je dag eruit?', 'h2', color=INK),
        T('<ol>' + ''.join('<li><strong>%s</strong><span>%s</span></li>' % s for s in [
            ('Samen naar de werf', 'Je rijdt samen met je collega uit de regio Dendermonde naar de werf.'),
            ('Werven in heel België', 'De werven zijn in heel België, met focus op Vlaanderen. Soms werk je dicht '
                                      'bij huis, soms ligt een project verder weg.'),
            ('Kilometervergoeding', 'Voor de verplaatsing krijg je een kilometervergoeding als chauffeur of bijrijder.'),
            ('Maandag tot vrijdag, 40 uur', 'Je werkt van maandag tot vrijdag, 40 uur per week. Overuren of '
                                            'zaterdagwerk zijn uitzonderlijk.')]) + '</ol>', 'm-steps'))

    over = M('m-sec m-sec--grey',
        C('zw-about', EYE('Over ons'), H('Wij zijn Electro Zwijsen', 'h2', color=INK), T(OVER_ONS)))

    getuigen = M('m-sec',
        EYE('Collega’s aan het woord'),
        H('Waarom kies je voor Electro Zwijsen?', 'h2', 'm-quotes-h', INK),
        getuigenissen(),
        IMG('ploeg-electro-zwijsen-luchtfoto', 'zp-group m-group', '1536x1536'),
        cta('Solliciteer in 30 seconden'))

    bellen = M('m-sec m-sec--grey',
        C('m-call',
          H('Liever gewoon bellen?', 'h3', 'm-call-h', WHITE),
          T('<p>Ook goed. Vraag naar de personeelsdienst.</p>', 'm-call-p'),
          B('015 305 305', 'tel:+3215305305', 'yellow', 'zw-btn--block')))

    return page(5, 'Vacature technieker — voorstel 5 (Snelsollicitatie mobiel)', 'vacature-technieker-5',
                [hero, form, job, voordelen, taken, profiel, dag, over, getuigen, bellen,
                 MOBILEBAR('Solliciteer in 30 seconden', '#solliciteer')])


PAGINAS = {2: pagina_2, 5: pagina_5}


def nest(elements, top=True):
    for e in elements:
        if e['elType'] == 'container':
            e['isInner'] = not top
        nest(e['elements'], top=False)


def main(which=None):
    os.makedirs(BUILD, exist_ok=True)
    for n, fn in sorted(PAGINAS.items()):
        if which and n not in which:
            continue
        _rng.seed(1000 + n)  # vaste id's: opnieuw bouwen geeft dezelfde elementen
        p = fn()
        nest(p['elements'])
        with open(os.path.join(BUILD, 'pagina-%d.json' % n), 'w', encoding='utf-8') as f:
            json.dump(p, f, ensure_ascii=False, indent=1)
        print('pagina-%d.json' % n, len(json.dumps(p)), 'bytes')


if __name__ == '__main__':
    import sys
    main({int(a) for a in sys.argv[1:]} or None)
