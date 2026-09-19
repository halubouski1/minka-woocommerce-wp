/**
 * Отдаёт Yoast содержимое полей ACF.
 *
 * Статьи и страницы собираются из гибких блоков ACF, а post_content у них
 * пустой — Yoast показывал «0 слов» и не мог посчитать ни плотность фразы,
 * ни ссылки, ни подзаголовки. Здесь мы собираем значения полей прямо из
 * редактора и подмешиваем их в текст, который анализирует Yoast.
 *
 * Разметку восстанавливаем настоящую: заголовки — <h2>/<h3>, текст —
 * как есть из редактора (со ссылками и списками), изображения — <img alt>.
 * Тогда проверки «фраза в подзаголовке», «внутренние ссылки» и «alt у
 * картинок» работают так же, как на обычной записи.
 */
(function (window, document) {
	'use strict';

	var PLUGIN = 'minkaAcf';
	var altCache = {};
	var pending = {};

	/**
	 * Поля-заготовки внутри репитеров ACF в анализ не берём.
	 */
	function isTemplate(field) {
		return !!field.closest('.acf-clone, .acf-uploader-template, script');
	}

	/**
	 * Текст поля wysiwyg: пока открыта визуальная вкладка, актуальное
	 * значение живёт в TinyMCE, а не в textarea.
	 */
	function editorContent(field) {
		var textarea = field.querySelector('textarea');

		if (!textarea) {
			return '';
		}

		var editor = window.tinymce && textarea.id ? window.tinymce.get(textarea.id) : null;

		if (editor && !editor.isHidden()) {
			return editor.getContent();
		}

		return textarea.value;
	}

	/**
	 * Описание картинки берём из медиатеки — оно там и хранится.
	 * Ответ кэшируем и после загрузки просим Yoast пересчитать анализ.
	 */
	function imageAlt(id) {
		if (Object.prototype.hasOwnProperty.call(altCache, id)) {
			return altCache[id];
		}

		if (!pending[id] && window.wp && window.wp.apiFetch) {
			pending[id] = true;

			window.wp.apiFetch({ path: '/wp/v2/media/' + id })
				.then(function (media) {
					altCache[id] = (media && media.alt_text) || '';
					refresh();
				})
				.catch(function () {
					altCache[id] = '';
				});
		}

		return '';
	}

	/**
	 * Одно поле ACF → кусок разметки для анализа.
	 */
	function fieldHtml(field) {
		var key = field.getAttribute('data-key') || '';
		var type = field.getAttribute('data-type') || '';
		var input;

		if (type === 'wysiwyg') {
			return editorContent(field);
		}

		if (type === 'image') {
			input = field.querySelector('input[type="hidden"]');
			var id = input && input.value ? parseInt(input.value, 10) : 0;

			return id ? '<img src="#" alt="' + imageAlt(id).replace(/"/g, '&quot;') + '">' : '';
		}

		if (type !== 'text' && type !== 'textarea') {
			return '';
		}

		input = field.querySelector('input[type="text"], textarea');

		var value = input ? input.value.trim() : '';

		if (!value) {
			return '';
		}

		// Заголовки блоков статьи — настоящими h2/h3, остальное абзацем.
		if (key.indexOf('field_minka_post_h2') === 0) {
			return '<h2>' + value + '</h2>';
		}

		if (key.indexOf('field_minka_post_h3') === 0) {
			return '<h3>' + value + '</h3>';
		}

		return '<p>' + value + '</p>';
	}

	/**
	 * Всё содержимое ACF на экране, в том же порядке, что и в редакторе.
	 */
	function collect() {
		var fields = document.querySelectorAll('.acf-field[data-key]');
		var parts = [];

		Array.prototype.forEach.call(fields, function (field) {
			if (isTemplate(field)) {
				return;
			}

			var html = fieldHtml(field);

			if (html) {
				parts.push(html);
			}
		});

		return parts.join('\n');
	}

	function addContent(data) {
		var extra = collect();

		return extra ? data + '\n' + extra : data;
	}

	var timer = null;

	function refresh() {
		if (!window.YoastSEO || !window.YoastSEO.app) {
			return;
		}

		window.clearTimeout(timer);

		timer = window.setTimeout(function () {
			window.YoastSEO.app.pluginReloaded(PLUGIN);
		}, 500);
	}

	function init() {
		if (!window.YoastSEO || !window.YoastSEO.app || !window.YoastSEO.app.registerPlugin) {
			window.setTimeout(init, 300);

			return;
		}

		window.YoastSEO.app.registerPlugin(PLUGIN, { status: 'ready' });
		window.YoastSEO.app.registerModification('content', addContent, PLUGIN, 5);

		// Пересчёт по ходу правок: обычные поля, TinyMCE и действия ACF
		// (добавление блока, перетаскивание, выбор картинки).
		document.addEventListener('input', function (event) {
			if (event.target.closest('.acf-field')) {
				refresh();
			}
		});

		document.addEventListener('change', function (event) {
			if (event.target.closest('.acf-field')) {
				refresh();
			}
		});

		if (window.tinymce) {
			window.tinymce.on('AddEditor', function (event) {
				event.editor.on('input keyup change SetContent', refresh);
			});
		}

		if (window.acf) {
			['append', 'remove', 'sortstop', 'change'].forEach(function (action) {
				window.acf.addAction(action, refresh);
			});
		}

		refresh();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})(window, document);
