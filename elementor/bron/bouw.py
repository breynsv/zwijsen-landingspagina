#!/usr/bin/env python3
"""Bouwt de twee goedgekeurde regiopagina's als Elementor-data (containers + native widgets).

Sinds 31 okt 2026 (definitief) staan op staging enkel nog de twee
regiopagina's van voorstel 5 met film:

    5-snelsollicitatie-mobiel-film.html                        -> elektricien-dendermonde (post 3292)
    5-snelsollicitatie-mobiel-film-lier-antwerpen-zuidrand.html -> elektricien-lier-antwerpen-zuidrand (nieuw)

De lokale HTML is de bron: alle teksten, de lijst met foto's, de kaart (SVG),
de film (shots), de vragen onderaan en de JSON-LD worden er met bs4 uit
gelezen. Een tekstwijziging = de HTML aanpassen en opnieuw bouwen.
(Voorstel 2 en de oude versie van 5 zitten in de git-geschiedenis.)

Uitvoer in elementor/bron/build/:
    pagina-dendermonde.json, pagina-lier.json   titel, slug, (id), elements, page settings, yoast
    static/js/apply-form.js                      formulier ZONDER meting (geen pixel/gtag/dataLayer)
    static/js/film.js                            ongewijzigd
    static/*.avif|webp                           de foto's die de film via JS laadt
bron/plaats.php zet een pagina op de site; bron/herplaats.sh doet het geheel.

    python3 elementor/bron/bouw.py
"""
import hashlib
import json
import os
import random
import re
import shutil

from bs4 import BeautifulSoup, Comment

HIER = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HIER))
BUILD = os.path.join(HIER, 'build')
STATIC = os.path.join(BUILD, 'static')
JANA = os.path.join(ROOT, 'assets', 'img', 'jana')

# Vaste map op de site voor wat JS laadt (film-foto's, scripts). Buiten de mediabibliotheek.
STATIC_URL = '/wp-content/uploads/zwijsen-landing/'
# Verzending van het formulier: REST-route van de mu-plugin elementor/server/zwijsen-snelsollicitatie.php.
# De lokale HTML-drafts geven geen endpoint mee en blijven dus in demomodus.
FORM_ENDPOINT = '/wp-json/zwijsen/v1/sollicitatie'

# Foto's in de mediabibliotheek van staging (id + url + alt). Zie media.json.
MEDIA = json.load(open(os.path.join(HIER, 'media.json'), encoding='utf-8'))

PAGINAS = {
    'dendermonde': {
        'bron': '5-snelsollicitatie-mobiel-film.html',
        'title': 'Vacature elektricien Dendermonde',
        'slug': 'elektricien-dendermonde',
        'id': 3292,  # de oude "vacature-technieker-5", in place bijgewerkt
        'formulier': 'dendermonde',  # sleutel in mu-plugin zwijsen-snelsollicitatie (formuliernaam + regio)
    },
    'lier': {
        'bron': '5-snelsollicitatie-mobiel-film-lier-antwerpen-zuidrand.html',
        'title': 'Vacature elektricien Lier en Antwerpen-Zuidrand',
        'slug': 'elektricien-lier-antwerpen-zuidrand',
        'formulier': 'lier',
    },
}

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


def IMG(naam, cls='', size='medium_large'):
    """Native afbeelding uit de mediabibliotheek. WordPress maakt zelf srcset en lazy loading;
    de alt-tekst staat op de bijlage zelf (Elementor neemt die over)."""
    m = MEDIA[naam]
    s = {'image': {'id': m['id'], 'url': m['url'], 'alt': m['alt'], 'source': 'library'},
         'image_size': size, 'align': 'left', '_css_classes': ('zp-pic ' + cls).strip()}
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


def FAQ(items, cls='zw-faq zw-faq--sm m-faq-w'):
    """Native Elementor-accordeon (nested accordion = <details>/<summary>): het antwoord
    staat in de HTML, ook dichtgeklapt, dus zonder JS leesbaar voor zoekmachines."""
    rep, kids = [], []
    for q, a in items:
        rep.append({'item_title': q, '_id': _id(), 'element_css_id': ''})
        kids.append(C('', T('<p>%s</p>' % a)))
    w = W('nested-accordion', {
        'items': rep, 'default_state': 'all_collapsed', 'max_items_expended': 'multiple', 'title_tag': 'h3',
        'accordion_item_title_icon': {'value': 'fas fa-plus', 'library': 'fa-solid'},
        'accordion_item_title_icon_active': {'value': 'fas fa-times', 'library': 'fa-solid'},
        '_css_classes': cls,
    })
    w['elements'] = kids
    return w


