/**
 * @file
 * Nav scroll/menu behaviour, scroll reveal and stat count-up -- ported
 * verbatim from mockups/alden-homepage-mockup.html's inline <script>.
 * No animation library. Fully inert under prefers-reduced-motion (the
 * mockup's own reduced-motion branch below: reveals are made visible
 * immediately and stats jump straight to their final value, no observer
 * ever attaches).
 */
(function () {
  'use strict';

  var nav = document.getElementById('nav');
  if (nav) {
    // .has-hero is set server-side (page.html.twig) on the three
    // templates with a full-bleed hero to sit transparently over: front
    // page, exhibition detail, membership. Every other template has no
    // hero and should read solid immediately, not just after scrolling.
    var hasHero = nav.classList.contains('has-hero');
    var onScroll = function () {
      var scrolled = window.scrollY > 60;
      nav.classList.toggle('solid', !hasHero || scrolled);
      nav.classList.toggle('scrolled', scrolled);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var burger = document.getElementById('burger');
  var links = document.getElementById('navlinks');
  if (burger && links) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') === 'true';
      burger.setAttribute('aria-expanded', String(!open));
      links.setAttribute('data-open', String(!open));
    });
    links.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') {
        burger.setAttribute('aria-expanded', 'false');
        links.setAttribute('data-open', 'false');
        burger.focus();
      }
    });
    links.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        burger.setAttribute('aria-expanded', 'false');
        links.setAttribute('data-open', 'false');
      }
    });
  }

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var items = document.querySelectorAll('.rv');
  if (reduced || !('IntersectionObserver' in window)) {
    items.forEach(function (el) { el.classList.add('in'); });
    document.querySelectorAll('[data-count]').forEach(function (el) {
      el.textContent = Number(el.dataset.count).toLocaleString();
    });
    return;
  }

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en, i) {
      if (!en.isIntersecting) return;
      var el = en.target;
      setTimeout(function () { el.classList.add('in'); }, (i % 4) * 90);
      io.unobserve(el);
      var n = el.querySelector('[data-count]');
      if (n) count(n);
    });
  }, { threshold: .12, rootMargin: '0px 0px -8% 0px' });
  items.forEach(function (el) { io.observe(el); });

  function count(el) {
    var target = Number(el.dataset.count), plain = el.dataset.plain, start = null, dur = 1500;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var e = 1 - Math.pow(1 - p, 3);
      var v = Math.round(target * e);
      el.textContent = plain ? String(v) : v.toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
}());
