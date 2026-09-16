/**
 * @file
 * Progressive-enhancement client-side filtering for the exhibition
 * listing and What's On pages. The .tabs links underneath are real
 * working links (?state=1/?kind=1 etc.) that filter server-side via the
 * View's exposed filter -- this only intercepts clicks to re-filter the
 * already-rendered items instantly, without a page reload, for anyone
 * with JS. Everything here is inert if #xtabs/#wtabs aren't present.
 */
(function () {
  function wireTabs(tabsId, itemSelector, dataAttr) {
    var tabs = document.getElementById(tabsId);
    if (!tabs) {
      return;
    }
    var items = document.querySelectorAll(itemSelector);

    tabs.addEventListener('click', function (event) {
      var link = event.target.closest('a[data-filter]');
      if (!link) {
        return;
      }
      event.preventDefault();
      var filter = link.dataset.filter;

      tabs.querySelectorAll('a').forEach(function (a) {
        a.setAttribute('aria-pressed', a === link ? 'true' : 'false');
      });
      items.forEach(function (item) {
        var value = item.dataset[dataAttr];
        item.hidden = !(filter === 'all' || value === filter);
      });

      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', link.getAttribute('href'));
      }
    });
  }

  wireTabs('xtabs', '.xcard', 'state');
  wireTabs('wtabs', '.row', 'kind');
})();