def BG(naam, overlay, xy=None):
    """Achtergrondfoto met een kleurlaag erover, als native containerinstelling.
    Elementor gebruikt het volle bestand (1600 px, 50 kB na Imagify) op elke breedte."""
    m = MEDIA[naam]
    s = {'background_background': 'classic',
         'background_image': {'id': m['id'], 'url': m['url'], 'source': 'library', 'alt': ''},
         'background_repeat': 'no-repeat', 'background_size': 'cover',
         'background_color': '#121413',
         'background_overlay_opacity': {'unit': 'px', 'size': 1, 'sizes': []}}
    if xy:
        s['background_position'] = 'initial'
        s['background_xpos'] = {'unit': '%', 'size': xy[0], 'sizes': []}
        s['background_ypos'] = {'unit': '%', 'size': xy[1], 'sizes': []}
    s.update(overlay)
    return s


# ---------------------------------------------------------------- statische bestanden

def versie(path):
    return hashlib.sha1(open(path, 'rb').read()).hexdigest()[:10]


def formulier_js():
    """apply-form.js zonder meting: geen pixel, geen gtag, geen dataLayer op staging."""
    js = open(os.path.join(ROOT, 'assets', 'apply-form.js'), encoding='utf-8').read()
    start = js.index('  /* Meta Pixel / GA4')
    end = js.index('  /* ----------------------------------------------------------------------\n     3. De motor')
    stub = ("  /* Meting staat UIT op staging: er is nog geen echte pixel-ID en geen\n"
            "     toestemmingslaag. Zie README, hoofdstuk \"Meta Pixel en events\".\n"
            "     (Gebouwd door elementor/bron/bouw.py uit assets/apply-form.js.) */\n"
            "  function track() {}\n  function trackStandard() {}\n\n")
    js = js[:start] + stub + js[end:]
    oud = "var header = document.querySelector('.zw-header');"
    assert oud in js, 'kleefbalk: hoofdingselector niet gevonden'
    js = js.replace(oud, "var header = document.querySelector('.zw-header')"
                         " || document.querySelector('.elementor-location-header .elementor-sticky--active:not(.elementor-sticky__spacer)')"
                         " || document.querySelector('.elementor-location-header');")
    assert 'fbq' not in js and 'gtag' not in js and 'dataLayer' not in js, 'meetcode niet volledig verwijderd'
    return js


def bouw_static(shots):
    """Scripts + de film-foto's (AVIF/WebP, alle breedtes die de film opvraagt)."""
    if os.path.isdir(STATIC):
        shutil.rmtree(STATIC)
    os.makedirs(os.path.join(STATIC, 'js'))
    open(os.path.join(STATIC, 'js', 'apply-form.js'), 'w', encoding='utf-8').write(formulier_js())
    shutil.copy(os.path.join(ROOT, 'assets', 'film.js'), os.path.join(STATIC, 'js', 'film.js'))
    n = 0
    for img, widths in sorted(shots.items()):
        for w in sorted(widths):
            for ext in ('avif', 'webp'):
                f = '%s-%d.%s' % (img, w, ext)
                src = os.path.join(JANA, f)
                assert os.path.exists(src), f
                shutil.copy(src, os.path.join(STATIC, f))
                n += 1
    return n


# ---------------------------------------------------------------- uit de lokale HTML lezen

def tekst(el):
    s = re.sub(r'\s+', ' ', el.get_text(' ', strip=True))
    return re.sub(r' ([,.?!:])', r'\1', s)


def inner(el):
    """Inhoud van een element als HTML, witruimte samengevouwen."""
    return re.sub(r'\s+', ' ', ''.join(str(c) for c in el.contents)).strip()


