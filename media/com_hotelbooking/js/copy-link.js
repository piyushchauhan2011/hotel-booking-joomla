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
