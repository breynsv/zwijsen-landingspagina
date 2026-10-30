/* ==========================================================================
   De fotofilm — speler. Gedeeld door de filmvarianten van voorstel 2 en 5.

   ZwijsenFilm.mount(sectie, {
     base:      'assets/img/jana/',
     shots:     [{ img, w:[480, 800, …], sizes, pos, from:[schaal, x%, y%], to:[...],
                   soft?: true, kicker, line }],
                img: bestandsnaam zonder breedte; er bestaan <img>-<w>.avif en .webp
                (zie copy/VERSLAG-originelen-2026-10-30.md). De oude vorm
                { src, srcset? } werkt nog.
     duration:  5500,        ms per shot
     still:     0,           welke shot bij "minder beweging"
     lazyStart: false,       true: pas laden als de film in de buurt komt
     onShot:    function (i) {}
   })
   -> { pause(), play(), hold(reden, aan), index() }

   Gedrag:
   - Alleen de eerste shot laadt meteen (of pas vlak voor beeld, met lazyStart);
     elke volgende laadt halverwege de vorige.
   - Pauzeknop (WCAG 2.2.2). De film staat ook stil zolang hij uit beeld is,
     het tabblad verborgen is, of de pagina een reden doorgeeft via hold().
   - "Minder beweging": één stilstaand beeld met zijn ondertitel.
   - soft: foto's die op groot scherm al uitvergroot worden. Hun zoom blijft binnen 8%.
   - Elke shot krijgt AVIF met WebP als terugval, op de breedte die het scherm
     nodig heeft (srcset + sizes).
   - De verschuiving wordt begrensd, zodat er nooit een rand zichtbaar wordt.
   ========================================================================== */
