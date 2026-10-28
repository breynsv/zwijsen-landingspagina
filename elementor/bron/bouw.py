#!/usr/bin/env python3
"""Bouwt de vijf landingspagina's als Elementor-data (containers + native widgets).

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

# Bestaande foto's in de mediabibliotheek van zwijsen.be (dezelfde bestanden als in de drafts)
FOTO_MULTIMETER = {'id': 1230, 'url': 'https://staging.zwijsen.be/wp-content/uploads/2022/01/electrical-measurements-with-multimeter-tester-scaled.jpg'}
FOTO_KNIPTANG = {'id': 1240, 'url': 'https://staging.zwijsen.be/wp-content/uploads/2022/01/worker-is-cutting-wires-with-linemans-pliers-scaled.jpg'}

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


def B(text, href, variant='red', cls='', ghost_on_dark=False):
    s = {'text': text, 'link': {'url': href, 'is_external': '', 'nofollow': '', 'custom_attributes': ''}}
    if ghost_on_dark:
        s.update({'background_color': 'rgba(0,0,0,0)', 'button_text_color': '#FFFFFF',
                  'button_background_hover_color': '#FFFFFF', 'hover_color': '#121413',
                  'border_border': 'solid',
                  'border_width': {'unit': 'px', 'top': '2', 'right': '2', 'bottom': '2', 'left': '2', 'isLinked': True},
                  'border_color': 'rgba(255,255,255,0.5)'})
        cls = (cls + ' zw-btn--ghost').strip()
    else:
        g, hb, ht = VARIANTS[variant]
        s['__globals__'] = dict(g)
        s['button_background_hover_color'] = hb
        s['hover_color'] = ht
    if cls:
        s['_css_classes'] = cls
    return W('button', s)


def IMG(foto, alt, cls=''):
    s = {'image': {'id': foto['id'], 'url': foto['url'], 'alt': alt, 'source': 'library'},
         'image_size': 'large'}
    if cls:
        s['_css_classes'] = cls
    return W('image', s)


def IB(icon, title, desc, cls):
    return W('icon-box', {
        'selected_icon': {'value': icon, 'library': 'fa-solid'},
        'title_text': title, 'description_text': desc, 'title_size': 'h3',
        '_css_classes': cls,
    })


def HTML(html, cls=''):
    s = {'html': html.strip()}
    if cls:
        s['_css_classes'] = cls
    return W('html', s)


def FAQ(items, cls='zw-faq'):
    """Native Elementor-accordeon (details/summary), eerste item open."""
    rep, kids = [], []
    for q, a in items:
        rep.append({'item_title': q, '_id': _id(), 'element_css_id': ''})
        kids.append(C('', T('<p>%s</p>' % a)))
    w = W('nested-accordion', {
        'items': rep, 'default_state': 'expanded', 'max_items_expended': 'multiple', 'title_tag': 'div',
        'accordion_item_title_icon': {'value': 'fas fa-plus', 'library': 'fa-solid'},
        'accordion_item_title_icon_active': {'value': 'fas fa-times', 'library': 'fa-solid'},
        '_css_classes': cls,
    })
    w['elements'] = kids
    return w


def BG(foto, pos, overlay):
    """Achtergrondfoto met een kleurverloop erover, als native containerinstelling."""
    s = {'background_background': 'classic', 'background_image': dict(foto, source='library', alt=''),
         'background_position': pos, 'background_repeat': 'no-repeat', 'background_size': 'cover',
         'background_color': '#121413',
         'background_overlay_opacity': {'unit': 'px', 'size': 1, 'sizes': []}}
    s.update(overlay)
    return s


def GRAD(a, b, angle, a_stop=0, b_stop=100):
    return {'background_overlay_background': 'gradient',
            'background_overlay_color': a, 'background_overlay_color_stop': {'unit': '%', 'size': a_stop, 'sizes': []},
            'background_overlay_color_b': b, 'background_overlay_color_b_stop': {'unit': '%', 'size': b_stop, 'sizes': []},
            'background_overlay_gradient_type': 'linear',
            'background_overlay_gradient_angle': {'unit': 'deg', 'size': angle, 'sizes': []}}


def MOBILEBAR(text, href):
    return C('zwl zw-mobilebar', B(text, href, cls='zw-btn--block zw-btn--block'))


# ---------------------------------------------------------------- formulier

def formulier_js():
    js = open(os.path.join(ROOT, 'assets', 'apply-form.js'), encoding='utf-8').read()
    start = js.index('  /* Meta Pixel / GA4')
    end = js.index('  /* ----------------------------------------------------------------------\n     3. De motor')
    stub = ("  /* Meting staat UIT op staging: er is nog geen echte pixel-ID en geen\n"
            "     toestemmingslaag. Zie README, hoofdstuk \"Meta Pixel en events\". */\n"
            "  function track() {}\n  function trackStandard() {}\n\n")
    js = js[:start] + stub + js[end:]
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
                "  });")
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
    parts = [lees('basis.css'), lees('pagina-%d.css' % n)]
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


def pagina_1():
    hero = SEC('hero zw-rise-host',
        C('hero__inner',
          C('zw-rise',
            EYE('Vacature · regio Dendermonde', YELLOW, 'zw-eyebrow--redbar'),
            H('Technieker algemene elektriciteit', 'h1', color=WHITE),
            T('<p>Je bouwt en onderhoudt elektrische installaties in scholen, ziekenhuizen, rusthuizen en '
              'appartementsgebouwen. Vast contract, ervaren ploeg naast je, en werven die je \'s avonds gewoon '
              'weer thuis brengen.</p>', 'hero__sub'),
            C('facts', *[C('', H(k, 'div', 'fact-k', YELLOW), T('<p>%s</p>' % v, 'fact-v')) for k, v in [
                ('Regio', 'Dendermonde en omstreken'), ('Contract', 'Vast, voltijds'),
                ('Loon', 'Volgens PC 149.01'), ('VCA', 'Wij betalen je opleiding')]])),
          C('hero__form zw-rise zw-rise-2',
            H('Kom jij in aanmerking?', 'h2', 'hero__form-h', INK),
            T('<p>8 korte vragen. Je weet het in één minuut — een CV heb je niet nodig.</p>', 'hero__form-p'),
            HTML(formulier('inline'), 'zw-form'),
            id='solliciteer')),
        **BG(FOTO_MULTIMETER, 'center center', GRAD('rgba(18,20,19,0.96)', 'rgba(18,20,19,0.55)', 100)))

    job = SEC('zw-section',
        C('split',
          C('',
            EYE('De job'),
            H('Wat je elke dag doet', 'h2', color=INK),
            T('<p>Je werkt samen met ervaren collega\'s aan de opbouw en het onderhoud van elektrische '
              'installaties. Onze klanten zijn scholen, ziekenhuizen, rusthuizen en appartementsgebouwen — '
              'geen twee werven zijn hetzelfde.</p>', 'zw-lead'),
            UL(['Kabelgoten plaatsen', 'Kabels trekken', 'Schakelmateriaal plaatsen',
                'Verlichtingstoestellen aansluiten', 'Aansluitingen in het verdeelbord',
                'Mee zorgen dat de werf tot een goed einde komt'], 'taskgrid')),
          C('zw-card zw-card--accent',
            EYE('Jouw profiel'),
            H('Past dit bij jou?', 'h3', 'aside-h', INK),
            UL(['Een technische richting afgerond, of gelijkwaardig door ervaring',
                'Een eerste ervaring als elektricien is een plus, geen must',
                'Goede kennis van het Nederlands (Frans is meegenomen)',
                'Gemotiveerd en leergierig',
                'Je kan zelfstandig werken, met ervaren collega\'s als vangnet',
                'Rijbewijs B',
                'VCA-attest — heb je dat nog niet, dan regelen wij het'], 'zw-checks zw-checks--sm'))),
        C('cta-inline',
          T('<p><strong>Herken je jezelf hierin?</strong> Acht korte vragen en je weet of je in aanmerking komt.</p>'),
          B('Solliciteer in 60 seconden', '#solliciteer')))

    perks = [
        ('fas fa-euro-sign', 'Loon volgens PC 149.01', 'Een competitief pakket volgens het paritair comité, correct en op tijd betaald.'),
        ('fas fa-graduation-cap', 'Opleiding voorzien', 'VCA, bijscholing en on-the-job leren van collega\'s die het vak al jaren doen.'),
        ('fas fa-users', 'Teamwork én zelfstandigheid', 'Je krijgt je eigen verantwoordelijkheid, maar staat er nooit alleen voor.'),
        ('fas fa-bolt', 'Afwisselend werk', 'Scholen, ziekenhuizen, rusthuizen, appartementen — elke werf is anders.'),
        ('fas fa-map-marker-alt', 'Werven dicht bij huis', 'Je werkt in je eigen regio. Geen eindeloze ritten, \'s avonds gewoon thuis.'),
        ('fas fa-shield-alt', 'Een werkgever die blijft', 'Een groeiend familiebedrijf met een gevulde orderportefeuille en vaste contracten.'),
    ]
    aanbod = SEC('zw-section zw-section--grey',
        C('zw-intro',
          EYE('Ons aanbod'),
          H('Groei mee met een sterke, stabiele werkgever', 'h2', color=INK),
          T('<p>Electro Zwijsen bestaat al decennia en groeit nog altijd. Dat merk je aan je materiaal, je '
            'planning en de collega\'s naast je.</p>', 'zw-lead')),
        C('perks', *[IB(i, t, d, 'perk') for i, t, d in perks]),
        C('cta-inline',
          T('<p><strong>Klinkt dit als jouw volgende job?</strong> Geen CV nodig, geen motivatiebrief. '
            'Antwoord binnen één werkdag.</p>'),
          B('Start je sollicitatie', '#solliciteer')))

    citaat = SEC('zw-section',
        C('zw-narrow quote',
          T('<p>“Ik ben hier binnengekomen met een diploma en bijna geen werfervaring. Na drie maanden deed ik '
            'mijn eerste bord zelfstandig. Je krijgt hier de kans om het echt te leren.”</p>', 'quote-q'),
          T('<p><strong>[Naam collega]</strong>Technieker algemene elektriciteit · [x] jaar bij Electro Zwijsen</p>',
            'quote-who')))

    faq = SEC('zw-section zw-section--grey',
        C('zw-intro--center',
          EYE('Voor je solliciteert', cls='zw-eyebrow--center'),
          H('Veelgestelde vragen', 'h2', color=INK)),
        FAQ([
            ('Heb ik een CV nodig?', 'Nee. Je vult acht korte vragen in en laat je gegevens achter — dat volstaat. '
             'Heb je wel een CV bij de hand, dan mag je het toevoegen, maar het is niet verplicht.'),
            ('Ik heb nog geen VCA-attest. Kan ik dan solliciteren?', 'Zeker. VCA is een vereiste om op de werf te '
             'staan, maar je hoeft het niet vooraf te hebben. Wij plannen en betalen die opleiding voor je.'),
            ('Hoe snel krijg ik antwoord?', 'Binnen één werkdag krijg je een telefoontje. Je krijgt altijd een '
             'duidelijk antwoord, ook als het geen match blijkt te zijn.'),
            ('Ik heb weinig werfervaring. Maak ik kans?', 'Ja. Een eerste ervaring is een plus, geen voorwaarde. Wat '
             'telt is een technische achtergrond en de goesting om bij te leren. Je start altijd naast een ervaren collega.'),
            ('Is mijn sollicitatie vertrouwelijk?', 'Volledig. Je gegevens komen enkel bij onze personeelsdienst '
             'terecht en worden niet gedeeld met derden. Je huidige werkgever verneemt hier niets van.'),
        ]))

    slot = SEC('zw-section zw-section--ink finale',
        EYE('Klaar?', YELLOW, 'zw-eyebrow--center'),
        H('Eén minuut van je tijd, en je weet waar je staat', 'h2', color=WHITE),
        T('<p>Geen motivatiebrief, geen sollicitatieprocedure van drie weken. Acht vragen, je gegevens, en een '
          'telefoontje binnen de werkdag.</p>', 'finale-p'),
        B('Start je sollicitatie', '#solliciteer', cls='zw-btn--lg'),
        T('<p>Liever bellen? <a href="tel:+3215305305">015 305 305</a></p>', 'finale-call'))

    return page(1, 'Vacature technieker — voorstel 1 (Vacature+)', 'vacature-technieker-1',
                [hero, job, aanbod, citaat, faq, slot, MOBILEBAR('Solliciteer in 60 seconden', '#solliciteer')])


def pagina_2():
    hero = SEC('hero2',
        C('hero2__inner',
          C('zw-rise',
            EYE('Technieker algemene elektriciteit · Dendermonde'),
            H('Kom jij in aanmerking als <em>technieker</em> bij Electro Zwijsen?', 'h1', color=INK),
            T('<p>Acht korte vragen over je ervaring, je rijbewijs en je regio. Je weet meteen waar je staat — en '
              'wij weten of we je kunnen bellen. Geen CV, geen motivatiebrief, geen verplichtingen.</p>', 'hero2__sub'),
            B('Start de check', '#check', cls='zw-btn--lg hero2__btn'),
            UL(['60 seconden', 'Geen CV nodig', 'Volledig vertrouwelijk', 'Antwoord binnen 1 werkdag'], 'zw-trust')),
          C('starter zw-rise zw-rise-2',
            H('Wat we je vragen', 'h2', 'starter-h', INK),
            T('<p>Vooraf weten wat komt, scheelt een drempel. Dit is de volledige lijst:</p>', 'starter-p'),
            T('<p>De acht vragen</p><ol>' + ''.join('<li>%s</li>' % q for q in [
                'Je ervaring met elektriciteit', 'Residentieel, utiliteit of industrieel', "Schema's lezen",
                'VCA-attest', 'Rijbewijs', 'Kennis Nederlands', 'Regio Dendermonde', 'Wanneer je kan starten']) + '</ol>',
              'starter__preview'),
            T('<p>Ongeveer 60 sec</p>', 'meter'),
            B('Begin met vraag 1', '#check', cls='zw-btn--block'))))

    how = SEC('zw-section',
        C('zw-intro',
          EYE('Hoe het verder gaat'),
          H('Van één minuut naar een gesprek', 'h2', color=INK),
          T('<p>Geen zwart gat na het versturen. Dit is precies wat er gebeurt.</p>', 'zw-lead')),
        C('how', *[C('', H(t, 'h3', 'how-h', INK), T('<p>%s</p>' % d, 'how-p')) for t, d in [
            ('Je doet de check', 'Acht vragen, één minuut. Op het einde laat je je naam en nummer achter.'),
            ('Bevestiging in je mailbox', 'Meteen een mail met wie je contacteert, wanneer, en waarover het gesprek gaat.'),
            ('Telefoontje binnen 1 werkdag', 'Tien minuten kennismaken. Past het langs beide kanten, dan kom je langs in Duffel.')]]),
        B('Doe de check', '#check', cls='zw-after'))

    voordelen = SEC('zw-section zw-section--grey',
        C('zw-intro',
          EYE('Ons aanbod'),
          H('Wat je bij Electro Zwijsen krijgt', 'h2', color=INK)),
        C('benefits',
          C('', H('Het werk zelf', 'h3', 'benefits-h', INK),
            UL(['Opbouw en onderhoud van elektrische installaties',
                'Scholen, ziekenhuizen, rusthuizen en appartementsgebouwen',
                'Kabelgoten, kabels trekken, schakelmateriaal, verdeelborden',
                'Elke werf anders — geen dag hetzelfde',
                'Je start altijd naast een ervaren collega',
                'Zelfstandigheid zodra je er klaar voor bent'])),
          C('', H('De voorwaarden', 'h3', 'benefits-h', INK),
            UL(['Vast contract, voltijds', 'Competitief loon volgens PC 149.01',
                'VCA-opleiding betaald en ingepland door ons',
                'Doorlopende bijscholing en opleidingsmogelijkheden',
                "Werven in je eigen regio — 's avonds thuis",
                'Een groeiend bedrijf met een gevulde orderportefeuille']))),
        B('Kijk of het past', '#check', cls='zw-after'))

    regio = SEC('zw-section',
        C('region',
          C('',
            EYE('Je werkgebied'),
            H('Werven rond Dendermonde', 'h2', color=INK),
            T("<p>We zoeken specifiek versterking voor de werven in en rond Dendermonde. Woon je in deze omgeving, "
              "dan is je rit naar de werf kort — en ben je 's avonds gewoon weer thuis.</p>", 'zw-lead'),
            UL(['Dendermonde', 'Lebbeke', 'Buggenhout', 'Zele', 'Berlare', 'Waasmunster', 'Hamme', 'Wichelen',
                'Laarne', 'Bornem', 'Puurs-Sint-Amands', 'Willebroek'], 'region__towns')),
          C('', IMG(FOTO_KNIPTANG, 'Elektricien aan het werk met een kniptang', 'zw-photo'))))

    eerlijk = SEC('zw-section zw-section--grey',
        C('zw-intro',
          EYE('Eerlijk'),
          H('Wat deze sollicitatie wél en niet is', 'h2', color=INK)),
        C('compare',
          C('compare__col--no', H('Wat het niet is', 'h3', 'compare-h', INK),
            UL(['Geen uitgebreid formulier van twintig velden', 'Geen motivatiebrief schrijven',
                'Geen CV verplicht uploaden', 'Geen weken wachten op een antwoord',
                'Geen verbintenis — je kan altijd nog nee zeggen'], 'compare-l')),
          C('compare__col--yes', H('Wat het wél is', 'h3', 'compare-h', WHITE),
            UL(['Acht vragen die er echt toe doen', 'Meteen duidelijkheid of je profiel past',
                'Een bevestigingsmail met de volgende stap erin', 'Een telefoontje binnen één werkdag',
                'Altijd een duidelijk antwoord, ook bij een nee'], 'compare-l'))))

    slot = SEC('zw-section',
        C('cta-strip',
          C('', H('Benieuwd of je in aanmerking komt?', 'h3', 'cta-strip-h', WHITE),
            T('<p>Acht vragen, één minuut, en je weet het. Zonder CV.</p>', 'cta-strip-p')),
          B('Start de check', '#check', 'light', 'zw-btn--lg')),
        T('<p>Liever gewoon bellen? <a href="tel:+3215305305">015 305 305</a> — vraag naar de personeelsdienst.</p>',
          'call-note'))

    host = C('zwl zw-formhost', HTML(formulier('modal'), 'zw-form'))
    return page(2, 'Vacature technieker — voorstel 2 (Kwalificatietest)', 'vacature-technieker-2',
                [hero, how, voordelen, regio, eerlijk, slot, MOBILEBAR('Doe de check — 60 sec', '#check'), host])


def pagina_3():
    hero = SEC('hero3',
        C('hero3__inner',
          EYE('Technieker algemene elektriciteit · Dendermonde', YELLOW),
          H('Om 16u30 leg je je tang neer.<br><span>En dan is het gedaan.</span>', 'h1', 'zw-rise', WHITE),
          T("<p>Geen files van twee uur, geen werven aan de andere kant van het land. Je werkt in je eigen regio, "
            "met degelijk materiaal en collega's die het vak al twintig jaar doen. Dit is hoe je dag eruitziet.</p>",
            'hero3__sub zw-rise zw-rise-2'),
          C('zw-btnrow hero3__cta',
            B('Solliciteer in 60 seconden', '#solliciteer', cls='zw-btn--lg'),
            B('Lees eerst de dag', '#dag', cls='zw-btn--lg', ghost_on_dark=True)),
          UL(['Vast contract', 'Loon volgens PC 149.01', 'VCA betaald door ons', 'Geen CV nodig om te solliciteren'],
             'zw-trust')),
        **BG(FOTO_KNIPTANG, 'center center', GRAD('rgba(18,20,19,0.35)', 'rgba(18,20,19,0.96)', 180)))

    dag = [
        ('7u30', 'Start op de werf', 'Je rijdt rechtstreeks naar de werf — een school in Dendermonde die een nieuwe vleugel krijgt. Korte briefing met je ploegbaas: wat vandaag af moet, waar het materiaal staat, wie waaraan begint.', ['Korte rit', 'Duidelijke planning'], False),
        ('8u00', 'Kabelgoten en tracé', 'Je zet het tracé uit en hangt de kabelgoten. Recht, waterpas, netjes — want alles wat erna komt, hangt hieraan. Je werkt met een collega samen; één meet, één boort.', ['Kabelgoten', 'Teamwork'], False),
        ('10u00', 'Kabels trekken', "De goten liggen, nu gaan de kabels erin. Je leest het schema, je weet welke waar hoort, je labelt meteen. Kan je nog geen schema's lezen? Dan leer je het hier — naast iemand die het je toont in plaats van uitlegt.", ["Schema's lezen", 'On-the-job leren'], False),
        ('12u00', 'Middagpauze op de werf', 'Boterhammen in de keet, koffie, en het gesprek van elke werf: voetbal, de planning en wie er dit weekend werkt (niemand).', ['Vaste uren'], True),
        ('12u30', 'Schakelmateriaal en verlichting', 'Stopcontacten, schakelaars, verlichtingstoestellen. Het zichtbare werk — en dus het werk waar de klant naar kijkt. Hier telt afwerking net zo hard als techniek.', ['Schakelmateriaal', 'Verlichting'], False),
        ('14u30', 'Aansluiten in het verdeelbord', 'Het precisiewerk. Aders op de juiste klem, alles gelabeld, alles nagemeten. In het begin kijkt een ervaren collega mee. Na een paar maanden doe je je eerste bord zelfstandig.', ['Verdeelbord', 'Groeipad'], False),
        ('16u30', 'Opruimen en naar huis', 'Materiaal opbergen, werf proper achterlaten, kort doorgeven wat morgen nog moet. En dan naar huis — geen overuren die niemand gevraagd heeft.', ["'s Avonds thuis"], True),
    ]
    tl = '<div class="timeline">\n' + '\n'.join(
        '<article class="tl%s"><div class="tl__time">%s</div><div class="tl__body"><h3>%s</h3><p>%s</p>'
        '<div class="tl__tags">%s</div></div></article>' % (
            ' tl--gold' if gold else '', t, h, p, ''.join('<span>%s</span>' % x for x in tags))
        for t, h, p, tags, gold in dag) + '\n</div>'
    dagsec = SEC('zw-section',
        C('zw-intro--center',
          EYE('Een doordeweekse dinsdag', cls='zw-eyebrow--center'),
          H('Van 7u30 tot 16u30', 'h2', color=INK),
          T('<p>Geen twee werven zijn hetzelfde, maar het ritme wel. Zo verloopt een gewone dag bij Electro Zwijsen.</p>',
            'zw-lead')),
        HTML('<!-- Tijdlijn: tekst aanpassen kan hier rechtstreeks, de opmaak staat in de pagina-CSS -->\n' + tl),
        C('tl-cta zw-btnrow zw-center', B("Zo'n dag wil ik wel — solliciteer", '#solliciteer', cls='zw-btn--lg')),
        id='dag')

    eerlijk = SEC('zw-section zw-section--grey',
        C('zw-intro',
          EYE('Eerlijk erbij verteld'),
          H('Waar je rekening mee moet houden', 'h2', color=INK),
          T('<p>Een vacature die alleen maar mooi klinkt, klopt niet. Dit hoort er ook bij.</p>', 'zw-lead')),
        C('honest', *[C('', H(h, 'h3', 'honest-h', INK), T('<p>%s</p>' % p, 'honest-p')) for h, p in [
            ('Het is fysiek werk', 'Je staat, je kruipt, je boort boven je hoofd. Wie een bureaujob zoekt, zit hier verkeerd.'),
            ('Een werf is een werf', 'Stof, lawaai en soms een planning die schuift omdat een andere aannemer uitloopt.'),
            ('VCA is verplicht', 'Zonder attest kom je niet op de werf. Heb je het nog niet, dan plannen en betalen wij het.'),
            ('Je moet kunnen rijden', 'Rijbewijs B is nodig om de werven te bereiken. Zonder rijbewijs lukt het niet.')]]))

    plus = SEC('zw-section',
        C('zw-intro',
          EYE('Ons aanbod'),
          H('Wat daar tegenover staat', 'h2', color=INK)),
        C('plus', *[C('', H(h, 'h3', 'plus-h', INK), UL(items, 'zw-checks zw-checks--sm')) for h, items in [
            ('Je loon en contract', ['Vast contract, voltijds', 'Competitief loon volgens PC 149.01',
                                     'Correcte en stipte uitbetaling', 'Een bedrijf dat al decennia bestaat en nog groeit']),
            ('Je ontwikkeling', ['VCA-opleiding betaald en ingepland', 'Bijscholing en opleidingsmogelijkheden',
                                 'Je start naast een ervaren technieker', 'Zelfstandigheid zodra je er klaar voor bent']),
            ('Je dagelijkse leven', ['Werven in en rond Dendermonde', "Vaste uren, 's avonds gewoon thuis",
                                     'Degelijk materiaal en werkkledij voorzien',
                                     'Afwisseling: scholen, ziekenhuizen, rusthuizen, appartementen'])]]))

    band = SEC('band',
        C('band__inner',
          C('', H('Acht vragen. Eén minuut. Geen CV.', 'h3', 'band-h', INK),
            T('<p>En je weet meteen of je in aanmerking komt.</p>', 'band-p')),
          B('Start je sollicitatie', '#solliciteer', cls='zw-btn--lg')))

    apply = SEC('zw-section',
        C('applyblock',
          C('',
            EYE('Solliciteren'),
            H('Klaar om je eigen dag zo in te vullen?', 'h2', color=INK),
            T('<p>Je hoeft geen motivatiebrief te schrijven en geen CV op te zoeken. Acht korte vragen volstaan om '
              'te weten of het klikt.</p>', 'zw-lead'),
            UL(['Bevestiging per mail, meteen na het versturen', 'Telefoontje binnen één werkdag',
                'Daarna langskomen in Duffel: werkplek en ploeg zien',
                'Altijd een duidelijk antwoord, ook als het geen match is',
                'Volledig vertrouwelijk — je huidige werkgever verneemt niets'], 'zw-checks zw-checks--sm apply-list'),
            T('<p>Liever eerst even bellen? Dat mag ook: <a href="tel:+3215305305"><strong>015 305 305</strong></a></p>',
              'apply-call')),
          C('applyblock__form', HTML(formulier('inline'), 'zw-form'))),
        id='solliciteer')

    return page(3, 'Vacature technieker — voorstel 3 (Een dag op de werf)', 'vacature-technieker-3',
                [hero, dagsec, eerlijk, plus, band, apply, MOBILEBAR('Solliciteer in 60 seconden', '#solliciteer')])


def pagina_4():
    pakket = [('Contract', 'Vast, voltijds'), ('Loon', 'Volgens PC 149.01'), ('Sector', 'Elektriciens (149.01)'),
              ('Werkregio', 'Dendermonde e.o.'), ('VCA-attest', 'Wij betalen het'),
              ('Opleiding', 'On-the-job + bijscholing'), ('Werkkledij', 'Voorzien'), ('Ervaring vereist', 'Nee, een plus')]
    hero = SEC('hero4',
        C('hero4__inner',
          C('zw-rise',
            EYE('Technieker algemene elektriciteit · Dendermonde', YELLOW),
            H('Vergelijk gerust. <mark>Wij zetten alles op tafel.</mark>', 'h1', color=WHITE),
            T('<p>Je hebt al werk. Dan wissel je niet voor een mooie zin, maar voor betere voorwaarden. Hier staat '
              'het volledige pakket — geen "marktconform", geen "aantrekkelijk salaris" zonder uitleg.</p>', 'hero4__sub'),
            C('zw-btnrow hero4__cta',
              B('Solliciteer in 60 seconden', '#solliciteer', cls='zw-btn--lg'),
              B('Bekijk de voorwaarden', '#voorwaarden', cls='zw-btn--lg', ghost_on_dark=True))),
          C('package zw-rise zw-rise-2',
            H('Het pakket in het kort', 'div', 'package__top', INK),
            T('<ul>' + ''.join('<li>%s <strong>%s</strong></li>' % kv for kv in pakket) + '</ul>', 'package__list'),
            C('package__foot',
              B('Ik wil dit — solliciteer', '#solliciteer', cls='zw-btn--block'),
              T('<p>Geen CV nodig · antwoord binnen 1 werkdag</p>', 'package__note')))))

    rijen = [
        ('Contract', 'Vast contract, voltijds. Geen interim-constructie, geen proefproject.'),
        ('Loon', 'Competitief salarispakket conform paritair comité 149.01, afgestemd op je ervaring en anciënniteit.'),
        ('Werkuren', "Vaste dagploeg. Je start op de werf en bent 's avonds gewoon weer thuis."),
        ('Werkgebied', 'Werven in en rond Dendermonde: Lebbeke, Buggenhout, Zele, Berlare, Hamme, Waasmunster, Wichelen, Laarne en omgeving.'),
        ('Soort klanten', 'Scholen, ziekenhuizen, rusthuizen en appartementsgebouwen. Residentieel en utiliteit, geen twee werven hetzelfde.'),
        ('Je taken', 'Kabelgoten plaatsen, kabels trekken, schakelmateriaal plaatsen, verlichtingstoestellen aansluiten, aansluitingen in het verdeelbord.'),
        ('VCA-attest', 'Vereist om op de werf te staan. Heb je het nog niet, dan plannen en betalen wij de opleiding.'),
        ('Rijbewijs', 'Rijbewijs B is nodig om de werven te bereiken.'),
        ('Taal', 'Goede kennis van het Nederlands. Frans is een pluspunt, geen vereiste.'),
        ('Ervaring', 'Een eerste ervaring als elektricien is een plus, geen voorwaarde. Een technische opleiding en leergierigheid volstaan.'),
        ('Begeleiding', 'Je start naast een ervaren technieker en groeit naar zelfstandig werk.'),
        ('Materiaal', 'Werkkledij en gereedschap worden voorzien.'),
    ]
    tabel = ('<!-- LET OP: werkuren en materiaal zijn ingevuld op basis van wat gangbaar is in PC 149.01. '
             'Laten bevestigen door Katrien en aanvullen met extralegale voordelen als die er zijn. -->\n'
             '<div class="tablewrap"><table class="terms"><caption>Technieker algemene elektriciteit — regio Dendermonde</caption><tbody>\n'
             + '\n'.join('<tr><th>%s</th><td>%s</td></tr>' % r for r in rijen) + '\n</tbody></table></div>')
    voorwaarden = SEC('zw-section',
        C('zw-intro',
          EYE('Alle voorwaarden'),
          H('Punt per punt, zonder wollige taal', 'h2', color=INK),
          T('<p>Zo weet je meteen waar je aan toe bent — en of het de moeite is om verder te praten.</p>', 'zw-lead')),
        HTML(tabel),
        C('zw-btnrow terms-cta',
          B('Dit past bij mij — solliciteer', '#solliciteer', cls='zw-btn--lg'),
          T('<p>Of bel <a href="tel:+3215305305"><strong>015 305 305</strong></a> voor je solliciteert.</p>')),
        id='voorwaarden')

    redenen = SEC('zw-section zw-section--grey',
        C('zw-intro',
          EYE('Waarom Electro Zwijsen'),
          H('Zes redenen die je pas na een jaar echt voelt', 'h2', color=INK)),
        C('stack', *[C('', H(h, 'h3', 'stack-h', INK), T('<p>%s</p>' % p, 'stack-p')) for h, p in [
            ('Een werkgever die blijft bestaan', 'Electro Zwijsen bestaat al decennia, groeit nog steeds en heeft een gevulde orderportefeuille. Je bouwt hier iets op.'),
            ('Je leert er effectief bij', "Opleidingsmogelijkheden en bijscholing, plus collega's die het vak al jaren doen en het je willen tonen."),
            ('Afwisseling, geen sleur', 'Scholen, ziekenhuizen, rusthuizen, appartementen. Andere werf, andere uitdaging.'),
            ('Zelfstandigheid met vangnet', 'Je krijgt je eigen verantwoordelijkheid, maar er is altijd iemand om te bellen.'),
            ('Twee vestigingen, één aanpak', 'Duffel en Zonhoven, met dezelfde manier van werken: correct, degelijk, afgewerkt.'),
            ('Werk dicht bij huis', 'We zoeken specifiek versterking voor de regio Dendermonde. Korte ritten, geen eindeloze verplaatsingen.')]]),
        B('Start je sollicitatie', '#solliciteer', cls='zw-btn--lg zw-after'))

    apply = SEC('zw-section',
        C('applygrid',
          C('',
            EYE('Solliciteren'),
            H('Acht vragen, en je weet waar je staat', 'h2', color=INK),
            T('<p>We vragen geen motivatiebrief. We vragen wat je kan, wat je hebt en waar je woont — dat is genoeg '
              'om te weten of het zin heeft om te bellen.</p>', 'zw-lead'),
            H('Wat er daarna gebeurt', 'h3', 'sub-h', INK),
            UL(['<strong>Meteen:</strong> een bevestigingsmail met de volgende stap',
                '<strong>Binnen 1 werkdag:</strong> een telefoontje van tien minuten',
                '<strong>Daarna:</strong> langskomen in Duffel, werkplek en ploeg zien',
                '<strong>Altijd:</strong> een duidelijk antwoord, ook bij een nee'], 'zw-checks zw-checks--sm'),
            H('Wat je niet hoeft te doen', 'h3', 'sub-h', INK),
            UL(['Geen motivatiebrief schrijven', 'Geen CV opzoeken of uploaden — mag wel, moet niet',
                'Geen weken wachten op een antwoord', 'Geen verbintenis aangaan: informeren mag vrijblijvend'],
               'zw-checks zw-checks--sm'),
            T('<p>Volledig vertrouwelijk. Je gegevens gaan enkel naar onze personeelsdienst en worden niet gedeeld '
              'met derden.</p>', 'apply-note')),
          C('applygrid__form',
            H('Kom jij in aanmerking?', 'h3', 'form-h', INK),
            T('<p>Eén minuut. Geen CV nodig.</p>', 'form-p'),
            HTML(formulier('inline'), 'zw-form'))),
        id='solliciteer')

    slot = SEC('zw-section zw-section--ink band4',
        EYE('Nog twijfel?', YELLOW, 'zw-eyebrow--center'),
        H('Bellen mag ook. Dan hoor je het gewoon van ons.', 'h2', color=WHITE),
        T('<p>Liever eerst je vragen stellen voor je iets invult? Volkomen begrijpelijk. Vraag naar de '
          'personeelsdienst — ze weten waarover het gaat.</p>', 'band4-p'),
        C('zw-btnrow zw-center',
          B('015 305 305', 'tel:+3215305305', 'yellow', 'zw-btn--lg'),
          B('Toch online solliciteren', '#solliciteer', cls='zw-btn--lg', ghost_on_dark=True)))

    return page(4, 'Vacature technieker — voorstel 4 (Wat je krijgt)', 'vacature-technieker-4',
                [hero, voorwaarden, redenen, apply, slot, MOBILEBAR('Solliciteer in 60 seconden', '#solliciteer')])


def pagina_5():
    def M(cls, *kids, **kw):
        return SEC('m-col ' + cls, *kids, **kw)

    hero = M('m-hero',
        EYE('Regio Dendermonde', YELLOW),
        H('Elektricien?<br><span>Wij zoeken jou.</span>', 'h1', 'zw-rise', WHITE),
        T("<p>Technieker algemene elektriciteit bij Electro Zwijsen. Vast contract, werven in je eigen regio, en "
          "'s avonds gewoon thuis.</p>", 'm-hero-p zw-rise zw-rise-2'),
        UL(['Vast contract', 'PC 149.01', 'VCA betaald', 'Geen CV nodig'], 'chips'),
        **BG(FOTO_MULTIMETER, 'center center',
             {'background_overlay_background': 'classic', 'background_overlay_color': 'rgba(18,20,19,0.78)'}))

    form = M('m-formsec',
        C('m-form',
          H('Solliciteer in 60 seconden', 'h2', 'm-form-h', INK),
          T('<p>Acht korte vragen. Geen CV, geen motivatiebrief.</p>', 'm-form-p'),
          HTML(formulier('inline'), 'zw-form'),
          id='solliciteer'))

    def cta(text, note=None):
        return C('m-cta', B(text, '#solliciteer', cls='zw-btn--block'),
                 T('<p>%s</p>' % note, 'm-cta-note') if note else None)

    taken = [
        ('fas fa-grip-lines', 'Kabelgoten plaatsen', 'Recht, waterpas en netjes — de basis van de hele werf.'),
        ('fas fa-wave-square', 'Kabels trekken', 'Volgens schema, meteen gelabeld.'),
        ('fas fa-toggle-on', 'Schakelmateriaal plaatsen', 'Het zichtbare werk, waar afwerking telt.'),
        ('fas fa-bolt', 'Verlichting aansluiten', 'Van armaturen tot noodverlichting.'),
        ('fas fa-th-large', 'Aansluiten in het verdeelbord', 'Precisiewerk. Eerst met begeleiding, daarna zelfstandig.'),
    ]
    job = M('m-sec',
        EYE('De job'),
        H('Wat je doet', 'h2', color=INK),
        T("<p>Je bouwt en onderhoudt elektrische installaties in scholen, ziekenhuizen, rusthuizen en "
          "appartementsgebouwen. Samen met ervaren collega's.</p>", 'm-intro'),
        C('m-list', *[IB(i, t, d, 'm-item') for i, t, d in taken]),
        cta('Solliciteer nu', 'Duurt 60 seconden · geen CV nodig'))

    krijgt = M('m-sec m-sec--grey',
        EYE('Ons aanbod'),
        H('Wat je krijgt', 'h2', color=INK),
        UL(['<strong>Vast contract, voltijds</strong> — geen interim-constructie',
            '<strong>Loon volgens PC 149.01</strong> — competitief en correct betaald',
            '<strong>VCA-opleiding betaald</strong> — heb je het attest nog niet, dan regelen wij het',
            "<strong>Opleiding en bijscholing</strong> — je leert het vak van collega's die het al jaren doen",
            "<strong>Werven in je eigen regio</strong> — korte ritten, 's avonds thuis",
            '<strong>Afwisselend werk</strong> — scholen, ziekenhuizen, rusthuizen, appartementen',
            '<strong>Teamwork én zelfstandigheid</strong> — eigen verantwoordelijkheid, altijd een vangnet',
            '<strong>Een stabiele werkgever</strong> — een groeiend bedrijf met een gevulde orderportefeuille'],
           'zw-checks m-gap'),
        cta('Ik wil solliciteren'))

    profiel = M('m-sec',
        EYE('Jouw profiel'),
        H('Wie we zoeken', 'h2', color=INK),
        UL(['Een technische richting afgerond, of gelijkwaardig door ervaring',
            'Een eerste ervaring als elektricien is een plus, geen must',
            'Goede kennis van het Nederlands (Frans is meegenomen)', 'Gemotiveerd en leergierig',
            "Je kan zelfstandig werken, met ervaren collega's als vangnet", 'Rijbewijs B',
            'VCA-attest — of de wil om het te halen'], 'zw-checks m-gap'),
        cta('Kom ik in aanmerking?', 'Acht vragen en je weet het meteen'))

    stappen = M('m-sec m-sec--grey',
        EYE('Hoe het verder gaat'),
        H('Na je sollicitatie', 'h2', color=INK),
        T('<ol>' + ''.join('<li><strong>%s</strong><span>%s</span></li>' % s for s in [
            ('Bevestiging per mail', 'Meteen, met wie je contacteert en wanneer.'),
            ('Telefoontje binnen 1 werkdag', 'Tien minuten kennismaken, geen druk.'),
            ('Langskomen in Duffel', 'Je ziet het atelier, het materiaal en je ploeg.'),
            ('Duidelijk antwoord', 'Altijd — ook als het geen match is.')]) + '</ol>', 'm-steps'))

    vragen = M('m-sec',
        EYE('Vragen'),
        H('Kort beantwoord', 'h2', color=INK),
        FAQ([
            ('Heb ik een CV nodig?', 'Nee. Acht vragen en je gegevens volstaan. Een CV toevoegen mag, maar hoeft niet.'),
            ('Ik heb geen VCA-attest.', 'Geen probleem. VCA is nodig om op de werf te staan, maar wij plannen en betalen die opleiding voor je.'),
            ('Ik heb weinig ervaring. Maak ik kans?', 'Ja. Een technische opleiding en goesting om bij te leren volstaan. Je start altijd naast een ervaren collega.'),
            ('Waar zou ik werken?', 'Op werven in en rond Dendermonde: Lebbeke, Buggenhout, Zele, Berlare, Hamme, Waasmunster en omgeving.'),
            ('Verneemt mijn huidige werkgever hiervan?', 'Nee. Je sollicitatie gaat rechtstreeks en uitsluitend naar onze personeelsdienst.'),
        ], 'zw-faq zw-faq--sm m-faq-w'),
        cta('Solliciteer in 60 seconden'))

    bellen = M('m-sec m-sec--grey',
        C('m-call',
          H('Liever gewoon bellen?', 'h3', 'm-call-h', WHITE),
          T('<p>Ook goed. Vraag naar de personeelsdienst — zij weten waarover het gaat.</p>', 'm-call-p'),
          B('015 305 305', 'tel:+3215305305', 'yellow', 'zw-btn--block')))

    return page(5, 'Vacature technieker — voorstel 5 (Snelsollicitatie mobiel)', 'vacature-technieker-5',
                [hero, form, job, krijgt, profiel, stappen, vragen, bellen,
                 MOBILEBAR('Solliciteer in 60 seconden', '#solliciteer')])


PAGINAS = {1: pagina_1, 2: pagina_2, 3: pagina_3, 4: pagina_4, 5: pagina_5}


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
