(function () {
	var banner = document.getElementById('hb-cookie-banner');

	if (!banner) {
		return;
	}

	function setCookie(value) {
		document.cookie = 'hb_cookie_consent=' + value + '; path=/; max-age=31536000; samesite=lax';
	}

	function hide() {
		banner.remove();
	}

	banner.addEventListener('click', function (event) {
		var action = event.target && event.target.getAttribute('data-hb-cookie');

		if (!action) {
			return;
		}

		if (action === 'dismiss') {
			setCookie('0');
			hide();
			return;
		}

		if (action !== 'accept') {
			return;
		}

		var token = banner.getAttribute('data-token');
		var url = banner.getAttribute('data-ajax-url');
		var body = new FormData();

		body.append(token, '1');
		body.append('accept', '1');

		fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).finally(function () {
			setCookie('1');
			hide();
		});
	});
})();
