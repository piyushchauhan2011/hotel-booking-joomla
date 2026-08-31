(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var links = document.querySelectorAll('.hb-select-link');

		Array.prototype.forEach.call(links, function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault();

				var options = Joomla.getOptions('xtd-hotelbooking');

				if (!options || !options.editor) {
					return;
				}

				var type = link.getAttribute('data-type');
				var id = link.getAttribute('data-id');
				var tag = '{hotelbooking type="' + type + '" id="' + id + '"}';

				if (type === 'offer') {
					var entity = link.getAttribute('data-entity');
					var index = link.getAttribute('data-index');
					tag = '{hotelbooking type="offer" entity="' + entity + '" id="' + id + '" index="' + index + '"}';
				}

				window.parent.Joomla.editors.instances[options.editor].replaceSelection(tag);

				if (window.parent.Joomla.Modal && window.parent.Joomla.Modal.getCurrent()) {
					window.parent.Joomla.Modal.getCurrent().close();
				}
			});
		});
	});
})();
