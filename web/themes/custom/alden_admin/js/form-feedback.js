/**
 * @file
 * Minimal unsaved-changes indicator for entity forms. Progressive
 * enhancement only: forms work identically without it, and it never
 * intercepts or delays submission (no beforeunload prompt).
 */
(function () {
  'use strict';

  Drupal.behaviors.aldenFormFeedback = {
    attach: function (context) {
      once('alden-form-feedback', '.node-form, .paragraphs-config-form, form.media-form', context).forEach(function (form) {
        var actions = form.querySelector('.form-actions');
        if (!actions) {
          return;
        }

        var indicator = document.createElement('span');
        indicator.className = 'alden-unsaved-indicator';
        indicator.hidden = true;
        indicator.textContent = Drupal.t('Unsaved changes');
        actions.insertAdjacentElement('afterbegin', indicator);

        form.addEventListener('input', function () {
          indicator.hidden = false;
        }, { once: true, capture: true });

        form.addEventListener('change', function () {
          indicator.hidden = false;
        }, { once: true, capture: true });

        form.addEventListener('submit', function () {
          indicator.hidden = true;
        });
      });
    }
  };
})();
