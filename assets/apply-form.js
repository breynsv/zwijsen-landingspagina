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
     Definitieve tekst van Jana (copywriter), 28 oktober 2026: vier vragen.
     Soorten:
       - keuzevraag (options) — gaat na een klik meteen door;
       - meerkeuzevraag (multi: true) — selectievakjes, minstens één verplicht,
         door met de knop Volgende. Een optie met `other: true` toont een
         tekstvak ("Een andere taal:") dat dan ook ingevuld moet zijn.
     Feedback Jana + Katrien 30 okt 2026: geen icoontjes en geen A/B/C-letters
     meer (te dicht bij bestaande campagnes); de taalvraag is niet langer
     de eerste taal maar "in welke talen kun je overleggen", meerdere antwoorden.
       - tekstvraag (type: 'text') — verplicht in te vullen.
     ---------------------------------------------------------------------- */

  var QUESTIONS = [
    {
      name: 'werkervaring',
      label: 'Hoeveel relevante werkervaring heb je?',
      options: [
        { value: '1-2', label: '1-2 jaar' },
        { value: '3-4', label: '3-4 jaar' },
        { value: '5-10', label: '5-10 jaar' },
        { value: '10+', label: '+10 jaar' },
        { value: 'geen', label: 'Geen werkervaring' }
      ]
    },
    {
      name: 'talen',
      multi: true,
      label: 'In welke talen kun je overleggen met collega\u2019s en veiligheidsinstructies begrijpen?',
      hint: 'Je mag meerdere talen aanduiden.',
      error: 'Duid minstens \u00E9\u00E9n taal aan om verder te gaan.',
      otherError: 'Vul in welke andere taal je spreekt.',
      options: [
        { value: 'nederlands', label: 'Nederlands' },
        { value: 'frans', label: 'Frans' },
        { value: 'engels', label: 'Engels' },
        { value: 'andere', label: 'Een andere taal:', other: true,
          otherLabel: 'Welke andere taal?', otherPlaceholder: 'Welke taal?' }
      ]
    },
    {
      name: 'rijbewijs',
      label: 'Heb je een rijbewijs?',
      options: [
        { value: 'b', label: 'Rijbewijs B' },
        { value: 'be', label: 'Rijbewijs BE' },
        { value: 'ce', label: 'Rijbewijs C(E)' },
        { value: 'nee', label: 'Nee' }
      ]
    },
    {
      name: 'woonplaats',
      type: 'text',
      label: 'Waar woon je?',
      hint: 'Vul je postcode of de naam van je gemeente in.',
      placeholder: 'bv. 9200 of Dendermonde',
      autocomplete: 'address-level2',
      error: 'Vul je postcode of gemeente in om verder te gaan.'
    }
  ];

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

    /* Rustige voortgang: een dunne lijn en "Vraag 2 van 4" in gewone tekst. */
    var stepText = this.done ? '' : this.step < QUESTIONS.length
      ? 'Vraag ' + (this.step + 1) + ' van ' + QUESTIONS.length
      : 'Laatste stap';
    var head = el('div', { class: 'za__head' + (this.done ? ' is-done' : '') }, [
      el('span', { class: 'za__step', text: stepText }),
      el('div', { class: 'za__bar', role: 'progressbar', 'aria-valuemin': '0',
        'aria-valuemax': '100', 'aria-valuenow': String(this.done ? 100 : this.progress()),
        'aria-label': 'Voortgang sollicitatie' }, [
        el('span', { class: 'za__bar-fill', style: 'width:' + (this.done ? 100 : Math.max(this.progress(), 3)) + '%' })
      ])
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
    fs.appendChild(el('legend', { class: 'za__legend', text: q.label }));

    if (q.hint) fs.appendChild(el('p', { class: 'za__hint', id: group + '_hint', text: q.hint }));

    if (q.type === 'text') {
      /* Tekstvraag (woonplaats): verplicht, Enter = Volgende. */
      var wrap = el('div', { class: 'za__field za__field--full za__textq' });
      wrap.appendChild(el('label', { class: 'zw-visually-hidden', for: 'za_' + q.name, text: q.label }));
      wrap.appendChild(el('input', {
        class: 'za__input', type: 'text', id: 'za_' + q.name, name: q.name,
        autocomplete: q.autocomplete || 'on', placeholder: q.placeholder || '',
        required: 'required', 'aria-describedby': q.hint ? group + '_hint' : null,
        value: this.answers[q.name] || '',
        oninput: function (e) { self.answers[q.name] = e.target.value; self.clearError(); },
        onkeydown: function (e) { if (e.key === 'Enter') { e.preventDefault(); self.next(); } }
      }));
      fs.appendChild(wrap);
    } else {
      var multi = !!q.multi;
      var list = el('div', { class: 'za__options' + (multi ? ' za__options--multi' : ''),
        role: multi ? 'group' : 'radiogroup' });
      var otherBox = null;
      var isOn = function (v) {
        var a = self.answers[q.name];
        return multi ? (a || []).indexOf(v) > -1 : a === v;
      };
      q.options.forEach(function (o) {
        var id = group + '_' + o.value;
        var label;
        var input = el('input', {
          class: 'za__radio', type: multi ? 'checkbox' : 'radio', name: group, id: id, value: o.value,
          'aria-describedby': q.hint ? group + '_hint' : null,
          onchange: function () {
            self.clearError();
            if (multi) {
              /* Meerkeuze: aan/uit, en pas door met de knop Volgende. */
              var a = (self.answers[q.name] || []).filter(function (v) { return v !== o.value; });
              if (input.checked) a.push(o.value);
              self.answers[q.name] = a;
              label.classList.toggle('is-on', input.checked);
              if (o.other) {
                otherBox.hidden = !input.checked;
                if (input.checked) {
                  var t0 = otherBox.querySelector('input');
                  setTimeout(function () { t0.focus(); }, 30);
                }
              }
              return;
            }
            self.answers[q.name] = o.value;
            list.querySelectorAll('.za__option').forEach(function (n) { n.classList.remove('is-on'); });
            label.classList.add('is-on');
            if (otherBox) otherBox.hidden = !o.other;
            if (o.other) {
              var t = otherBox.querySelector('input');
              setTimeout(function () { t.focus(); }, 30);
              return;
            }
            /* Kort uitstel zodat de selectie zichtbaar is voor we doorgaan. */
            setTimeout(function () { self.next(); }, 260);
          }
        });
        if (isOn(o.value)) input.checked = true;

        label = el('label', { class: 'za__option' + (isOn(o.value) ? ' is-on' : ''), for: id }, [
          input,
          el('span', { class: 'za__tick', 'aria-hidden': 'true' }),
          el('span', { class: 'za__option-text', text: o.label })
        ]);
        list.appendChild(label);

        if (o.other) {
          var key = q.name + '_andere';
          otherBox = el('div', { class: 'za__other' });
          otherBox.hidden = !isOn(o.value);
          otherBox.appendChild(el('label', { class: 'zw-visually-hidden', for: 'za_' + key, text: o.otherLabel || o.label }));
          otherBox.appendChild(el('input', {
            class: 'za__input', type: 'text', id: 'za_' + key, name: key,
            placeholder: o.otherPlaceholder || '', autocomplete: 'off',
            value: self.answers[key] || '',
            oninput: function (e) { self.answers[key] = e.target.value; self.clearError(); },
            onkeydown: function (e) { if (e.key === 'Enter') { e.preventDefault(); self.next(); } }
          }));
          list.appendChild(otherBox);
        }
      });
      fs.appendChild(list);
    }
    this.body.appendChild(fs);

    this.body.appendChild(el('p', { class: 'za__error', role: 'alert', 'aria-live': 'polite' }));

    var nav = el('div', { class: 'za__nav' }, [
      this.step > 0 ? el('button', { class: 'za__back', type: 'button', text: 'Terug',
        onclick: function () { self.prev(); } }) : null,
      el('button', { class: 'zw-btn za__next', type: 'button',
        text: 'Volgende',
        onclick: function () { self.next(); } })
    ]);
    this.body.appendChild(nav);
    this.body.appendChild(this.reassure());

    if (!this.started) {
      this.started = true;
      track('SollicitatieGestart', { vacature: 'technieker-algemene-elektriciteit' });
    }
  };

  ZwijsenApply.prototype.reassure = function () {
    /* Jana: onderaan in de grijze letters enkel nog "100% vertrouwelijk". */
    return el('p', { class: 'za__reassure', text: '100% vertrouwelijk' });
  };

  /* ----------------------------------------------------------------------
     Contactstap. De oude kop (dat je "in aanmerking" komt) is weg: de test laat iedereen
     door, en dat sprak "Jouw profiel" tegen (feedback Jana, 30 okt 2026).
     ---------------------------------------------------------------------- */

  var PRIVACY_URL = 'https://zwijsen.be/privacy-policy/';

  ZwijsenApply.prototype.renderContact = function () {
    var self = this;

    var fs = el('fieldset', { class: 'za__fieldset' });
    fs.appendChild(el('legend', { class: 'za__legend', text: 'Bedankt voor je antwoorden! We horen graag meer van je' }));
    fs.appendChild(el('p', { class: 'za__hint',
      text: 'Laat zeker je gegevens na en wij nemen contact met je op.' }));

    /* Honeypot — echte bezoekers zien dit veld niet. */
    fs.appendChild(el('div', { class: 'za__hp', 'aria-hidden': 'true' }, [
      el('label', { for: 'za_website', text: 'Laat dit veld leeg' }),
      el('input', { type: 'text', id: 'za_website', name: 'company_website', tabindex: '-1', autocomplete: 'off' })
    ]));

    var grid = el('div', { class: 'za__grid' });
    grid.appendChild(this.field('naam', 'Je naam *', 'text', { autocomplete: 'name', required: true, full: true }));

    /* Telefoon met landcode */
    var telWrap = el('div', { class: 'za__field za__field--full' });
    telWrap.appendChild(el('label', { class: 'za__label', for: 'za_tel', text: 'Telefoonnummer *' }));
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

    grid.appendChild(this.field('email', 'E-mailadres *', 'email', { autocomplete: 'email', required: true, full: true }));

    fs.appendChild(grid);

    this.body.appendChild(fs);
    this.body.appendChild(el('p', { class: 'za__error', role: 'alert', 'aria-live': 'polite' }));

    this.body.appendChild(el('div', { class: 'za__nav' }, [
      el('button', { class: 'za__back', type: 'button', text: 'Terug',
        onclick: function () { self.prev(); } }),
      el('button', { class: 'zw-btn za__next za__submit', type: 'button',
        text: 'Neem contact met mij op',
        onclick: function (e) { self.submit(e.currentTarget); } })
    ]));

    /* Geen toestemmingsvakje: wie solliciteert vraagt zelf om contact
       (AVG art. 6.1.b). Wel informeren, met de privacyverklaring erbij. */
    this.body.appendChild(el('p', { class: 'za__legal', html:
      'Electro Zwijsen gebruikt je gegevens alleen om je over deze vacature te contacteren. ' +
      'Meer in onze <a href="' + PRIVACY_URL + '" target="_blank" rel="noopener">privacyverklaring</a>.' }));
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
      if (q.type === 'text') {
        if (!(this.answers[q.name] || '').trim()) {
          this.showError(q.error || 'Vul dit in om verder te gaan.');
          var ti = this.body.querySelector('#za_' + q.name);
          if (ti) ti.focus();
          return;
        }
        this.answers[q.name] = this.answers[q.name].trim();
      } else {
        var picked = q.multi ? (this.answers[q.name] || []) : (this.answers[q.name] ? [this.answers[q.name]] : []);
        if (!picked.length) {
          this.showError(q.error || 'Kies een antwoord om verder te gaan.');
          var first = this.body.querySelector('.za__radio');
          if (first && q.multi) first.focus();
          return;
        }
        var chosen = q.options.filter(function (o) { return o.other && picked.indexOf(o.value) > -1; })[0];
        if (chosen) {
          var key = q.name + '_andere';
          if (!(this.answers[key] || '').trim()) {
            this.showError(q.otherError || 'Vul dit in om verder te gaan.');
            var oi = this.body.querySelector('#za_' + key);
            if (oi) oi.focus();
            return;
          }
          this.answers[key] = this.answers[key].trim();
        } else {
          delete this.answers[q.name + '_andere'];
        }
      }
      /* Bewust ALLEEN het stapnummer en de vraagnaam naar Meta/Analytics.
         Het gegeven antwoord (rijbewijs, talen, woonplaats) gaat NIET mee:
         dat is persoonlijke informatie over een sollicitant, en die hoort bij
         Zwijsen thuis — niet bij een advertentieplatform. Voor het meten van
         afhaakpunten volstaat het stapnummer. De antwoorden zitten wel in de
         inzending naar de backend, zie submit(). */
      track('SollicitatieStap', { stap: this.step + 1, vraag: q.name });
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
    var n = this.body.querySelector('.za__legend, .za__done h3');
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
      naam: get('za_naam'),
      email: get('za_email'),
      landcode: get('za_landcode'),
      telefoon: get('za_tel')
    };
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
    if (!data.naam) fail('za_naam', 'Vul je naam in.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(data.email)) fail('za_email', 'Vul een geldig e-mailadres in.');
    if (data.telefoon.replace(/\D/g, '').length < 8) fail('za_tel', 'Vul een geldig telefoonnummer in.');
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
      btn.textContent = 'Neem contact met mij op';
      self.showError('Versturen lukte niet. Probeer opnieuw of bel 015 305 305.');
    });
  };

  /* ----------------------------------------------------------------------
     Bedanktscherm
     ---------------------------------------------------------------------- */

  ZwijsenApply.prototype.renderDone = function () {
    /* Tekst van Jana (30 okt 2026), zonder stappen of opsomming.
       Alleen de voornaam in de aanspreking: eerste woord van het naamveld.
       LET OP: de bevestigingsmail die hier beloofd wordt, bestaat nog niet.
       Die wordt gebouwd bij de overzetting naar WordPress. */
    var naam = ((this.payload && this.payload.naam) || '').trim().split(/\s+/)[0];
    this.body.appendChild(el('div', { class: 'za__done' }, [
      el('div', { class: 'za__done-mark', 'aria-hidden': 'true', html:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" ' +
        'stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' }),
      el('h3', { tabindex: '-1', text: naam ? 'Bedankt, ' + naam + '!' : 'Bedankt!' }),
      el('p', { class: 'za__done-lead', html:
        'We hebben je gegevens goed ontvangen.<br>' +
        'Je krijgt nog een mail van ons ter bevestiging.<br>' +
        'We nemen gauw contact met je op.' })
    ]));
  };

  /* Bevestigd door Jana (30 okt 2026): personeel@zwijsen.be, hetzelfde adres als de
     bestaande formulieren op zwijsen.be. */
  var CONTACT_MAIL = 'personeel@zwijsen.be';

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
    },

    /* Kleefbalk bovenaan — Jana: "formulier als pop-up en bij scrollen
       bovenaan vast". Verschijnt net onder de (kleverige) sitehoofding zodra
       het element `after` uit beeld is, en verdwijnt weer zolang het element
       `hideWhen` (het formulier zelf) in beeld is.
         opts.after       selector — balk pas tonen als dit voorbij is
         opts.hideWhen    selector — balk verbergen als dit in beeld is
         opts.modal       true: knop opent het pop-upformulier ([data-za-open])
         opts.href        anders: knop springt naar dit anker
         opts.desktopOnly true: enkel boven 860px (op mobiel is er de onderbalk) */
    stickyBar: function (opts) {
      opts = opts || {};
      var label = opts.button || 'Solliciteer in 30 seconden';
      var btn = opts.modal
        ? el('button', { class: 'zw-btn za-topbar__btn', type: 'button', 'data-za-open': '', text: label })
        : el('a', { class: 'zw-btn za-topbar__btn', href: opts.href || '#solliciteer', text: label });
      var bar = el('div', { class: 'za-topbar' + (opts.desktopOnly ? ' za-topbar--desktop' : ''),
        role: 'region', 'aria-label': 'Snel solliciteren' }, [
        el('div', { class: 'zw-container za-topbar__inner' }, [
          el('p', { class: 'za-topbar__text', html:
            '<strong>Kom je in aanmerking?</strong> <span>Doe de test.</span>' }),
          btn
        ])
      ]);
      document.body.appendChild(bar);

      /* De hoofding wordt door site-chrome.js pas bij DOMContentLoaded gebouwd
         (en op WordPress door het thema) — dus telkens opnieuw opzoeken. */
      var after = opts.after ? document.querySelector(opts.after) : null;
      var hideWhen = opts.hideWhen ? document.querySelector(opts.hideWhen) : null;

      function update() {
        var header = document.querySelector('.zw-header');
        var top = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
        bar.style.top = top + 'px';
        var past = after ? after.getBoundingClientRect().bottom < top : global.scrollY > 400;
        var formVisible = false;
        if (hideWhen) {
          var r = hideWhen.getBoundingClientRect();
          formVisible = r.top < global.innerHeight && r.bottom > top;
        }
        bar.classList.toggle('is-on', past && !formVisible);
      }
      global.addEventListener('scroll', update, { passive: true });
      global.addEventListener('resize', update);
      update();
      document.addEventListener('DOMContentLoaded', update);
      return bar;
    }
  };

  global.ZwijsenApply = API;

})(window);
