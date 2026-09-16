/**
 * @file
 * FAQ open/close: native <details> can't transition height (browsers
 * can't animate to/from the internal "not rendered" state), so the
 * summary click is intercepted and the panel height is animated
 * manually -- disabled under prefers-reduced-motion, where it just
 * jumps straight to the open/closed state.
 *
 * Behaviour: one row open at a time when clicked individually. The
 * expand-all control overrides that (opens every row); manually
 * collapsing any one row afterwards returns to normal one-at-a-time
 * behaviour. The control's label is always recomputed from how many
 * rows are actually open, whichever way they got that way.
 */
(function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function answerOf(details) {
    return details.querySelector('.answer');
  }

  // Both take an optional `done` callback, fired at the moment
  // details.open actually changes -- immediately for expand() (the
  // native property is set synchronously up front) but only once the
  // collapse animation's transitionend fires for collapse(), since that
  // removes `open` at the end, not the start. Callers that recompute
  // the expand/collapse-all label from live open/closed state need to
  // wait for that, not fire eagerly at click time.
  function expand(details, done) {
    var answer = answerOf(details);
    if (!answer) {
      return;
    }
    details.open = true;
    if (reduced) {
      answer.style.height = 'auto';
      if (done) {
        done();
      }
      return;
    }
    var target = answer.scrollHeight;
    answer.style.height = '0px';
    // Force a reflow so the browser paints the 0px state before the
    // next change -- otherwise both writes land in the same frame, the
    // transition never actually starts, and transitionend never fires.
    // eslint-disable-next-line no-unused-expressions
    answer.offsetHeight;
    requestAnimationFrame(function () {
      answer.style.height = target + 'px';
    });
    answer.addEventListener('transitionend', function onEnd(event) {
      if (event.propertyName !== 'height') {
        return;
      }
      answer.style.height = 'auto';
      answer.removeEventListener('transitionend', onEnd);
    });
    if (done) {
      done();
    }
  }

  function collapse(details, done) {
    var answer = answerOf(details);
    if (!answer) {
      details.open = false;
      if (done) {
        done();
      }
      return;
    }
    if (reduced) {
      details.open = false;
      answer.style.height = '';
      if (done) {
        done();
      }
      return;
    }
    var current = answer.scrollHeight;
    answer.style.height = current + 'px';
    // eslint-disable-next-line no-unused-expressions
    answer.offsetHeight;
    requestAnimationFrame(function () {
      answer.style.height = '0px';
    });
    answer.addEventListener('transitionend', function onEnd(event) {
      if (event.propertyName !== 'height') {
        return;
      }
      details.open = false;
      answer.style.height = '';
      answer.removeEventListener('transitionend', onEnd);
      if (done) {
        done();
      }
    });
  }

  document.querySelectorAll('.faq').forEach(function (faq) {
    var rows = Array.prototype.slice.call(faq.querySelectorAll('details'));
    var toggleAll = faq.parentElement ? faq.parentElement.querySelector('.faq-toggle-all') : null;
    var mode = 'single';

    function updateToggleLabel() {
      if (!toggleAll || !rows.length) {
        return;
      }
      var openCount = rows.filter(function (d) { return d.open; }).length;
      var allOpen = openCount === rows.length;
      toggleAll.textContent = allOpen ? 'Collapse all' : 'Expand all';
      toggleAll.setAttribute('aria-expanded', String(allOpen));
    }

    rows.forEach(function (details) {
      var summary = details.querySelector('summary');
      if (!summary) {
        return;
      }
      summary.addEventListener('click', function (event) {
        event.preventDefault();
        if (details.open) {
          collapse(details, updateToggleLabel);
          mode = 'single';
        }
        else {
          if (mode === 'single') {
            rows.forEach(function (other) {
              if (other !== details && other.open) {
                collapse(other, updateToggleLabel);
              }
            });
          }
          expand(details, updateToggleLabel);
        }
      });
    });

    if (toggleAll) {
      toggleAll.addEventListener('click', function () {
        var openCount = rows.filter(function (d) { return d.open; }).length;
        if (openCount !== rows.length) {
          mode = 'all';
          rows.forEach(function (d) {
            if (!d.open) {
              expand(d, updateToggleLabel);
            }
          });
        }
        else {
          mode = 'single';
          rows.forEach(function (d) {
            if (d.open) {
              collapse(d, updateToggleLabel);
            }
          });
        }
      });
    }

    updateToggleLabel();
  });
})();
