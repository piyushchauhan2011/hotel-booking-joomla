(function () {
	'use strict';

	var MIN_CHARS = 2;
	var DEBOUNCE_MS = 200;

	document.addEventListener('DOMContentLoaded', function () {
		var fields = document.querySelectorAll('.hb-search-field');

		for (var i = 0; i < fields.length; i++) {
			initField(fields[i]);
		}
	});

	function initField(field) {
		var input = field.querySelector('input[name="search"]');
		var list = field.querySelector('.hb-search-suggestions');

		if (!input || !list) {
			return;
		}

		var timer = null;
		var controller = null;
		var activeIndex = -1;

		input.addEventListener('input', function () {
			var term = input.value.trim();

			window.clearTimeout(timer);

			if (term.length < MIN_CHARS) {
				closeList();
				return;
			}

			timer = window.setTimeout(function () {
				fetchSuggestions(term);
			}, DEBOUNCE_MS);
		});

		input.addEventListener('keydown', function (event) {
			var items = list.querySelectorAll('button');

			if (list.hidden || !items.length) {
				return;
			}

			if (event.key === 'ArrowDown') {
				event.preventDefault();
				setActive(items, activeIndex + 1);
			} else if (event.key === 'ArrowUp') {
				event.preventDefault();
				setActive(items, activeIndex - 1);
			} else if (event.key === 'Enter') {
				if (activeIndex >= 0 && items[activeIndex]) {
					event.preventDefault();
					navigateTo(items[activeIndex].getAttribute('data-url'));
				}
			} else if (event.key === 'Escape') {
				closeList();
			}
		});

		document.addEventListener('click', function (event) {
			if (!field.contains(event.target)) {
				closeList();
			}
		});

		function setActive(items, index) {
			if (index < 0) {
				index = items.length - 1;
			} else if (index >= items.length) {
				index = 0;
			}

			for (var i = 0; i < items.length; i++) {
				items[i].classList.toggle('is-active', i === index);
			}

			items[index].scrollIntoView({ block: 'nearest' });
			activeIndex = index;
		}

		function navigateTo(url) {
			if (url) {
				window.location.href = url;
			}
		}

		function closeList() {
			list.hidden = true;
			list.innerHTML = '';
			input.setAttribute('aria-expanded', 'false');
			activeIndex = -1;
		}

		function openList() {
			list.hidden = false;
			input.setAttribute('aria-expanded', 'true');
		}

		function highlight(name, term) {
			var index = name.toLowerCase().indexOf(term.toLowerCase());

			if (index === -1) {
				return escapeHtml(name);
			}

			var before = escapeHtml(name.slice(0, index));
			var match = escapeHtml(name.slice(index, index + term.length));
			var after = escapeHtml(name.slice(index + term.length));

			return before + '<mark>' + match + '</mark>' + after;
		}

		function escapeHtml(value) {
			var div = document.createElement('div');
			div.textContent = value;
			return div.innerHTML;
		}

		function render(results, term) {
			list.innerHTML = '';
			activeIndex = -1;

			if (!results.length) {
				var empty = document.createElement('li');
				empty.className = 'hb-search-empty';
				empty.textContent = 'No destinations found';
				list.appendChild(empty);
				openList();
				return;
			}

			results.forEach(function (item) {
				var li = document.createElement('li');
				li.setAttribute('role', 'option');

				var button = document.createElement('button');
				button.type = 'button';
				button.setAttribute('data-url', item.url);
				button.innerHTML = highlight(item.name, term);

				button.addEventListener('click', function () {
					navigateTo(item.url);
				});

				li.appendChild(button);
				list.appendChild(li);
			});

			openList();
		}

		function fetchSuggestions(term) {
			if (controller && controller.abort) {
				controller.abort();
			}

			controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

			var paths = (window.Joomla && Joomla.getOptions) ? Joomla.getOptions('system.paths') : null;
			var root = paths ? paths.rootFull : '/';
			var url = root + 'index.php?option=com_hotelbooking&task=destinations.suggest&term=' + encodeURIComponent(term);

			fetch(url, { signal: controller ? controller.signal : undefined })
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Request failed');
					}
					return response.json();
				})
				.then(function (results) {
					render(results, term);
				})
				.catch(function (error) {
					if (error.name !== 'AbortError') {
						closeList();
					}
				});
		}
	}
})();
