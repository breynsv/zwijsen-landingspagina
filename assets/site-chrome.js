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

  function buildFooter(host) {
    host.outerHTML =
      '<footer class="zw-footer">' +
        '<div class="zw-container">' +
          '<div class="zw-footer__grid">' +
            '<div><h4>Pagina\'s</h4><ul>' +
              '<li><a href="https://zwijsen.be/over-ons/">Over ons</a></li>' +
              '<li><a href="https://zwijsen.be/realisaties/">Realisaties</a></li>' +
              '<li><a href="https://zwijsen.be/nieuws/">Nieuws</a></li>' +
              '<li><a href="https://zwijsen.be/werken-bij/">Werken bij</a></li>' +
              '<li><a href="https://zwijsen.be/open-vacatures/">Open vacatures</a></li>' +
              '<li><a href="https://zwijsen.be/contact/">Contact</a></li>' +
            '</ul></div>' +
            '<div><h4>Diensten</h4><ul>' +
              '<li><a href="https://zwijsen.be/algemene-elektriciteitswerken/">Algemene elektriciteitswerken</a></li>' +
              '<li><a href="https://zwijsen.be/">Bordenbouw en KNX</a></li>' +
              '<li><a href="https://zwijsen.be/datanetwerken/">Datanetwerken</a></li>' +
              '<li><a href="https://zwijsen.be/middenspanningsinstallaties/">Middenspanningsinstallaties</a></li>' +
              '<li><a href="https://zwijsen.be/securitywerken/">Securitywerken</a></li>' +
              '<li><a href="https://zwijsen.be/service-en-onderhoud/">Service en onderhoud</a></li>' +
            '</ul></div>' +
            '<div><h4>Electro Zwijsen</h4><ul>' +
              '<li>Adolphe Stocletlaan 202<br>2570 Duffel<br>België</li>' +
              '<li>Senator A. Jeurissenlaan 1160<br>3520 Zonhoven<br>België</li>' +
            '</ul></div>' +
            '<div><h4>Contact</h4><ul>' +
              '<li><a href="tel:+3215305305">+32 15 305 305</a></li>' +
              '<li><a href="tel:+3215305300">+32 15 305 300</a></li>' +
              '<li><a href="mailto:info@zwijsen.be">info@zwijsen.be</a></li>' +
            '</ul></div>' +
          '</div>' +
          '<div class="zw-footer__bottom">' +
            '<span>Copyright © 2026 zwijsen.be</span>' +
            '<span><a href="https://zwijsen.be/algemene-voorwaarden/">Algemene voorwaarden</a> · ' +
            '<a href="https://zwijsen.be/privacy-verklaring/">Privacy verklaring</a></span>' +
          '</div>' +
        '</div>' +
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
