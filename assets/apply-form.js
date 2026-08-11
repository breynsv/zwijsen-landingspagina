/* ==========================================================================
   Electro Zwijsen — sollicitatieflow ("kwalificatieformulier")
   --------------------------------------------------------------------------
   Eén herbruikbare motor voor alle vijf de ontwerpen.

   Opbouw: korte vragen, één per scherm, een voortgangsbalk, en pas op het
   einde de contactgegevens. Dat verlaagt de drempel om te beginnen en houdt
   mensen op gang. Bewust rustig gehouden: geen tellers, geen kunstmatige
   schaarste en geen automatische afwijzing — elke sollicitatie komt binnen,
   met de antwoorden erbij zodat Zwijsen zelf kan beoordelen.

   Gebruik:
     ZwijsenApply.mount('#doelelement', { variant: 'inline' | 'modal' });

   Koppelen aan een echte backend: zie onSubmit() onderaan.
   ========================================================================== */

(function (global) {
  'use strict';

  /* ----------------------------------------------------------------------
     1. De vragen
     Afgeleid van de echte vacature-eisen op zwijsen.be:
     technische richting, eerste ervaring = plus, Nederlands, rijbewijs B,
     VCA-attest, zelfstandig kunnen werken.
     ---------------------------------------------------------------------- */

  var QUESTIONS = [
    {
      name: 'ervaring',
      label: 'Heb je al ervaring met of kennis van elektriciteit?',
      icon: '⚡',
      options: [
        { value: 'werkervaring', label: 'Ja, vanuit werkervaring' },
        { value: 'opleiding', label: 'Ja, vanuit mijn opleiding' },
        { value: 'beide', label: 'Ja, vanuit werkervaring én opleiding' },
        { value: 'geen', label: 'Nog niet, maar ik leer het graag' }
      ]
    },
    {
      name: 'domein',
      label: 'Waar ligt je ervaring vooral?',
      icon: '\u{1F3D7}️',
      hint: 'Geen van beide? Ook prima — je leert bij ons on-the-job.',
      options: [
        { value: 'residentieel', label: 'Residentieel (woningen, appartementen)' },
        { value: 'utiliteit', label: 'Utiliteit (scholen, ziekenhuizen, rusthuizen)' },
        { value: 'industrieel', label: 'Industrieel' },
        { value: 'nvt', label: 'Nog geen ervaring op de werf' }
      ]
    },
    {
      name: 'schemas',
      label: 'Kan je elektrische schema’s en componenten lezen?',
      icon: '\u{1F4D6}',
      options: [
        { value: 'ja', label: 'Ja, vlot' },
        { value: 'basis', label: 'De basis wel' },
        { value: 'nee', label: 'Nee, maar ik wil dat graag leren' }
      ]
    },
    {
      name: 'vca',
      label: 'Heb je een geldig VCA-attest?',
      icon: '\u{1F9BA}',
      hint: 'Nog geen attest? Geen probleem — Zwijsen betaalt en plant je VCA-opleiding.',
      options: [
        { value: 'geldig', label: 'Ja, geldig' },
        { value: 'vervallen', label: 'Gehad, maar vervallen' },
        { value: 'nee', label: 'Nee, nog niet' }
      ]
    },
    {
      name: 'rijbewijs',
      label: 'Welk rijbewijs heb je?',
      icon: '\u{1F697}',
      options: [
        { value: 'b', label: 'Rijbewijs B' },
        { value: 'be', label: 'Rijbewijs BE' },
        { value: 'ce', label: 'Rijbewijs C(E)' },
        { value: 'geen', label: '(Nog) geen rijbewijs' }
      ]
    },
    {
      name: 'nederlands',
      label: 'Hoe goed is je Nederlands?',
      icon: '\u{1F5E3}️',
      hint: 'Frans is een pluspunt, geen vereiste.',
      options: [
        { value: 'moedertaal', label: 'Moedertaal' },
        { value: 'gevorderd', label: 'Gevorderd' },
        { value: 'basis', label: 'Basis' },
        { value: 'geen', label: 'Ik spreek geen Nederlands' }
      ]
    },
    {
      name: 'regio',
      label: 'Werk je graag in de regio Dendermonde?',
      icon: '\u{1F5FA}️',
      map: true,
      options: [
        { value: 'ja', label: 'Ja, dat ligt goed voor mij' },
        { value: 'bespreekbaar', label: 'Bespreekbaar' },
        { value: 'nee', label: 'Liever een andere regio' }
      ]
    },
    {
      name: 'start',
      label: 'Wanneer zou je kunnen starten?',
      icon: '\u{1F5D3}️',
      options: [
        { value: 'direct', label: 'Zo goed als direct' },
        { value: 'maand', label: 'Binnen de maand' },
        { value: 'kwartaal', label: 'Over 1 tot 3 maanden' },
        { value: 'orienteren', label: 'Ik kijk voorlopig rond' }
      ]
    }
  ];

  /* Gemeenten in de wervingsregio — getoond bij de regiovraag. */
  var REGIO = ['Dendermonde', 'Lebbeke', 'Buggenhout', 'Zele', 'Berlare',
    'Waasmunster', 'Hamme', 'Wichelen', 'Laarne', 'Bornem', 'Puurs-Sint-Amands', 'Willebroek'];

  var LANDCODES = [
    ['BE', '\u{1F1E7}\u{1F1EA} +32'], ['NL', '\u{1F1F3}\u{1F1F1} +31'],
    ['FR', '\u{1F1EB}\u{1F1F7} +33'], ['DE', '\u{1F1E9}\u{1F1EA} +49'],
    ['LU', '\u{1F1F1}\u{1F1FA} +352'], ['PL', '\u{1F1F5}\u{1F1F1} +48'],
    ['PT', '\u{1F1F5}\u{1F1F9} +351'], ['RO', '\u{1F1F7}\u{1F1F4} +40']
  ];

  /* ----------------------------------------------------------------------
     2. Hulpjes
     ---------------------------------------------------------------------- */

  function el(tag, attrs, kids) {
    var n = document.createElement(tag);
    attrs = attrs || {};
    Object.keys(attrs).forEach(function (k) {
      if (k === 'class') n.className = attrs[k];
      else if (k === 'text') n.textContent = attrs[k];
      else if (k === 'html') n.innerHTML = attrs[k];
      else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), attrs[k]);
      else if (attrs[k] !== null && attrs[k] !== undefined) n.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) n.appendChild(c); });
    return n;
  }

  var LETTERS = 'ABCDEF';

  /* Meta Pixel / GA4 — faalt stil als er (nog) geen pixel geladen is. */
  function track(event, params) {
    try { if (typeof global.fbq === 'function') global.fbq('trackCustom', event, params || {}); } catch (e) {}
    try { if (typeof global.gtag === 'function') global.gtag('event', event, params || {}); } catch (e) {}
    try { (global.dataLayer = global.dataLayer || []).push(Object.assign({ event: event }, params || {})); } catch (e) {}
  }
  function trackStandard(event, params) {
    try { if (typeof global.fbq === 'function') global.fbq('track', event, params || {}); } catch (e) {}
    try { (global.dataLayer = global.dataLayer || []).push(Object.assign({ event: event }, params || {})); } catch (e) {}
  }

  /* ----------------------------------------------------------------------
     3. De motor
     ---------------------------------------------------------------------- */

  function ZwijsenApply(root, opts) {
    this.root = root;
    this.opts = opts || {};
    this.answers = {};
    this.step = 0;
    this.total = QUESTIONS.length + 1;   /* vragen + contactstap */
    this.started = false;
    this.render();
  }

  ZwijsenApply.prototype.progress = function () {
    return Math.round(((this.step) / this.total) * 100);
  };

  ZwijsenApply.prototype.render = function () {
    this.root.innerHTML = '';
    this.root.classList.add('za');

    var head = el('div', { class: 'za__head' }, [
      el('div', { class: 'za__bar', role: 'progressbar', 'aria-valuemin': '0',
        'aria-valuemax': '100', 'aria-valuenow': String(this.progress()),
        'aria-label': 'Voortgang sollicitatie' }, [
        el('span', { class: 'za__bar-fill', style: 'width:' + Math.max(this.progress(), 4) + '%' })
      ]),
      el('span', { class: 'za__step', text: this.step >= this.total
        ? 'Laatste stap'
        : 'Stap ' + (this.step + 1) + ' van ' + this.total })
    ]);
    this.root.appendChild(head);

    var body = el('div', { class: 'za__body' });
    this.root.appendChild(body);
    this.body = body;

    if (this.done) this.renderDone();
    else if (this.step < QUESTIONS.length) this.renderQuestion(QUESTIONS[this.step]);
    else this.renderContact();
  };

  ZwijsenApply.prototype.renderQuestion = function (q) {
    var self = this;
    var group = 'q_' + q.name;

    var fs = el('fieldset', { class: 'za__fieldset' });
    fs.appendChild(el('legend', { class: 'za__legend',
      html: (q.icon ? '<span class="za__icon" aria-hidden="true">' + q.icon + '</span> ' : '') + q.label }));

    if (q.hint) fs.appendChild(el('p', { class: 'za__hint', text: q.hint }));

    if (q.map) fs.appendChild(this.buildMap());

    var list = el('div', { class: 'za__options' });
    q.options.forEach(function (o, i) {
      var id = group + '_' + o.value;
      var input = el('input', {
        class: 'za__radio', type: 'radio', name: group, id: id, value: o.value,
        onchange: function () {
          self.answers[q.name] = o.value;
          list.querySelectorAll('.za__option').forEach(function (n) { n.classList.remove('is-on'); });
          label.classList.add('is-on');
          self.clearError();
          /* Kort uitstel zodat de selectie zichtbaar is voor we doorgaan. */
          setTimeout(function () { self.next(); }, 260);
        }
      });
      if (self.answers[q.name] === o.value) input.checked = true;

      var label = el('label', { class: 'za__option' + (self.answers[q.name] === o.value ? ' is-on' : ''), for: id }, [
        input,
        el('span', { class: 'za__letter', 'aria-hidden': 'true', text: LETTERS[i] || '+' }),
        el('span', { class: 'za__option-text', text: o.label })
      ]);
      list.appendChild(label);
    });
    fs.appendChild(list);
    this.body.appendChild(fs);

    this.body.appendChild(el('p', { class: 'za__error', role: 'alert', 'aria-live': 'polite' }));

    var nav = el('div', { class: 'za__nav' }, [
      this.step > 0 ? el('button', { class: 'za__back', type: 'button', text: '← Terug',
        onclick: function () { self.prev(); } }) : null,
      el('button', { class: 'zw-btn za__next', type: 'button',
        html: 'Volgende <span aria-hidden="true">→</span>',
        onclick: function () { self.next(); } })
    ]);
    this.body.appendChild(nav);
    this.body.appendChild(this.reassure());

    if (!this.started) {
      this.started = true;
      track('SollicitatieGestart', { vacature: 'technieker-algemene-elektriciteit' });
    }
  };

  /* Eenvoudige regiokaart — bewust zonder externe kaartbibliotheek,
     zodat de pagina snel blijft en er geen extra cookie-toestemming nodig is. */
  ZwijsenApply.prototype.buildMap = function () {
    var wrap = el('div', { class: 'za__map' });
    wrap.appendChild(el('div', { class: 'za__map-visual', 'aria-hidden': 'true', html:
      '<svg viewBox="0 0 320 150" role="presentation">' +
      '<defs><radialGradient id="zaGlow" cx="50%" cy="50%" r="50%">' +
      '<stop offset="0%" stop-color="#E60E13" stop-opacity=".28"/>' +
      '<stop offset="100%" stop-color="#E60E13" stop-opacity="0"/></radialGradient></defs>' +
      '<rect width="320" height="150" fill="#EFEDEE"/>' +
      '<path d="M0 96 L52 84 L104 98 L156 76 L208 92 L262 72 L320 88 L320 150 L0 150Z" fill="#DDD9DB"/>' +
      '<path d="M0 118 L60 110 L128 122 L196 104 L268 118 L320 108 L320 150 L0 150Z" fill="#CFCCCE"/>' +
      '<circle cx="160" cy="72" r="58" fill="url(#zaGlow)"/>' +
      '<circle cx="160" cy="72" r="30" fill="none" stroke="#E60E13" stroke-width="2" stroke-dasharray="5 4"/>' +
      '<circle cx="160" cy="72" r="7" fill="#E60E13"/>' +
      '<circle cx="160" cy="72" r="13" fill="none" stroke="#E60E13" stroke-width="2" opacity=".45"/>' +
      '<text x="160" y="104" text-anchor="middle" font-family="Roboto,sans-serif" font-size="13" font-weight="700" fill="#121413">Dendermonde</text>' +
      '</svg>' }));
    wrap.appendChild(el('p', { class: 'za__map-caption',
      text: 'Werven in en rond: ' + REGIO.join(' · ') }));
    return wrap;
  };

  ZwijsenApply.prototype.reassure = function () {
    return el('ul', { class: 'za__reassure' }, [
      el('li', { text: 'Duurt 60 seconden' }),
      el('li', { text: 'CV niet verplicht' }),
      el('li', { text: '100% vertrouwelijk' })
    ]);
  };

  /* ----------------------------------------------------------------------
     Contactstap
     ---------------------------------------------------------------------- */

  ZwijsenApply.prototype.renderContact = function () {
    var self = this;

    var fs = el('fieldset', { class: 'za__fieldset' });
    fs.appendChild(el('legend', { class: 'za__legend', text: 'Waar mogen we je bereiken?' }));
    fs.appendChild(el('p', { class: 'za__hint',
      text: 'Je krijgt binnen 1 werkdag antwoord van Katrien, rechtstreeks. Geen wachtrij, geen tussenpersoon.' }));

    /* Honeypot — echte bezoekers zien dit veld niet. */
    fs.appendChild(el('div', { class: 'za__hp', 'aria-hidden': 'true' }, [
      el('label', { for: 'za_website', text: 'Laat dit veld leeg' }),
      el('input', { type: 'text', id: 'za_website', name: 'company_website', tabindex: '-1', autocomplete: 'off' })
    ]));

    var grid = el('div', { class: 'za__grid' });
    grid.appendChild(this.field('voornaam', 'Voornaam', 'text', { autocomplete: 'given-name', required: true }));
    grid.appendChild(this.field('achternaam', 'Achternaam', 'text', { autocomplete: 'family-name', required: true }));
    grid.appendChild(this.field('email', 'E-mailadres', 'email', { autocomplete: 'email', required: true, full: true }));

    /* Telefoon met landcode */
    var telWrap = el('div', { class: 'za__field za__field--full' });
    telWrap.appendChild(el('label', { class: 'za__label', for: 'za_tel', text: 'Telefoon' }));
    var telRow = el('div', { class: 'za__telrow' });
    var sel = el('select', { class: 'za__select', id: 'za_landcode', name: 'landcode',
      'aria-label': 'Landcode' });
    LANDCODES.forEach(function (c) { sel.appendChild(el('option', { value: c[0], text: c[1] })); });
    telRow.appendChild(sel);
    telRow.appendChild(el('input', { class: 'za__input', type: 'tel', id: 'za_tel', name: 'telefoon',
      autocomplete: 'tel-national', inputmode: 'tel', placeholder: '0470 12 34 56', required: 'required' }));
    telWrap.appendChild(telRow);
    telWrap.appendChild(el('p', { class: 'za__field-error', 'aria-live': 'polite' }));
    grid.appendChild(telWrap);

    fs.appendChild(grid);

    /* CV — optioneel, precies zoals in de referentie */
    var fileWrap = el('div', { class: 'za__file' });
    fileWrap.appendChild(el('input', { type: 'file', id: 'za_cv', name: 'cv',
      accept: '.pdf,.doc,.docx,.jpg,.png', class: 'zw-visually-hidden',
      onchange: function (e) {
        var f = e.target.files && e.target.files[0];
        fileLabel.querySelector('.za__file-text').textContent = f ? f.name : 'CV toevoegen (optioneel)';
        fileWrap.classList.toggle('is-set', !!f);
      } }));
    var fileLabel = el('label', { class: 'za__file-label', for: 'za_cv' }, [
      el('span', { class: 'za__file-icon', 'aria-hidden': 'true', text: '\u{1F4CE}' }),
      el('span', { class: 'za__file-text', text: 'CV toevoegen (optioneel)' })
    ]);
    fileWrap.appendChild(fileLabel);
    fs.appendChild(fileWrap);

    /* GDPR — expliciete, niet vooraf aangevinkte toestemming */
    var consentWrap = el('div', { class: 'za__consent' });
    consentWrap.appendChild(el('input', { type: 'checkbox', id: 'za_consent', name: 'consent', required: 'required' }));
    consentWrap.appendChild(el('label', { for: 'za_consent', html:
      'Ik ga ermee akkoord dat Electro Zwijsen mijn gegevens bijhoudt om deze sollicitatie te behandelen. ' +
      '<a href="https://zwijsen.be/privacy-verklaring/" target="_blank" rel="noopener">Privacyverklaring</a>.' }));
    fs.appendChild(consentWrap);
    fs.appendChild(el('p', { class: 'za__field-error za__consent-error', 'aria-live': 'polite' }));

    this.body.appendChild(fs);
    this.body.appendChild(el('p', { class: 'za__error', role: 'alert', 'aria-live': 'polite' }));

    this.body.appendChild(el('div', { class: 'za__nav' }, [
      el('button', { class: 'za__back', type: 'button', text: '← Terug',
        onclick: function () { self.prev(); } }),
      el('button', { class: 'zw-btn za__next za__submit', type: 'button',
        text: 'Verstuur mijn sollicitatie',
        onclick: function (e) { self.submit(e.currentTarget); } })
    ]));

    this.body.appendChild(el('p', { class: 'za__legal',
      text: 'Je gegevens worden vertrouwelijk verwerkt en niet gedeeld met derden.' }));
  };

  ZwijsenApply.prototype.field = function (name, label, type, o) {
    o = o || {};
    var wrap = el('div', { class: 'za__field' + (o.full ? ' za__field--full' : '') });
    wrap.appendChild(el('label', { class: 'za__label', for: 'za_' + name, text: label }));
    wrap.appendChild(el('input', {
      class: 'za__input', type: type, id: 'za_' + name, name: name,
      autocomplete: o.autocomplete || 'on',
      required: o.required ? 'required' : null,
      value: this.answers[name] || ''
    }));
    wrap.appendChild(el('p', { class: 'za__field-error', 'aria-live': 'polite' }));
    return wrap;
  };

  /* ----------------------------------------------------------------------
     Navigatie
     ---------------------------------------------------------------------- */

  ZwijsenApply.prototype.showError = function (msg) {
    var n = this.body.querySelector('.za__error');
    if (n) { n.textContent = msg; n.classList.add('is-on'); }
  };
  ZwijsenApply.prototype.clearError = function () {
    var n = this.body.querySelector('.za__error');
    if (n) { n.textContent = ''; n.classList.remove('is-on'); }
  };

  ZwijsenApply.prototype.next = function () {
    if (this.step < QUESTIONS.length) {
      var q = QUESTIONS[this.step];
      if (!this.answers[q.name]) { this.showError('Kies een antwoord om verder te gaan.'); return; }
      track('SollicitatieStap', { stap: this.step + 1, vraag: q.name, antwoord: this.answers[q.name] });
    }
    this.step = Math.min(this.step + 1, this.total);
    this.render();
    this.focusFirst();
  };

  ZwijsenApply.prototype.prev = function () {
    this.step = Math.max(0, this.step - 1);
    this.render();
    this.focusFirst();
  };

  ZwijsenApply.prototype.focusFirst = function () {
    var n = this.body.querySelector('.za__legend');
    if (n) { n.setAttribute('tabindex', '-1'); n.focus({ preventScroll: true }); }
    var box = this.root.closest('[data-za-scroll]') || this.root;
    if (box.scrollIntoView) box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  };

  /* ----------------------------------------------------------------------
     Verzenden
     ---------------------------------------------------------------------- */

  ZwijsenApply.prototype.submit = function (btn) {
    var self = this;
    this.clearError();
    this.body.querySelectorAll('.za__field-error').forEach(function (n) { n.textContent = ''; });

    var get = function (id) { var n = self.body.querySelector('#' + id); return n ? n.value.trim() : ''; };
    var data = {
      voornaam: get('za_voornaam'),
      achternaam: get('za_achternaam'),
      email: get('za_email'),
      landcode: get('za_landcode'),
      telefoon: get('za_tel')
    };
    var consent = this.body.querySelector('#za_consent').checked;
    var honeypot = this.body.querySelector('#za_website').value;

    var bad = false;
    function fail(id, msg) {
      var input = self.body.querySelector('#' + id);
      var slot = input.closest('.za__field').querySelector('.za__field-error');
      if (slot) slot.textContent = msg;
      input.classList.add('is-bad');
      input.setAttribute('aria-invalid', 'true');
      if (!bad) input.focus();
      bad = true;
    }
    if (!data.voornaam) fail('za_voornaam', 'Vul je voornaam in.');
    if (!data.achternaam) fail('za_achternaam', 'Vul je achternaam in.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(data.email)) fail('za_email', 'Vul een geldig e-mailadres in.');
    if (data.telefoon.replace(/\D/g, '').length < 8) fail('za_tel', 'Vul een geldig telefoonnummer in.');
    if (!consent) {
      this.body.querySelector('.za__consent-error').textContent = 'Je moet akkoord gaan om te kunnen versturen.';
      bad = true;
    }
    if (bad) { this.showError('Even nakijken — er ontbreekt nog iets.'); return; }

    /* Bot gedetecteerd: doe alsof alles lukte, verstuur niets. */
    if (honeypot) { this.done = true; this.render(); return; }

    var payload = Object.assign({}, this.answers, data, {
      vacature: 'Technieker Algemene Elektriciteit',
      regio_campagne: 'Dendermonde',
      bron: (global.location && global.location.search) || '',
      tijdstip: new Date().toISOString()
    });

    btn.disabled = true;
    btn.textContent = 'Versturen…';

    /* --------------------------------------------------------------
       KOPPELPUNT — hier gaat de inzending naar de backend.
       Zie README: WP Forms / Fluent Forms / Make / n8n / eigen endpoint.
       De Lead-event blijft hoe dan ook vuren zodat Meta kan optimaliseren.
       -------------------------------------------------------------- */
    var endpoint = self.opts.endpoint;
    var finish = function () {
      trackStandard('Lead', {
        content_name: 'Technieker Algemene Elektriciteit',
        content_category: 'Vacature',
        value: 1, currency: 'EUR'
      });
      track('SollicitatieVoltooid', { vacature: 'technieker-algemene-elektriciteit' });
      self.done = true;
      self.payload = payload;
      self.render();
    };

    if (!endpoint) { setTimeout(finish, 550); return; }   /* demo-modus */

    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(finish).catch(function () {
      btn.disabled = false;
      btn.textContent = 'Verstuur mijn sollicitatie';
      self.showError('Versturen lukte niet. Probeer opnieuw of bel 015 305 305.');
    });
  };

  /* ----------------------------------------------------------------------
     Bedanktscherm
     ---------------------------------------------------------------------- */

  ZwijsenApply.prototype.renderDone = function () {
    var naam = (this.payload && this.payload.voornaam) || '';
    this.body.appendChild(el('div', { class: 'za__done' }, [
      el('div', { class: 'za__done-mark', 'aria-hidden': 'true', html:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" ' +
        'stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' }),
      el('h3', { text: naam ? 'Bedankt, ' + naam + '!' : 'Bedankt voor je sollicitatie!' }),
      el('p', { class: 'za__done-lead',
        text: 'We hebben je sollicitatie goed ontvangen. Er is net een bevestigingsmail naar je onderweg.' }),
      el('ol', { class: 'za__steps' }, [
        el('li', {}, [el('strong', { text: 'Bevestiging per mail' }),
          el('span', { text: 'Meteen in je inbox, met wie je contacteert en wanneer.' })]),
        el('li', {}, [el('strong', { text: 'Telefoontje binnen 1 werkdag' }),
          el('span', { text: 'Een gesprek van 10 minuten. Geen druk, gewoon kennismaken.' })]),
        el('li', {}, [el('strong', { text: 'Langskomen in Duffel' }),
          el('span', { text: 'Je ziet het atelier, het materiaal en je toekomstige ploeg.' })]),
        el('li', {}, [el('strong', { text: 'Duidelijk antwoord' }),
          el('span', { text: 'Altijd een antwoord — ook als het geen match is.' })])
      ]),
      el('p', { class: 'za__legal',
        text: 'Liever meteen bellen? 015 305 305 — vraag naar de personeelsdienst.' })
    ]));
  };

  /* ----------------------------------------------------------------------
     4. Publieke API
     ---------------------------------------------------------------------- */

  var API = {
    QUESTIONS: QUESTIONS,
    mount: function (target, opts) {
      var node = typeof target === 'string' ? document.querySelector(target) : target;
      if (!node) return null;
      return new ZwijsenApply(node, opts);
    },
    /* Modale variant: elke [data-za-open] knop opent het formulier. */
    mountModal: function (opts) {
      var overlay = el('div', { class: 'za-modal', hidden: 'hidden' });
      var panel = el('div', { class: 'za-modal__panel', role: 'dialog', 'aria-modal': 'true',
        'aria-label': 'Sollicitatieformulier', 'data-za-scroll': '' });
      var close = el('button', { class: 'za-modal__close', type: 'button', 'aria-label': 'Sluiten', html: '&times;' });
      var host = el('div');
      panel.appendChild(close);
      panel.appendChild(host);
      overlay.appendChild(panel);
      document.body.appendChild(overlay);

      var app = new ZwijsenApply(host, opts);
      var lastFocus = null;

      function open(trigger) {
        lastFocus = trigger || document.activeElement;
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        trackStandard('ViewContent', { content_name: 'Sollicitatieformulier geopend' });
        requestAnimationFrame(function () {
          overlay.classList.add('is-on');
          app.focusFirst();
        });
      }
      function shut() {
        overlay.classList.remove('is-on');
        document.body.style.overflow = '';
        setTimeout(function () { overlay.hidden = true; }, 220);
        if (lastFocus && lastFocus.focus) lastFocus.focus();
      }

      close.addEventListener('click', shut);
      overlay.addEventListener('click', function (e) { if (e.target === overlay) shut(); });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !overlay.hidden) shut();
      });
      document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-za-open]');
        if (t) { e.preventDefault(); open(t); }
      });

      return { open: open, close: shut, app: app };
    }
  };

  global.ZwijsenApply = API;

})(window);
