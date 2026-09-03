(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var clearButton = event.target.closest('[data-hb-snippets-clear]');

		if (clearButton) {
			event.preventDefault();

			var form = document.getElementById('adminForm');

			if (!form) {
				return;
			}

			var search = form.querySelector('#filter_search');

			if (search) {
				search.value = '';
			}

			form.querySelectorAll('select[name="filter[destination_id]"], select[name="filter_destination_id"], select[name="filter[entity]"], select[name="filter_entity"]').forEach(function (field) {
				field.value = '';
			});

			form.submit();

			return;
		}

		var link = event.target.closest('.hb-select-link');

		if (!link) {
			return;
		}

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
})();