class Bron:
    def __init__(self, naam):
        self.html = open(os.path.join(ROOT, naam), encoding='utf-8').read()
        self.soup = BeautifulSoup(self.html, 'html.parser')
        for c in self.soup.find_all(string=lambda s: isinstance(s, Comment)):
            c.extract()

    def sectie(self, eyebrow):
        for s in self.soup.select('main section'):
            e = s.select_one('.zw-eyebrow')
            if e and tekst(e) == eyebrow:
                return s
        raise KeyError(eyebrow)

    def lijst(self, ul):
        return [inner(li) for li in ul.find_all('li', recursive=False)]

    def fotos(self, scope):
        """Namen van de foto's (zonder breedte/extensie), in volgorde, voor elke <picture> in scope."""
        return [re.sub(r'-\d+\.jpg$', '', os.path.basename(img['src'])) for img in scope.select('picture img')]

    def meta(self, name):
        return self.soup.find('meta', attrs={'name': name})['content']

    def jsonld(self):
        return [s.string.strip() for s in self.soup.find_all('script', type='application/ld+json')]

    def mount_opts(self):
        m = re.search(r"ZwijsenApply\.mount\('#apply'(.*?)\);", self.html, re.S)
        return m.group(1)  # '' of ", { ... }"

    def film_shots(self):
        """De letterlijke shots-array uit de pagina (de filmteksten staan in de pagina, niet in film.js)."""
        m = re.search(r"ZwijsenFilm\.mount\('#film', \{(.*?)\n    \}\);", self.html, re.S)
        body = m.group(1)
        shots = body[body.index('shots:'):].rstrip()
        imgs = {}
        for img, ws in re.findall(r"img: '([^']+)', w: \[([^\]]+)\]", shots):
            imgs.setdefault(img, set()).update(int(w) for w in ws.split(','))
        return shots, imgs


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


def pagina_css():
    parts = [lees('basis.css'), lees('fotos.css'), lees('pagina-5.css'), lees('pagina-film.css'),
             prefix_css(open(os.path.join(ROOT, 'assets', 'film.css'), encoding='utf-8').read()),
             prefix_css(lees('formulier-extra.css')),
             prefix_css(open(os.path.join(ROOT, 'assets', 'apply-form.css'), encoding='utf-8').read())]
    css = '\n\n'.join(parts)
    # het woord dat Elementor vervangt, mag alleen als voorvoegsel voorkomen
    assert 'selectors' not in css
    return css


# ---------------------------------------------------------------- de pagina

ICONEN = {  # Font Awesome in plaats van de lijntekeningen van de draft (zelfde betekenis)
    'Grote werven': 'fas fa-building', 'Breed takenpakket': 'fas fa-bolt', 'Afwisseling': 'fas fa-sync-alt',
    'Vrijheid in je vak': 'fas fa-compass', 'In duo': 'fas fa-users', 'Vast contract': 'fas fa-shield-alt',
}


