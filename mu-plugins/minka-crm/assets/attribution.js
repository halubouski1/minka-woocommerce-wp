/**
 * Сбор атрибуции для форм сайта.
 *
 * Скрипт ничего не отправляет сам — он только дописывает скрытые поля в формы
 * с атрибутом data-minka-form. Тема собирает payload через new FormData(form),
 * поэтому скрытые поля уезжают на сервер без каких-либо правок в теме.
 */
(function () {
	'use strict';

	var STORE = 'minka_attr';
	var TTL_DAYS = 90;
	var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

	// Поля, которые дописываются в каждую форму.
	var FIELDS = UTM_KEYS.concat([
		'fbclid',
		'fbp',
		'fbc',
		'landing_page',
		'referrer',
		'event_id',
		'marketing_consent'
	]);

	function readCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.*+?^${}()|[\]\\])/g, '\\$1') + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : '';
	}

	function writeCookie(name, value, days) {
		var expires = new Date(Date.now() + days * 864e5).toUTCString();
		document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
	}

	/**
	 * Запись атрибуции.
	 *
	 * Правило: заход с меткой кампании (utm_source или fbclid) перезаписывает
	 * сохранённое значение. Это соответствует логике самой Meta — её cookie
	 * _fbc тоже хранит последний клик, а не первый. Прямой заход сохранённую
	 * кампанию не затирает, иначе любой возврат на сайт по закладке обнулял бы
	 * атрибуцию.
	 */
	function attribution() {
		var params = new URLSearchParams(window.location.search);
		var fresh = {};
		var hasCampaign = false;

		UTM_KEYS.forEach(function (key) {
			var value = params.get(key);
			if (value) {
				fresh[key] = value;
				hasCampaign = true;
			}
		});

		var fbclid = params.get('fbclid');
		if (fbclid) {
			fresh.fbclid = fbclid;
			fresh.fbclid_ts = Date.now();
			hasCampaign = true;
		}

		var stored = null;
		try {
			stored = JSON.parse(readCookie(STORE) || 'null');
		} catch (e) {
			stored = null;
		}

		if (!hasCampaign && stored) {
			return stored;
		}

		fresh.landing_page = window.location.href;
		fresh.referrer = document.referrer || '';

		writeCookie(STORE, JSON.stringify(fresh), TTL_DAYS);

		return fresh;
	}

	/**
	 * _fbc в формате Meta: fb.<subdomainIndex>.<время клика>.<fbclid>.
	 *
	 * Пока Pixel не установлен, cookie _fbc не существует, поэтому значение
	 * собирается из сохранённого fbclid — иначе клик по объявлению не с чем
	 * будет сопоставить на стороне Meta.
	 */
	function fbc(data) {
		var cookie = readCookie('_fbc');
		if (cookie) {
			return cookie;
		}

		if (!data.fbclid) {
			return '';
		}

		return 'fb.1.' + (data.fbclid_ts || Date.now()) + '.' + data.fbclid;
	}

	function uuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}

		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = (Math.random() * 16) | 0;
			return (c === 'x' ? r : ((r & 0x3) | 0x8)).toString(16);
		});
	}

	function marketingConsent() {
		try {
			return typeof window.cmplz_has_consent === 'function' && window.cmplz_has_consent('marketing') ? '1' : '0';
		} catch (e) {
			return '0';
		}
	}

	/**
	 * Значения на момент отправки.
	 *
	 * _fbp и _fbc читаются именно здесь, а не при загрузке страницы: их ставит
	 * Meta Pixel, который может появиться позже — после согласия на маркетинг.
	 */
	function values(data) {
		var out = {
			fbclid: data.fbclid || '',
			fbp: readCookie('_fbp'),
			fbc: fbc(data),
			landing_page: data.landing_page || '',
			referrer: data.referrer || '',
			event_id: uuid(),
			marketing_consent: marketingConsent()
		};

		UTM_KEYS.forEach(function (key) {
			out[key] = data[key] || '';
		});

		return out;
	}

	function fill(form, data) {
		var current = values(data);

		FIELDS.forEach(function (name) {
			var input = form.querySelector('input[type="hidden"][name="' + name + '"]');

			if (!input) {
				input = document.createElement('input');
				input.type = 'hidden';
				input.name = name;
				form.appendChild(input);
			}

			input.value = current[name];
		});
	}

	function forms() {
		return document.querySelectorAll('form[data-minka-form]');
	}

	var data = attribution();

	// Первичное заполнение — чтобы поля были в разметке сразу.
	var ready = function () {
		forms().forEach(function (form) {
			fill(form, data);
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', ready);
	} else {
		ready();
	}

	/**
	 * Обновление на сабмите.
	 *
	 * Фаза перехвата — чтобы значения обновились до того, как обработчик темы
	 * соберёт FormData. Здесь же покрывается случай формы, добавленной в DOM
	 * динамически: она получит поля при первой же отправке.
	 *
	 * Обновление обязательно: после успеха тема вызывает form.reset(), и без
	 * этого следующая заявка ушла бы со старым event_id, сломав дедупликацию
	 * событий в Meta.
	 */
	document.addEventListener(
		'submit',
		function (event) {
			var form = event.target;

			if (form && form.matches && form.matches('form[data-minka-form]')) {
				fill(form, data);
			}
		},
		true
	);
})();
