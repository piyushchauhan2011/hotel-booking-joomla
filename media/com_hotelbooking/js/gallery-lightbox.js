(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var lightbox = document.querySelector('[data-hb-lightbox]');

		if (!lightbox) {
			return;
		}

		var image   = lightbox.querySelector('[data-hb-lightbox-image]');
		var caption = lightbox.querySelector('[data-hb-lightbox-caption]');
		var closeBtn = lightbox.querySelector('[data-hb-lightbox-close]');

		document.addEventListener('click', function (event) {
			var thumb = event.target.closest('[data-hb-gallery-full]');

			if (!thumb) {
				return;
			}

			open(thumb.getAttribute('data-hb-gallery-full'), thumb.getAttribute('data-hb-gallery-caption') || '');
		});

		closeBtn.addEventListener('click', close);

		lightbox.addEventListener('click', function (event) {
			if (event.target === lightbox) {
				close();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && !lightbox.hidden) {
				close();
			}
		});

		function open(src, captionText) {
			image.src = src;
			image.alt = captionText;
			caption.textContent = captionText;
			lightbox.hidden = false;
		}

		function close() {
			lightbox.hidden = true;
			image.src = '';
		}
	});
})();
