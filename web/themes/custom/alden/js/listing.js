/**
 * @file
 * Progressive-enhancement client-side filtering for the exhibition
 * listing and What's On pages. The .tabs links underneath are real
 * working links (?state=1/?kind=1 etc.) that filter server-side via the
 * View's exposed filter -- this only intercepts clicks to re-filter the
 * already-rendered items instantly, without a page reload, for anyone
 * with JS. Everything here is inert if #xtabs/#wtabs aren't present.
 *
 * Every item here also carries .rv, the scroll-reveal class motion.js
 * animates in via IntersectionObserver (opacity:0 -> 1) and then
 * unobserves for good. That reveal is a one-time "entered the viewport"
 * effect, not aware that an item can be hidden and shown again by a
 * filter -- so filtering an item back in must not depend on it: an item
 * hidden before it ever scrolled into view (still opacity:0, no .in)
 * would otherwise reappear blank, and one already unobserved after its
 * first reveal would never be re-evaluated at all. Fixed by forcing
 * .in directly whenever a filter shows an item, instead of waiting on
 * the observer to notice.
 */
(function () {
  function wireTabs(tabsId, itemSelector, dataAttr) {
    var tabs = document.getElementById(tabsId);
    if (!tabs) {
      return;
    }
    var items = document.querySelectorAll(itemSelector);

    tabs.addEventListener('click', function (event) {
      event.preventDefault();
      var link = event.target.closest('a[data-filter]');
      if (!link) {
        return;
      }
      var filter = link.dataset.filter;

      tabs.querySelectorAll('a').forEach(function (a) {
        a.setAttribute('aria-pressed', a === link ? 'true' : 'false');
      });
      items.forEach(function (item) {
        var value = item.dataset[dataAttr];
        var show = filter === 'all' || value === filter;
        item.hidden = !show;
        if (show) {
          item.classList.add('in');
        }
      });

      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', link.getAttribute('href'));
      }
    });
  }

  wireTabs('xtabs', '.xcard', 'state');
  wireTabs('wtabs', '.row', 'kind');
})();
