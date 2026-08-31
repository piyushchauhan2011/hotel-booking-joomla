(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var galleries = document.querySelectorAll('.hb-gallery');

		Array.prototype.forEach.call(galleries, function (gallery) {
			var dialog = gallery.nextElementSibling;

			if (!dialog || !dialog.matches('[data-hb-lightbox]')) {
				return;
			}

			initGallery(gallery, dialog);
		});
	});

	function initGallery(gallery, dialog) {
		var image       = dialog.querySelector('[data-hb-lightbox-image]');
		var caption     = dialog.querySelector('[data-hb-lightbox-caption]');
		var description = dialog.querySelector('[data-hb-lightbox-description]');
		var counter     = dialog.querySelector('[data-hb-lightbox-counter]');
		var closeBtn    = dialog.querySelector('[data-hb-lightbox-close]');
		var prevBtn     = dialog.querySelector('[data-hb-lightbox-prev]');
		var nextBtn     = dialog.querySelector('[data-hb-lightbox-next]');
		var figure      = dialog.querySelector('.hb-lightbox-figure');

		var items = [];
		var currentIndex = 0;
		var lastFocused = null;
		var touchStartX = null;

		gallery.addEventListener('click', function (event) {
			var thumb = event.target.closest('[data-hb-gallery-full]');

			if (!thumb) {
				return;
			}

			var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-hb-gallery-full]'));

			items = thumbs.map(function (el) {
				return {
					src: el.getAttribute('data-hb-gallery-full'),
					caption: el.getAttribute('data-hb-gallery-caption') || '',
					description: el.getAttribute('data-hb-gallery-description') || ''
				};
			});

			open(thumbs.indexOf(thumb));
		});

		closeBtn.addEventListener('click', function () {
			dialog.close();
		});

		prevBtn.addEventListener('click', prev);
		nextBtn.addEventListener('click', next);

		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) {
				dialog.close();
			}
		});

		dialog.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				prev();
			} else if (event.key === 'ArrowRight') {
				event.preventDefault();
				next();
			}
		});

		dialog.addEventListener('close', function () {
			image.src = '';

			if (lastFocused && typeof lastFocused.focus === 'function') {
				lastFocused.focus();
			}
		});

		figure.addEventListener('touchstart', function (event) {
			touchStartX = event.changedTouches[0].clientX;
		}, { passive: true });

		figure.addEventListener('touchend', function (event) {
			if (touchStartX === null) {
				return;
			}

			var deltaX = event.changedTouches[0].clientX - touchStartX;
			touchStartX = null;

			if (Math.abs(deltaX) < 40) {
				return;
			}

			if (deltaX > 0) {
				prev();
			} else {
				next();
			}
		}, { passive: true });

		function open(index) {
			currentIndex = index;
			lastFocused = document.activeElement;
			render();
			dialog.showModal();
		}

		function prev() {
			currentIndex = (currentIndex - 1 + items.length) % items.length;
			render();
		}

		function next() {
			currentIndex = (currentIndex + 1) % items.length;
			render();
		}

		function render() {
			var item = items[currentIndex];

			if (!item) {
				return;
			}

			image.src = item.src;
			image.alt = item.caption;
			caption.textContent = item.caption;
			description.textContent = item.description;
			description.hidden = item.description === '';
			counter.textContent = (currentIndex + 1) + ' / ' + items.length;
			counter.hidden = items.length < 2;
			prevBtn.hidden = items.length < 2;
			nextBtn.hidden = items.length < 2;
		}
	}
})();
