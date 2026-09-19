<?php
/**
 * Избранное.
 *
 * Аккаунтов на сайте нет, поэтому список хранится в браузере посетителя
 * (localStorage), а сервер по запросу отдаёт разметку карточек для этих товаров.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Адрес страницы избранного.
 */
function minka_favorites_url() {
	return minka_page_url( 'favorites' );
}

/**
 * Открыта ли сейчас страница избранного.
 */
function minka_is_favorites_page() {
	$page = get_page_by_path( 'favorites' );

	return $page && is_page( $page->ID );
}

/**
 * Метка страницы избранного для стилей: вёрстка пустого состояния и вёрстка
 * списка живут в одних и тех же файлах, и их нужно разводить.
 */
function minka_favorites_body_class( $classes ) {
	if ( minka_is_favorites_page() ) {
		$classes[] = 'page-favorites';
	}

	return $classes;
}
add_filter( 'body_class', 'minka_favorites_body_class' );

/**
 * Товары для блока «Популярные модели».
 */
function minka_popular_products( $limit = 4 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => 'popularity',
			'order'   => 'DESC',
		)
	);

	return array_filter(
		$products,
		static function ( $product ) {
			return $product->is_visible();
		}
	);
}

/**
 * Отдаёт разметку карточек для переданных id — ими JS наполняет избранное.
 */
function minka_favorites_cards() {
	$raw = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

	if ( ! $ids ) {
		wp_send_json_success( array( 'html' => '', 'found' => 0 ) );
	}

	// Порядок задаёт посетитель, поэтому идём по его списку, а не по выдаче базы.
	$html  = '';
	$found = 0;

	foreach ( array_slice( $ids, 0, 60 ) as $id ) {
		$product = wc_get_product( $id );

		if ( ! $product || 'publish' !== get_post_status( $id ) || ! $product->is_visible() ) {
			continue;
		}

		ob_start();
		get_template_part( 'template-parts/card', 'product', array( 'product' => $product ) );
		$html .= ob_get_clean();
		$found++;
	}

	wp_send_json_success(
		array(
			'html'  => $html,
			'found' => $found,
		)
	);
}
add_action( 'wp_ajax_minka_favorites', 'minka_favorites_cards' );
add_action( 'wp_ajax_nopriv_minka_favorites', 'minka_favorites_cards' );
