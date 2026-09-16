alden theme, generated from starterkit_theme. Additional information on generating themes can be found in the [Starterkit documentation](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme).

## Mobile navigation toggle

`js/nav-toggle.js` follows the [ARIA Authoring Practices Guide disclosure pattern](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/), not a modal/dialog pattern. Concretely, that means:

- The toggle is a native `<button>` with `aria-expanded` reflecting open/closed state, and `aria-controls` pointing at the nav list it reveals.
- Tab moves into the revealed nav items once open — no focus is forced onto them, since they're already next in DOM order after the button.
- Escape, pressed while the menu is open, closes it and returns focus to the toggle button. The listener lives on the nav container (`.alden-primary-nav`), not `document` — Escape presses elsewhere on the page are not intercepted.
- There is deliberately **no focus trap**: Tab can move out of the open menu to whatever follows it in the page, exactly as it would with any other disclosed content. A focus trap belongs to the modal/dialog pattern, not this one.
- With JavaScript disabled, the nav is never hidden in the first place (the toggle button itself is CSS-hidden until the script confirms it can run) — there's no broken, inert toggle to encounter.