(function (global) {
  'use strict';

  var ICON_PAUSE = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>';
  var ICON_PLAY = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
  var FADE = 1800;

  /* scale(s) translate(t%): de verschuiving telt mee met de schaal. Zonder rand
     mag s*|t| hoogstens de helft van de overmaat zijn: |t| <= 50(s-1)/s. */
  function veilig(v, soft) {
    var s = Math.max(1, soft ? Math.min(v[0], 1.08) : v[0]);
    var m = 50 * (s - 1) / s;
    var c = function (t) { return Math.max(-m, Math.min(m, t)); };
    return 'scale(' + s + ') translate(' + c(v[1]).toFixed(2) + '%,' + c(v[2]).toFixed(2) + '%)';
  }

  function mount(root, o) {
    if (typeof root === 'string') root = document.querySelector(root);
    if (!root) return null;
    var base = o.base || '';
    var SHOTS = o.shots, DUUR = o.duration || 5500;
    var stil = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var holder = root.querySelector('.film__shots');
    var progress = root.querySelector('.film__progress');
    var kicker = root.querySelector('.film__kicker');
    var line = root.querySelector('.film__line');
    var btn = root.querySelector('.film__pause');

    /* Shots opbouwen. Een <img> die al in de HTML staat (voor de LCP) wordt
       hergebruikt als eerste shot. */
    var bestaand = holder.querySelector('img');
    var els = SHOTS.map(function (s, i) {
      var wrap, img;
      if (i === 0 && bestaand) {
        img = bestaand;
        wrap = img.closest('.film__shot') || img.parentNode;
      } else {
        wrap = document.createElement('div');
        wrap.className = 'film__shot';
        img = document.createElement('img');
        img.alt = '';                       /* sfeerbeeld; de ondertitel draagt de inhoud */
        img.decoding = 'async';
        if (s.img) {
          var pic = document.createElement('picture');
          var bron = document.createElement('source');
          bron.type = 'image/avif';
          pic.appendChild(bron); pic.appendChild(img);
          wrap.appendChild(pic);
        } else {
          wrap.appendChild(img);
        }
        holder.appendChild(wrap);
      }
      img.style.objectPosition = s.pos || '50% 50%';
      var tick = null;
      if (progress) {
        var t = document.createElement('div');
        t.className = 'film__tick';
        t.appendChild(document.createElement('span'));
        progress.appendChild(t);
        tick = t;
      }
      return { wrap: wrap, img: img, tick: tick, anim: null, bar: null };
    });

    function laad(i) {
      var e = els[i], s = SHOTS[i];
      if (e.img.getAttribute('src')) return;
      if (s.img) {
        var lijst = function (ext) {
          return s.w.map(function (w) { return base + s.img + '-' + w + '.' + ext + ' ' + w + 'w'; }).join(', ');
        };
        var bron = e.img.parentNode.querySelector('source');
        if (bron) { bron.sizes = s.sizes || '100vw'; bron.srcset = lijst('avif'); }
        e.img.sizes = s.sizes || '100vw';
        e.img.srcset = lijst('webp');
        e.img.src = base + s.img + '-' + (s.w.indexOf(800) > -1 ? 800 : s.w[s.w.length - 1]) + '.webp';
        return;
      }
      if (s.srcset) { e.img.sizes = s.sizes || '100vw'; e.img.srcset = s.srcset; }
      e.img.src = base + s.src;
    }

    function zetTekst(i) {
      if (!kicker || !line) return;
      kicker.textContent = SHOTS[i].kicker || '';
      line.textContent = SHOTS[i].line || '';
    }

    /* --- Minder beweging: één still -------------------------------------- */
    if (stil) {
      var k = o.still || 0;
      els.forEach(function (e, i) { if (i !== k) e.wrap.classList.remove('is-on'); });
      var toonStil = function () {
        laad(k);
        els[k].wrap.classList.add('is-on');
        zetTekst(k);
        kicker && kicker.classList.add('is-on');
        line && line.classList.add('is-on');
      };
      if (o.lazyStart) wachtOpNabij(toonStil); else toonStil();
      root.classList.add('is-still');
      return { pause: function () {}, play: function () {}, hold: function () {}, index: function () { return k; } };
    }

    /* --- Afspelen ------------------------------------------------------- */
    var huidig = -1, timer = null, voorlader = null, rest = DUUR, gestart = 0;
    var holds = {}, gebruikerPauze = false, begonnen = false, klaar = false;

    function speelt() {
      if (gebruikerPauze) return false;
      for (var h in holds) if (holds[h]) return false;
      return true;
    }

    function toon(i) {
      var vorige = els[huidig];
      huidig = i % els.length;
      var e = els[huidig], s = SHOTS[huidig];
      laad(huidig);
      /* Volgende shot halverwege deze laden: niet meteen, zodat wie snel op de
         knop drukt die bytes nooit binnenhaalt; wel ruim op tijd voor de overgang. */
      clearTimeout(voorlader);
      var volgende = (huidig + 1) % els.length;
      voorlader = setTimeout(function () { laad(volgende); }, DUUR / 2);

      if (vorige && vorige !== e) {
        if (vorige.bar) { vorige.bar.cancel(); vorige.bar = null; }
        if (vorige.tick) vorige.tick.classList.add('is-done');
        setTimeout(function () { vorige.wrap.classList.remove('is-on'); }, 50);
      }
      if (huidig === 0) els.forEach(function (x) {   /* nieuwe ronde: voortgang leeg */
        if (x.bar) { x.bar.cancel(); x.bar = null; }
        if (x.tick) x.tick.classList.remove('is-done');
      });
      e.wrap.classList.add('is-on');

      if (e.anim) e.anim.cancel();
      /* De beweging loopt door tot na de overvloeiing: nooit een stilstand. */
      e.anim = e.img.animate([{ transform: veilig(s.from, s.soft) }, { transform: veilig(s.to, s.soft) }],
        { duration: DUUR + FADE, easing: 'cubic-bezier(.35,.1,.35,1)', fill: 'forwards' });
      if (e.tick) e.bar = e.tick.firstChild.animate(
        [{ transform: 'scaleX(0)' }, { transform: 'scaleX(1)' }], { duration: DUUR, fill: 'forwards' });

      if (kicker && line) {
        kicker.classList.remove('is-on'); line.classList.remove('is-on');
        var nu = huidig;
        setTimeout(function () {
          if (nu !== huidig) return;
          zetTekst(nu);
          kicker.classList.add('is-on'); line.classList.add('is-on');
        }, 450);
      }
      if (o.onShot) o.onShot(huidig);

      rest = DUUR;
      if (speelt()) plan(); else bevries();
    }

    function plan() {
      clearTimeout(timer);
      gestart = Date.now();
      timer = setTimeout(function () { toon(huidig + 1); }, Math.max(0, rest));
    }
    function bevries() {
      var e = els[huidig];
      root.classList.add('is-paused');
      if (!e) return;
      e.anim && e.anim.pause();
      e.bar && e.bar.pause();
    }

    var liep = false;
    function bijwerken() {
      var nu = speelt();
      if (!begonnen) {
        if (nu && klaar) { begonnen = true; liep = true; root.classList.add('is-started'); toon(0); }
        return;
      }
      if (nu === liep) return;
      liep = nu;
      var e = els[huidig];
      if (!nu) {
        clearTimeout(timer);
        rest -= Date.now() - gestart;
        bevries();
      } else {
        root.classList.remove('is-paused');
        e.anim && e.anim.play();
        e.bar && e.bar.play();
        plan();
      }
    }

    function zetKnop() {
      if (!btn) return;
      btn.setAttribute('aria-pressed', String(gebruikerPauze));
      btn.setAttribute('aria-label', gebruikerPauze ? 'Film afspelen' : 'Film pauzeren');
      btn.innerHTML = gebruikerPauze ? ICON_PLAY : ICON_PAUSE;
    }
    if (btn) {
      zetKnop();
      btn.addEventListener('click', function () {
        gebruikerPauze = !gebruikerPauze;
        zetKnop();
        bijwerken();
      });
    }

    function hold(reden, aan) { holds[reden] = !!aan; bijwerken(); }

    /* Niet afspelen wat niemand ziet. */
    holds.view = true;
    holds.tab = document.hidden;
    if ('IntersectionObserver' in global) {
      new IntersectionObserver(function (en) {
        hold('view', !en[en.length - 1].isIntersecting);
      }, { threshold: 0.15 }).observe(root);
    } else {
      holds.view = false;
    }
    document.addEventListener('visibilitychange', function () { hold('tab', document.hidden); });

    /* Eerste beeld: meteen, of pas vlak voordat de film in beeld komt. */
    function eersteKlaar() { klaar = true; bijwerken(); }
    function startLaden() {
      laad(0);
      var img = els[0].img;
      if (img.complete && img.naturalWidth) eersteKlaar();
      else {
        img.addEventListener('load', eersteKlaar, { once: true });
        img.addEventListener('error', eersteKlaar, { once: true });
      }
    }
    if (o.lazyStart) wachtOpNabij(startLaden); else startLaden();

    return {
      pause: function () { gebruikerPauze = true; zetKnop(); bijwerken(); },
      play: function () { gebruikerPauze = false; zetKnop(); bijwerken(); },
      hold: hold,
      index: function () { return huidig; }
    };

    function wachtOpNabij(fn) {
      if (!('IntersectionObserver' in global)) { fn(); return; }
      var io = new IntersectionObserver(function (en) {
        if (en.some(function (x) { return x.isIntersecting; })) { io.disconnect(); fn(); }
      }, { rootMargin: '300px 0px' });
      io.observe(root);
    }
  }

  global.ZwijsenFilm = { mount: mount };
})(window);
