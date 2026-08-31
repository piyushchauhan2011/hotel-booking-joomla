(function () {
	'use strict';

	// This script is loaded in <head> with no `defer`, so it runs before
	// <body> exists. Flag it immediately (hides the nav via CSS straight
	// away, avoiding a flash of the expanded list), but wait for
	// DOMContentLoaded before touching any elements.
	document.documentElement.classList.add('hb-js');

	document.addEventListener('DOMContentLoaded', init);

	function init() {
		var nav = document.querySelector('.container-header .container-nav');
		var menu = document.querySelector('.container-header .mod-menu');

		if (!nav || !menu) {
			return;
		}

		var header = document.querySelector('.header.container-header');

		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'hb-nav-toggle';
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-controls', 'hb-mobile-menu');
		toggle.setAttribute('aria-label', 'Menu');
		toggle.innerHTML = '<span></span><span></span><span></span>';

		menu.id = 'hb-mobile-menu';
		header.insertBefore(toggle, header.firstChild);

		function closeMenu() {
			nav.classList.remove('is-open');
			toggle.classList.remove('is-open');
			toggle.setAttribute('aria-expanded', 'false');
		}

		function openMenu() {
			nav.classList.add('is-open');
			toggle.classList.add('is-open');
			toggle.setAttribute('aria-expanded', 'true');
		}

		toggle.addEventListener('click', function () {
			if (nav.classList.contains('is-open')) {
				closeMenu();
			} else {
				openMenu();
			}
		});

		menu.addEventListener('click', function (event) {
			if (event.target.tagName === 'A') {
				closeMenu();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				closeMenu();
			}
		});

		document.addEventListener('click', function (event) {
			if (!nav.classList.contains('is-open')) {
				return;
			}

			if (!nav.contains(event.target) && !toggle.contains(event.target)) {
				closeMenu();
			}
		});
	}
})();

(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var btn = event.target.closest('[data-hb-copy-link]');

		if (!btn) {
			return;
		}

		var url = btn.getAttribute('data-hb-copy-link');

		if (!url || !navigator.clipboard) {
			return;
		}

		navigator.clipboard.writeText(url).then(function () {
			var original = btn.getAttribute('aria-label');
			btn.classList.add('is-copied');
			btn.setAttribute('aria-label', btn.getAttribute('data-copied-label') || original);

			window.setTimeout(function () {
				btn.classList.remove('is-copied');
				btn.setAttribute('aria-label', original);
			}, 2000);
		});
	});
})();
