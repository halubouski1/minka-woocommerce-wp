/**
 * Meta Pixel.
 *
 * Загружается только при маркетинговом согласии: Complianz блокирует
 * рекламные скрипты, и обходить это нельзя. Если согласие дадут позже, Pixel
 * поднимется по событию смены статуса, без перезагрузки страницы.
 */
(function () {
	'use strict';

	var config = window.MINKA_PIXEL || {};

	if (!config.pixelId) {
		return;
	}

	var started = false;

	function hasConsent() {
		try {
			return typeof window.cmplz_has_consent === 'function' && window.cmplz_has_consent('marketing');
		} catch (e) {
			return false;
		}
	}

	function loadPixel() {
		/* eslint-disable */
		!(function (f, b, e, v, n, t, s) {
			if (f.fbq) return;
			n = f.fbq = function () {
				n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
			};
			if (!f._fbq) f._fbq = n;
			n.push = n;
			n.loaded = !0;
			n.version = '2.0';
			n.queue = [];
			t = b.createElement(e);
			t.async = !0;
			t.src = v;
			s = b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t, s);
		})(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
		/* eslint-enable */
	}

	/**
	 * Событие успешной заявки.
	 *
	 * eventID берётся из скрытого поля формы — тот же идентификатор уходит на
	 * сервер и дальше в CAPI. Без совпадения Meta посчитает браузерное и
	 * серверное событие за два разных.
	 */
	function trackLead(form) {
		var input = form.querySelector('input[type="hidden"][name="event_id"]');
		var eventId = input ? input.value : '';

		if (!eventId) {
			return;
		}

		window.fbq('track', 'Lead', {}, { eventID: eventId });
	}

	/**
	 * Успех определяется по классу submitted на попапе — его ставит тема в
	 * showThanks(). Так браузерное событие уходит ровно тогда, когда заявка
	 * действительно принята, и тему при этом менять не нужно.
	 */
	function watchForms() {
		var observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				var popup = mutation.target;

				if (!popup.classList || !popup.classList.contains('submitted')) {
					return;
				}

				if (popup.dataset.minkaLeadSent) {
					return;
				}

				var form = popup.querySelector('form[data-minka-form]');

				if (form) {
					popup.dataset.minkaLeadSent = '1';
					trackLead(form);
				}
			});
		});

		document.querySelectorAll('.popup').forEach(function (popup) {
			observer.observe(popup, { attributes: true, attributeFilter: ['class'] });
		});
	}

	function start() {
		if (started || !hasConsent()) {
			return;
		}

		started = true;

		loadPixel();
		window.fbq('init', config.pixelId);
		window.fbq('track', 'PageView');

		if (config.product && config.product.sku) {
			window.fbq('track', 'ViewContent', {
				content_ids: [config.product.sku],
				content_type: 'product',
				content_name: config.product.name,
				value: config.product.value,
				currency: config.product.currency
			});
		}

		watchForms();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}

	// Согласие могут дать уже после загрузки страницы.
	document.addEventListener('cmplz_status_change', start);
	document.addEventListener('cmplz_fire_categories', start);
})();
