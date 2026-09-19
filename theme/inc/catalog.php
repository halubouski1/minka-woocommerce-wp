<?php
/**
 * Каталог: фильтры, сортировка и вспомогательные данные для шаблона.
 *
 * Фильтры по атрибутам и цене работают на штатном механизме WooCommerce
 * (?filter_pa_color=..., ?min_price=...), поэтому запросы к базе строит сам
 * плагин. Своё тут только наличие и разметка под вёрстку.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Группы фильтров каталога. taxonomy — глобальный атрибут товара.
 */
function minka_catalog_filter_groups() {
	return apply_filters(
		'minka_catalog_filter_groups',
		array(
			array( 'label' => 'Фасон', 'taxonomy' => 'pa_fason' ),
			array( 'label' => 'Цвет', 'taxonomy' => 'pa_color' ),
			array( 'label' => 'Размер', 'taxonomy' => 'pa_size' ),
			array( 'label' => 'Длина', 'taxonomy' => 'pa_length' ),
		)
	);
}

/**
 * Варианты фильтра «Наличие».
 */
function minka_catalog_stock_options() {
	return array(
		'instock'      => 'В наличии',
		'onbackorder'  => 'Под заказ',
	);
}

/**
 * Термины атрибута, у которых есть товары.
 */
function minka_catalog_terms( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Выбранные значения атрибута из адреса страницы.
 */
function minka_catalog_chosen_terms( $taxonomy ) {
	$key = 'filter_' . str_replace( 'pa_', '', $taxonomy );

	if ( empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return array();
	}

	$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification

	return array_filter( array_map( 'sanitize_title', explode( ',', $value ) ) );
}

/**
 * Выбранные варианты наличия.
 */
function minka_catalog_chosen_stock() {
	if ( empty( $_GET['stock'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return array();
	}

	$value = sanitize_text_field( wp_unslash( $_GET['stock'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

	return array_intersect( explode( ',', $value ), array_keys( minka_catalog_stock_options() ) );
}

/**
 * Границы цены по всему каталогу — для ползунка.
 */
function minka_catalog_price_bounds() {
	global $wpdb;

	$cached = get_transient( 'minka_price_bounds' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT MIN(min_price) AS min_price, MAX(max_price) AS max_price
		 FROM {$wpdb->wc_product_meta_lookup} lookup
		 INNER JOIN {$wpdb->posts} posts ON posts.ID = lookup.product_id
		 WHERE posts.post_status = 'publish'"
	);

	$min = $row && null !== $row->min_price ? (int) floor( $row->min_price ) : 0;
	$max = $row && null !== $row->max_price ? (int) ceil( $row->max_price ) : 10000;

	if ( $max <= $min ) {
		$max = $min + 1000;
	}

	// Границы — ровно самый дешёвый и самый дорогой товар каталога. Шаг подбираем
	// так, чтобы он делил диапазон нацело: иначе правый край ползунка не доводится
	// до последней цены.
	$bounds = array(
		'min' => $min,
		'max' => $max,
	);

	$span = $bounds['max'] - $bounds['min'];

	foreach ( array( 100, 50, 10, 5 ) as $candidate ) {
		if ( 0 === $span % $candidate ) {
			$bounds['step'] = $candidate;
			break;
		}
	}

	if ( empty( $bounds['step'] ) ) {
		$bounds['step'] = 1;
	}

	set_transient( 'minka_price_bounds', $bounds, HOUR_IN_SECONDS );

	return $bounds;
}

/**
 * Сбрасываем закешированные границы цены при изменении товаров.
 */
function minka_flush_price_bounds() {
	delete_transient( 'minka_price_bounds' );
}
add_action( 'woocommerce_update_product', 'minka_flush_price_bounds' );
add_action( 'woocommerce_new_product', 'minka_flush_price_bounds' );
add_action( 'deleted_post', 'minka_flush_price_bounds' );

/**
 * Текущий диапазон цены: из адреса или по границам каталога.
 */
function minka_catalog_price_range() {
	$bounds = minka_catalog_price_bounds();

	$min = isset( $_GET['min_price'] ) ? (int) $_GET['min_price'] : $bounds['min']; // phpcs:ignore WordPress.Security.NonceVerification
	$max = isset( $_GET['max_price'] ) ? (int) $_GET['max_price'] : $bounds['max']; // phpcs:ignore WordPress.Security.NonceVerification

	return array(
		'min'      => max( $bounds['min'], min( $min, $bounds['max'] ) ),
		'max'      => min( $bounds['max'], max( $max, $bounds['min'] ) ),
		'bounds'   => $bounds,
		'is_activeundefined' => false,
	);
}

/**
 * Активен ли хоть один фильтр — по нему показываем кнопку «Очистить».
 */
function minka_catalog_has_active_filters() {
	if ( minka_catalog_chosen_stock() ) {
		return true;
	}

	if ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return true;
	}

	foreach ( minka_catalog_filter_groups() as $group ) {
		if ( minka_catalog_chosen_terms( $group['taxonomy'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Внутри одной группы чекбоксы работают как «или».
 */
function minka_layered_nav_query_type() {
	return 'or';
}
add_filter( 'woocommerce_layered_nav_default_query_type', 'minka_layered_nav_query_type' );

/**
 * Фильтр по наличию: своего параметра у WooCommerce нет.
 */
function minka_filter_by_stock( $query ) {
	$chosen = minka_catalog_chosen_stock();

	if ( ! $chosen ) {
		return;
	}

	$meta_query   = (array) $query->get( 'meta_query' );
	$meta_query[] = array(
		'key'     => '_stock_status',
		'value'   => array_values( $chosen ),
		'compare' => 'IN',
	);

	$query->set( 'meta_query', $meta_query );
}
add_action( 'woocommerce_product_query', 'minka_filter_by_stock' );

/**
 * По десять товаров за раз: два ряда вёрстки по пять карточек.
 * Столько же догружает кнопка «Показать ещё».
 */
function minka_products_per_page() {
	return 10;
}
add_filter( 'loop_shop_per_page', 'minka_products_per_page', 20 );

/**
 * Запрос за очередной порцией карточек (кнопка «Показать ещё»).
 */
function minka_catalog_is_ajax() {
	return ! empty( $_GET['minka_ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Номер страницы, как он записан в адресе. Основному запросу мы его сбрасываем,
 * поэтому запоминаем отдельно.
 */
function minka_catalog_logical_page( $set = null ) {
	static $page = 1;

	if ( null !== $set ) {
		$page = max( 1, (int) $set );
	}

	return $page;
}

/**
 * Адрес /catalog/page/2/ должен показывать всё до второй страницы включительно,
 * а не только её порцию: иначе после «Показать ещё» и обновления страницы
 * начало каталога пропадает. Догрузка по кнопке приходит с ?minka_ajax=1 —
 * ей отдаём ровно одну порцию, потому что скрипт дописывает её к уже показанным.
 */
function minka_catalog_accumulate_pages( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'is_shop' ) ) {
		return;
	}

	if ( ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}

	$paged = max( 1, (int) $query->get( 'paged' ) );
	minka_catalog_logical_page( $paged );

	if ( minka_catalog_is_ajax() || $paged < 2 ) {
		return;
	}

	$query->set( 'posts_per_page', minka_products_per_page() * $paged );
	$query->set( 'paged', 1 );
	$query->set( 'offset', 0 );
}
add_action( 'pre_get_posts', 'minka_catalog_accumulate_pages', 99 );

/**
 * Ссылка на следующую порцию — или пусто, если каталог показан целиком.
 */
function minka_catalog_next_link() {
	global $wp_query;

	$page = minka_catalog_logical_page();

	// Обычный запрос отдаёт всё до текущей страницы включительно, запрос за
	// порцией — только её, поэтому «показано всего» считаем по номеру страницы.
	$loaded = minka_catalog_is_ajax()
		? minka_products_per_page() * $page
		: (int) $wp_query->post_count;

	if ( (int) $wp_query->found_posts <= $loaded ) {
		return '';
	}

	// Второй аргумент — без экранирования: иначе «&» превращается в «&#038;»,
	// параметры после него становятся якорем и до сервера не доходят.
	// Экранирование делает сам шаблон при выводе.
	return remove_query_arg( 'minka_ajax', get_pagenum_link( $page + 1, false ) );
}

/**
 * Варианты сортировки из вёрстки.
 */
function minka_catalog_sort_options() {
	return array(
		'popularity' => 'По популярности',
		'price'      => 'Сначала дешевле',
		'price-desc' => 'Сначала дороже',
		'date'       => 'По новизне',
	);
}

/**
 * Текущая сортировка.
 */
function minka_catalog_current_sort() {
	$options = minka_catalog_sort_options();
	$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	if ( isset( $options[ $orderby ] ) ) {
		return $orderby;
	}

	$default = get_option( 'woocommerce_default_catalog_orderby', 'popularity' );

	return isset( $options[ $default ] ) ? $default : 'popularity';
}

/**
 * Отдаёт одну сетку каталога — её подставляет JS вместо перезагрузки страницы.
 *
 * Запрос идёт по обычному адресу каталога с ?minka_ajax=1, поэтому фильтры,
 * сортировку и пагинацию отрабатывает тот же основной запрос WordPress,
 * что и при полной загрузке страницы.
 */
function minka_catalog_ajax_response() {
	if ( empty( $_GET['minka_ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}

	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}

	global $wp_query;

	ob_start();
	get_template_part( 'template-parts/catalog-grid' );
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'  => $html,
			'found' => (int) $wp_query->found_posts,
			'pages' => (int) $wp_query->max_num_pages,
		)
	);
}
add_action( 'template_redirect', 'minka_catalog_ajax_response' );

/**
 * Адрес каталога без параметров фильтрации.
 */
function minka_catalog_base_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/catalog/' );
}
