/* ==========================================================================
   Electro Zwijsen — hoofding en voettekst van de site
   --------------------------------------------------------------------------
   BELANGRIJK VOOR DE BOUW IN ELEMENTOR
   Op de echte site komen deze twee blokken uit het bestaande thema. Je bouwt
   ze dus NIET na — je maakt de landingspagina gewoon met de standaard header
   en footer van zwijsen.be.

   Dit bestand bestaat alleen zodat de vijf drafts er los van WordPress
   identiek uitzien als de live site. De structuur en maten zijn 1-op-1
   opgemeten op https://zwijsen.be (1440px, 2026-08-11):

     container   1340px, 50px marge      logo        163 x 70 px
     hoogte      90px                    menu        Open Sans 15px/600
     knop        Roboto 18px/500         knopkleur   #FCC715, radius 3px

   Gebruik in een pagina:
     <div data-zw-header data-cta="#solliciteer"></div>
     ...
     <div data-zw-footer></div>
   ========================================================================== */

(function () {
  'use strict';

  var LOGO = 'https://zwijsen.be/wp-content/uploads/2023/09/' +
             'cropped-cropped-FRONNT-Combinatielogo-Elektro-Zwijsen-Kleur.png';

  var CARET = '<svg class="zw-header__caret" viewBox="0 0 10 6" fill="none" aria-hidden="true">' +
              '<path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.6" ' +
              'stroke-linecap="round" stroke-linejoin="round"/></svg>';

  /* Exact de menustructuur van zwijsen.be, inclusief beide uitklapmenu's. */
  var MENU = [
    { label: 'Over ons', href: 'https://zwijsen.be/over-ons/' },
    { label: 'Diensten', href: '#', sub: [
      { label: 'Algemene elektriciteitswerken', href: 'https://zwijsen.be/algemene-elektriciteitswerken/' },
      { label: 'Datanetwerken', href: 'https://zwijsen.be/datanetwerken/' },
      { label: 'Guardtowers', href: 'https://zwijsen.be/guardtowers/' },
      { label: 'Middenspanningsinstallaties', href: 'https://zwijsen.be/middenspanningsinstallaties/' },
      { label: 'Securitywerken', href: 'https://zwijsen.be/securitywerken/' },
      { label: 'Service en onderhoud', href: 'https://zwijsen.be/service-en-onderhoud/' }
    ]},
    { label: 'Realisaties', href: 'https://zwijsen.be/realisaties/' },
    { label: 'Nieuws', href: 'https://zwijsen.be/nieuws/' },
    { label: 'Werken bij', href: '#', sub: [
      { label: 'Electro Zwijsen als Werkgever', href: 'https://zwijsen.be/werken-bij/' },
      { label: 'Open vacatures', href: 'https://zwijsen.be/open-vacatures/' }
    ]},
    { label: 'Contact', href: 'https://zwijsen.be/contact/' }
  ];

  function buildHeader(host) {
    var ctaHref = host.getAttribute('data-cta') || '#solliciteer';
    var ctaText = host.getAttribute('data-cta-text') || 'Solliciteer Nu!';
    /* Opent het formulier als venster in plaats van te scrollen. */
    var ctaModal = host.hasAttribute('data-cta-modal');

    var items = MENU.map(function (m) {
      if (!m.sub) {
        return '<li><a class="zw-header__link" href="' + m.href + '">' + m.label + '</a></li>';
      }
      return '<li>' +
        '<a class="zw-header__link" href="' + m.href + '" aria-expanded="false" aria-haspopup="true">' +
        m.label + CARET + '</a>' +
        '<ul class="zw-header__sub">' +
        m.sub.map(function (s) { return '<li><a href="' + s.href + '">' + s.label + '</a></li>'; }).join('') +
        '</ul></li>';
    }).join('');

    var cta = ctaModal
      ? '<button class="zw-header__cta" type="button" data-za-open>' + ctaText + '</button>'
      : '<a class="zw-header__cta" href="' + ctaHref + '">' + ctaText + '</a>';

    host.outerHTML =
      '<header class="zw-header">' +
        '<div class="zw-header__inner">' +
          '<a class="zw-header__logo" href="https://zwijsen.be/" aria-label="Electro Zwijsen, naar de homepagina">' +
            '<img src="' + LOGO + '" alt="Electro Zwijsen" width="163" height="70">' +
          '</a>' +
          '<button class="zw-header__burger" type="button" aria-label="Menu openen" aria-expanded="false">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="#121413" stroke-width="2" stroke-linecap="round">' +
            '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/>' +
            '<line x1="3" y1="18" x2="21" y2="18"/></svg>' +
          '</button>' +
          '<ul class="zw-header__nav">' + items + '</ul>' +
          cta +
        '</div>' +
      '</header>';
  }

  /* Sociale iconen — dezelfde vijf als op zwijsen.be, in dezelfde volgorde. */
  var SOCIAL = {
    facebook: 'M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.49 0-1.95.93-1.95 1.87v2.25h3.32l-.53 3.49h-2.79V24C19.61 23.1 24 18.1 24 12.07z',
    instagram: 'M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4 1.3-.1 1.7-.1 4.8-.1zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.2.8-.4.3-.6.7-.8 1.2-.2.4-.3 1-.4 2.1C2.7 8.5 2.7 8.9 2.7 12s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.2.3.4.7.6 1.2.8.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.2-.8.4-.3.6-.7.8-1.2.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.8-1.2-.3-.4-.7-.6-1.2-.8-.4-.2-1-.3-2.1-.4C15.5 4 15.1 4 12 4zm0 3.1a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8zm0 8.1a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4zm6.3-8.3a1.15 1.15 0 1 1-2.3 0 1.15 1.15 0 0 1 2.3 0z',
    youtube: 'M21.6 7.2s-.2-1.4-.8-2c-.7-.8-1.5-.8-1.9-.8-2.7-.2-6.9-.2-6.9-.2s-4.2 0-6.9.2c-.4 0-1.2 0-1.9.8-.6.6-.8 2-.8 2S2.2 8.8 2.2 10.5v1.6c0 1.6.2 3.3.2 3.3s.2 1.4.8 2c.7.8 1.7.7 2.1.8 1.5.1 6.7.2 6.7.2s4.2 0 6.9-.2c.4-.1 1.2-.1 1.9-.8.6-.6.8-2 .8-2s.2-1.6.2-3.3v-1.6c0-1.7-.2-3.3-.2-3.3zM9.9 14.6V8.9l5.4 2.9-5.4 2.8z',
    whatsapp: 'M17.5 6.4A7.8 7.8 0 0 0 4.2 12c0 1.4.4 2.7 1 3.9L4.1 20l4.2-1.1a7.8 7.8 0 0 0 11.5-6.8 7.7 7.7 0 0 0-2.3-5.7zM12 18.5c-1.2 0-2.4-.3-3.4-.9l-.2-.1-2.5.6.7-2.4-.2-.3a6.5 6.5 0 1 1 12-3.4 6.5 6.5 0 0 1-6.4 6.5zm3.6-4.8c-.2-.1-1.2-.6-1.3-.6-.2-.1-.3-.1-.4.1s-.5.6-.6.8c-.1.1-.2.1-.4.1-.2-.1-.8-.3-1.6-1-.6-.5-1-1.2-1.1-1.4-.1-.2 0-.3.1-.4l.3-.3.2-.3v-.4c0-.1-.4-1.1-.6-1.5-.1-.4-.3-.3-.4-.3h-.4c-.1 0-.4.1-.6.3-.2.2-.7.7-.7 1.8s.8 2.1.9 2.2c.1.1 1.6 2.4 3.8 3.3.5.2.9.4 1.2.5.5.2 1 .1 1.3.1.4-.1 1.2-.5 1.4-1 .2-.5.2-.9.1-1l-.2-.1z',
    linkedin: 'M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3V9zm6.5 0h3.8v1.7h.1c.5-1 1.8-2 3.7-2 4 0 4.7 2.6 4.7 6V21h-4v-5.6c0-1.3 0-3-1.9-3-1.9 0-2.2 1.5-2.2 2.9V21h-4V9z'
  };

  function socialLink(net, label, href) {
    return '<li><a href="' + href + '" target="_blank" rel="noopener" aria-label="' + label + '">' +
      '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' + SOCIAL[net] + '"/></svg></a></li>';
  }

  function buildFooter(host) {
    host.outerHTML =
      '<footer class="zw-footer">' +

        '<div class="zw-footer__main"><div class="zw-footer__inner">' +
          '<div class="zw-footer__grid">' +

            /* --- Kolom 1: Pagina's --- */
            '<div>' +
              '<h4>Pagina\'s</h4>' +
              '<ul class="zw-footer__list">' +
                '<li><a href="https://zwijsen.be/over-ons/">Over ons</a></li>' +
                '<li><a href="https://zwijsen.be/realisaties/">Realisaties</a></li>' +
                '<li><a href="https://zwijsen.be/nieuws/">Nieuws</a></li>' +
                '<li><a href="https://zwijsen.be/werken-bij/">Werken bij</a></li>' +
                '<li><a href="https://zwijsen.be/contact/">Contact</a></li>' +
              '</ul>' +
            '</div>' +

            /* --- Kolom 2: Diensten --- */
            '<div>' +
              '<h4>Diensten</h4>' +
              '<ul class="zw-footer__list">' +
                '<li><a href="https://zwijsen.be/algemene-elektriciteitswerken/">Algemene elektriciteitswerken</a></li>' +
                '<li><a href="https://zwijsen.be/bordenbouw-en-knx/">Bordenbouw en KNX</a></li>' +
                '<li><a href="https://zwijsen.be/datanetwerken/">Datanetwerken</a></li>' +
                '<li><a href="https://zwijsen.be/middenspanningsinstallaties/">Middenspanningsinstallaties</a></li>' +
                '<li><a href="https://zwijsen.be/securitywerken/">Securitywerken</a></li>' +
                '<li><a href="https://zwijsen.be/service-en-onderhoud/">Service en onderhoud</a></li>' +
              '</ul>' +
            '</div>' +

            /* --- Kolom 3: adressen en contactgegevens --- */
            '<div>' +
              '<h4>Electro Zwijsen</h4>' +
              '<p class="zw-footer__addr"><strong>Locaties</strong>' +
                'Adolphe Stocletlaan 202<br>2570 Duffel<br>België</p>' +
              '<p class="zw-footer__addr">' +
                'Senator A. Jeurissenlaan 1160<br>3520 Zonhoven<br>België</p>' +
              '<ul class="zw-footer__contact">' +
                '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
                  'stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>' +
                  '<a href="tel:+3215305305">+32 15 305 305</a></li>' +
                '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
                  'stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>' +
                  '<a href="tel:+3215305300">+32 15 305 300</a></li>' +
                '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
                  'stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>' +
                  '<a href="mailto:info@zwijsen.be">info@zwijsen.be</a></li>' +
              '</ul>' +
            '</div>' +

            /* --- Kolom 4: sociale media, juridisch, offerteknop --- */
            '<div>' +
              '<h4>Contact</h4>' +
              '<ul class="zw-footer__social">' +
                socialLink('facebook',  'Facebook',  'https://www.facebook.com/electrozwijsen') +
                socialLink('instagram', 'Instagram', 'https://www.instagram.com/electrozwijsen') +
                socialLink('youtube',   'YouTube',   'https://www.youtube.com/') +
                socialLink('whatsapp',  'WhatsApp',  'https://wa.me/3215305305') +
                socialLink('linkedin',  'LinkedIn',  'https://www.linkedin.com/company/electro-zwijsen') +
              '</ul>' +
              '<ul class="zw-footer__legal">' +
                '<li><a href="https://zwijsen.be/algemene-voorwaarden/">Algemene voorwaarden</a></li>' +
                '<li><a href="https://zwijsen.be/privacy-verklaring/">Privacy verklaring</a></li>' +
                '<li><a href="https://zwijsen.be/verkoops-en-ondernemersvoorwaarden/">Verkoops- en ondernemersvoorwaarden</a></li>' +
                '<li><a href="https://zwijsen.be/vca-erkend/">VCA erkend</a></li>' +
              '</ul>' +
              '<a class="zw-footer__quote" href="https://zwijsen.be/contact/">Offerte aanvragen ' +
                '<svg width="15" height="12" viewBox="0 0 24 20" fill="none" stroke="currentColor" ' +
                'stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                '<path d="M2 10h19M14 3l7 7-7 7"/></svg></a>' +
            '</div>' +

          '</div>' +

          /* Certificaten, onder de eerste kolommen zoals op de live site */
          '<div class="zw-footer__badges">' +
            '<img src="https://zwijsen.be/wp-content/uploads/2021/12/aannemer-klasse-8-NIEUW-removebg-preview.png" ' +
              'alt="Erkend aannemer klasse 8" loading="lazy">' +
            '<img src="https://zwijsen.be/wp-content/uploads/2021/12/sc-sgs55.png" ' +
              'alt="VCA gecertificeerd door SGS" loading="lazy">' +
          '</div>' +
        '</div></div>' +

        /* Witte onderbalk */
        '<div class="zw-footer__bottom"><div class="zw-footer__inner">' +
          '<p class="zw-footer__copy">Copyright ©️ 2022 <a href="https://zwijsen.be/">zwijsen.be</a>' +
            ' | Website door <a href="https://wpinaday.be/">WPinaday</a>' +
            ' | Design by <a href="https://eurekacreates.be/">EurekaCreates</a></p>' +
          '<img class="zw-footer__esf" ' +
            'src="https://zwijsen.be/wp-content/uploads/2021/12/esf_banner_0-1024x76-removebg-preview.png" ' +
            'alt="Europees Sociaal Fonds — investeert in jouw toekomst" loading="lazy">' +
        '</div></div>' +

      '</footer>';
  }

  function wire() {
    var header = document.querySelector('.zw-header');
    if (!header) return;

    /* Schaduw zodra de pagina scrollt — de live site is plat bovenaan. */
    var onScroll = function () { header.classList.toggle('is-stuck', window.scrollY > 8); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    var burger = header.querySelector('.zw-header__burger');
    if (burger) {
      burger.addEventListener('click', function () {
        var open = header.classList.toggle('is-open');
        burger.setAttribute('aria-expanded', String(open));
        burger.setAttribute('aria-label', open ? 'Menu sluiten' : 'Menu openen');
      });
    }

    /* Uitklapmenu's: hover op desktop (CSS), tik op mobiel (hier). */
    header.querySelectorAll('.zw-header__link[aria-haspopup]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (window.matchMedia('(min-width: 1100px)').matches) return;
        e.preventDefault();
        var li = a.parentElement;
        var open = li.classList.toggle('is-open');
        a.setAttribute('aria-expanded', String(open));
      });
    });
  }

  function init() {
    document.querySelectorAll('[data-zw-header]').forEach(buildHeader);
    document.querySelectorAll('[data-zw-footer]').forEach(buildFooter);
    wire();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