def pagina(cfg, versies):
    src = Bron(cfg['bron'])
    M = lambda cls, *kids, **kw: SEC('m-col ' + cls, *kids, **kw)

    def cta(text, note=None):
        return C('m-cta', B(text, '#solliciteer', cls='zw-btn--block'),
                 T('<p>%s</p>' % note, 'm-cta-note') if note else None)

    def ctas(sec):
        """De knop onderaan een sectie, met de kleine regel eronder als die er is."""
        box = sec.select('.m-cta')[-1]
        small = box.select_one('small')
        return cta(tekst(box.a), tekst(small) if small else None)

    # Hero
    hero_s = src.soup.select_one('.m-hero')
    hero = M('m-hero',
        EYE(tekst(hero_s.select_one('.zw-eyebrow')), YELLOW),
        H(inner(hero_s.h1), 'h1', 'zw-rise', WHITE),
        T('<p>%s</p>' % inner(hero_s.p), 'm-hero-p zw-rise zw-rise-2'),
        UL(src.lijst(hero_s.select_one('ul.chips')), 'chips'),
        **BG('technieker-bij-bestelwagen',
             {'background_overlay_background': 'classic', 'background_overlay_color': 'rgba(18,20,19,0.66)'},
             xy=(72, 30)))

    # Formulier
    form_s = src.soup.select_one('#solliciteer')
    form_html = (
        '<div id="apply" data-za-endpoint="%s" data-za-formulier="%s"></div>\n'
        '<!-- Verzending: POST naar data-za-endpoint (mu-plugin zwijsen-snelsollicitatie):\n'
        '     bewaren bij Elementor > Inzendingen, mail aan personeel@zwijsen.be, bevestiging\n'
        '     aan de sollicitant. Zonder data-za-endpoint verstuurt het formulier niets. -->\n'
        '<script src="%sjs/apply-form.js?v=%s" defer></script>\n'
        '<script>\n'
        'document.addEventListener(\'DOMContentLoaded\', function () {\n'
        '  ZwijsenApply.mount(\'#apply\'%s);\n'
        '  /* Actiebalk onderaan weg zolang het formulier zelf in beeld is (anders bedekt\n'
        '     hij op gsm de knop "Volgende"). */\n'
        '  var bar = document.querySelector(\'.zw-mobilebar\');\n'
        '  var form = document.getElementById(\'solliciteer\');\n'
        '  if (bar && form && \'IntersectionObserver\' in window) {\n'
        '    new IntersectionObserver(function (en) { bar.classList.toggle(\'is-weg\', en[0].isIntersecting); },\n'
        '      { threshold: 0.05 }).observe(form);\n'
        '  }\n'
        '});\n'
        '</script>') % (FORM_ENDPOINT, cfg['formulier'], STATIC_URL, versies['apply-form.js'], src.mount_opts())
    form = M('m-formsec',
        C('m-form',
          C('m-form__top',
            H(tekst(form_s.select_one('.m-form__top h2')), 'h2', 'm-form-h', INK),
            T('<p>%s</p>' % inner(form_s.select_one('.m-form__top p')), 'm-form-p')),
          HTML(form_html, 'zw-form'),
          id='solliciteer', _attributes='data-za-scroll|1'))

    # De job + film
    job_s = src.sectie('De job')
    shots, _ = src.film_shots()
    film_html = (
        str(job_s.select_one('#film')) + '\n'
        '<script src="%sjs/film.js?v=%s" defer></script>\n'
        '<script>\n'
        '/* De zes punten uit de copy van Jana, in dezelfde volgorde als de lijst eronder.\n'
        '   De foto\'s staan als vaste bestanden in %s (AVIF/WebP, meerdere breedtes);\n'
        '   lazyStart: niets laden voor de film in de buurt komt. */\n'
        'document.addEventListener(\'DOMContentLoaded\', function () {\n'
        '  var punten = document.querySelectorAll(\'#punten > .elementor-widget\');\n'
        '  ZwijsenFilm.mount(\'#film\', {\n'
        '    base: \'%s\',\n'
        '    lazyStart: true,\n'
        '    still: 4,\n'
        '    onShot: function (i) {\n'
        '      for (var n = 0; n < punten.length; n++) punten[n].classList.toggle(\'is-film\', n === i);\n'
        '    },\n'
        '    %s\n'
        '  });\n'
        '});\n'
        '</script>') % (STATIC_URL, versies['film.js'], STATIC_URL, STATIC_URL, shots)
    punten = []
    for li in job_s.select('#punten > li'):
        t = tekst(li.strong)
        d = inner(li.select('span')[-1]).split('</strong>', 1)[1].strip()
        punten.append(IB(ICONEN[t], t, d, 'm-item'))
    job = M('m-sec',
        EYE('De job'),
        H(tekst(job_s.h2), 'h2', color=INK),
        HTML(film_html, 'zw-film'),
        C('m-list', *punten, id='punten'),
        ctas(job_s))

    # Voordelen + fotostrook
    aanbod_s = src.sectie('Ons aanbod')
    voordelen = M('m-sec m-sec--grey',
        EYE('Ons aanbod'),
        H(tekst(aanbod_s.h2), 'h2', color=INK),
        UL(src.lijst(aanbod_s.select_one('ul.zw-checks')), 'zw-checks m-gap m-15'),
        C('zp-band-wrap',
          C('zp-band zp-band--strip', *[IMG(f) for f in src.fotos(aanbod_s.select_one('.zp-band'))]),
          T('<p>%s</p>' % tekst(aanbod_s.select_one('.zp-swipe-hint')), 'zp-swipe-hint')),
        ctas(aanbod_s))

    # Taken + fotoblok
    taken_s = src.sectie('Je taken')
    taken = M('m-sec',
        EYE('Je taken'),
        H(tekst(taken_s.h2), 'h2', color=INK),
        T('<p>%s</p>' % inner(taken_s.p), 'm-intro'),
        UL(src.lijst(taken_s.select_one('ul.zw-checks')), 'zw-checks m-15'),
        C('zp-werk', *[IMG(f) for f in src.fotos(taken_s.select_one('.zp-werk'))]),
        ctas(taken_s))

    # Profiel + kaart (inline SVG in een HTML-widget)
    profiel_s = src.sectie('Jouw profiel')
    profiel = M('m-sec m-sec--grey',
        EYE('Jouw profiel'),
        H(tekst(profiel_s.h2), 'h2', color=INK),
        UL(src.lijst(profiel_s.select_one('ul.zw-checks')), 'zw-checks m-gap m-15'),
        HTML(re.sub(r'>\s+<', '><', str(profiel_s.select_one('figure.zk-fig'))), 'zw-kaart'),
        ctas(profiel_s))

    # Je dag
    dag_s = src.sectie('Je werkweek')
    stappen = ''.join('<li><strong>%s</strong><span>%s</span></li>' % (tekst(li.strong), inner(li.span))
                      for li in dag_s.select('.m-steps > li'))
    dag = M('m-sec',
        EYE('Je werkweek'),
        H(tekst(dag_s.h2), 'h2', color=INK),
        T('<ol>%s</ol>' % stappen, 'm-steps'))

    # Over ons + luchtfoto
    over_s = src.sectie('Over ons')
    over = M('m-sec m-sec--grey',
        C('zw-about',
          EYE('Over ons'), H(tekst(over_s.h2), 'h2', color=INK), T('<p>%s</p>' % inner(over_s.p)),
          IMG(src.fotos(over_s)[0], 'zp-group m-group', '1536x1536')))

    # Getuigenissen
    g_s = src.sectie('Collega’s aan het woord')
    quotes = []
    for fig in g_s.select('figure.zw-quote'):
        who = fig.select_one('.zp-who > span')
        naam = tekst(who.strong)
        functie = tekst(who).replace(naam, '', 1).strip()
        quotes.append(QUOTE(tekst(fig.blockquote), naam, functie, src.fotos(fig)[0], 'zp-' + naam.lower()))
    getuigen = M('m-sec',
        EYE('Collega’s aan het woord'),
        H(tekst(g_s.h2), 'h2', 'm-quotes-h', INK),
        C('zw-quotes', *quotes),
        ctas(g_s))

    # Veelgestelde vragen + JSON-LD (JobPosting + FAQPage, letterlijk uit de lokale HTML)
    faq_s = src.sectie('Vragen')
    items = [(tekst(d.summary), inner(d.p)) for d in faq_s.select('.m-faq details')]
    ld = '\n'.join('<script type="application/ld+json">\n%s\n</script>' % j for j in src.jsonld())
    faq = M('m-sec m-sec--grey m-faqsec',
        EYE('Vragen'),
        H(tekst(faq_s.h2), 'h2', color=INK),
        FAQ(items),
        HTML('<!-- Gestructureerde gegevens: vacature (JobPosting) en de vragen hierboven (FAQPage).\n'
             '     Wijzig je een vraag of antwoord, pas dan ook de FAQPage hieronder aan, anders\n'
             '     verschilt de zichtbare tekst van wat Google leest. Bron: elementor/bron/faq_jsonld.py -->\n'
             + ld, 'zw-jsonld'))

    # Bellen of mailen
    call_s = src.soup.select_one('.m-call')
    bellen = M('m-sec m-sec--grey',
        C('m-call',
          H(tekst(call_s.h3), 'h3', 'm-call-h', WHITE),
          B(tekst(call_s.a), call_s.a['href'], 'yellow', 'zw-btn--block'),
          T('<p>%s</p>' % inner(call_s.select_one('.m-call__mail')), 'm-call-mail')))

    bar_s = src.soup.select_one('.zw-mobilebar a')
    mobilebar = C('zwl zw-mobilebar', B(tekst(bar_s), bar_s['href'], cls='zw-btn--block'))

    page = {'title': cfg['title'], 'slug': cfg['slug'],
            'elements': [hero, form, job, voordelen, taken, profiel, dag, over, getuigen, faq, bellen, mobilebar],
            'settings': {'hide_title': 'yes', 'template': 'default', 'custom_css': pagina_css()},
            'yoast': {'title': tekst(src.soup.title), 'metadesc': src.meta('description')},
            'controle': {'faq': items, 'jsonld': src.jsonld()}}
    if cfg.get('id'):
        page['id'] = cfg['id']
    return page


def nest(elements, top=True):
    for e in elements:
        if e['elType'] == 'container':
            e['isInner'] = not top
        nest(e['elements'], top=False)


def main():
    os.makedirs(BUILD, exist_ok=True)
    alle = {}
    for cfg in PAGINAS.values():
        _, imgs = Bron(cfg['bron']).film_shots()
        for k, v in imgs.items():
            alle.setdefault(k, set()).update(v)
    n = bouw_static(alle)
    versies = {f: versie(os.path.join(STATIC, 'js', f)) for f in ('apply-form.js', 'film.js')}
    print('static:', n, "foto's,", versies)
    for i, (sleutel, cfg) in enumerate(sorted(PAGINAS.items())):
        _rng.seed(5000 + i)  # vaste id's: opnieuw bouwen geeft dezelfde elementen
        p = pagina(cfg, versies)
        nest(p['elements'])
        uit = os.path.join(BUILD, 'pagina-%s.json' % sleutel)
        with open(uit, 'w', encoding='utf-8') as f:
            json.dump(p, f, ensure_ascii=False, indent=1)
        print(os.path.basename(uit), len(json.dumps(p)), 'bytes')


if __name__ == '__main__':
    main()
