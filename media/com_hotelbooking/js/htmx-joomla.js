(function () {
	'use strict';

	document.body.addEventListener('htmx:configRequest', function (evt) {
		var token = window.Joomla && Joomla.getOptions ? Joomla.getOptions('csrf.token') : null;

		if (token) {
			evt.detail.parameters[token] = '1';
		}
	});

	document.body.addEventListener('htmx:beforeSwap', function (evt) {
		var status = evt.detail.xhr.status;

		if (status === 403 || status === 422) {
			evt.detail.shouldSwap = true;
			evt.detail.isError = false;
		}
	});

	document.body.addEventListener('hbMessage', function (evt) {
		if (!window.Joomla || !Joomla.renderMessages || !evt.detail || !evt.detail.text) {
			return;
		}

		var type = evt.detail.type === 'success' ? 'success' : 'error';
		var messages = {};
		messages[type] = [evt.detail.text];
		Joomla.renderMessages(messages);
	});
})();
