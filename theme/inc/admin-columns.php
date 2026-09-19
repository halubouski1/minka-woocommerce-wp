<?php
/**
 * Колонки в списках записей и товаров.
 *
 * Yoast и Complianz добавляют в таблицы админки свои колонки: вместе с
 * родными получается 15 колонок в записях и 19 в товарах. Таблица списка
 * свёрстана с table-layout: fixed, заданные ширины в сумме перекрывают
 * экран — и колонкам без ширины (в том числе заголовку) не остаётся ничего,
 * текст в них ломается по одной букве.
 *
 * Лишние колонки прячем по умолчанию: это тот же механизм, что «Настройки
 * экрана», поэтому любую из них можно вернуть галочкой, ничего не правя в коде.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Колонки, скрытые по умолчанию.
 *
 * Оценки Yoast (два кружка) оставляем — они узкие и полезны в списке.
 * Прячем то, что дублирует содержимое карточки: SEO-заголовок, описание,
 * ключевую фразу, счётчики ссылок, cornerstone, сканирование Complianz,
 * а в товарах ещё и штрихкод с брендами — они здесь не используются.
 */
function minka_default_hidden_columns( $hidden, $screen ) {
	$common = array(
		'wpseo-title',
		'wpseo-metadesc',
		'wpseo-focuskw',
		'wpseo-links',
		'wpseo-linked',
		'wpseo-cornerstone',
		'wpseo-inclusive-language',
		'cmplz_scan',
	);

	$by_screen = array(
		'edit-post'    => $common,
		'edit-page'    => $common,
		'edit-product' => array_merge( $common, array( 'global_unique_id', 'taxonomy-product_brand' ) ),
	);

	if ( isset( $by_screen[ $screen->id ] ) ) {
		$hidden = array_merge( (array) $hidden, $by_screen[ $screen->id ] );
	}

	return $hidden;
}
add_filter( 'default_hidden_columns', 'minka_default_hidden_columns', 10, 2 );

/**
 * Подстраховка по ширине.
 *
 * Если плагин добавит колонок ещё, столбец с названием не должен схлопнуться:
 * задаём ему ширину явно, чтобы он участвовал в раскладке наравне с остальными.
 */
function minka_admin_columns_style() {
	$screen = get_current_screen();

	if ( ! $screen || 0 !== strpos( $screen->id, 'edit-' ) ) {
		return;
	}

	echo '<style>.wp-list-table.fixed .column-title,.wp-list-table.fixed .column-name{width:22%}</style>';
}
add_action( 'admin_head', 'minka_admin_columns_style' );
