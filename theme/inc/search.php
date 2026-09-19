<?php
/**
 * Поиск по каталогу в выезжающей панели шапки.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Товары, подходящие под запрос.
 */
function minka_search_products( $query, $limit = 12 ) {
	$query = trim( (string) $query );

	if ( '' === $query ) {
		return array(
			'products' => array(),
			'total'    => 0,
		);
	}

	$search = new WP_Query(
		array(
			's'                   => $query,
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-search' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);

	$products = array();

	foreach ( $search->posts as $post ) {
		$product = wc_get_product( $post->ID );

		if ( $product ) {
			$products[] = $product;
		}
	}

	return array(
		'products' => $products,
		'total'    => (int) $search->found_posts,
	);
}

/**
 * Подсказки — названия подходящих моделей.
 */
function minka_search_suggestions( $query, $limit = 6 ) {
	$found = minka_search_products( $query, $limit );

	if ( ! $found ) {
		return array();
	}

	return array_map(
		static function ( $product ) {
			return $product->get_name();
		},
		$found['products']
	);
}

/**
 * Товары, выбранные в настройках вручную; если не выбраны — популярные.
 * Так собираются и «Может быть интересно», и «Популярные модели» на главной.
 */
function minka_chosen_products( $field, $limit = 4 ) {
	$products = array();

	if ( function_exists( 'get_field' ) ) {
		foreach ( (array) get_field( $field, 'option' ) as $item ) {
			$id      = is_object( $item ) ? $item->ID : (int) $item;
			$product = wc_get_product( $id );

			if ( $product && $product->is_visible() ) {
				$products[] = $product;
			}
		}
	}

	if ( ! $products ) {
		$products = minka_popular_products( $limit );
	}

	return array_slice( $products, 0, $limit );
}

/**
 * Блок «Может быть интересно» в панели поиска.
 */
function minka_search_interesting( $limit = 4 ) {
	return minka_chosen_products( 'search_interesting', $limit );
}

/**
 * Ответ на запрос из панели поиска: подсказки при наборе, карточки при выборе.
 */
function minka_search_response() {
	$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$mode  = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'suggest'; // phpcs:ignore WordPress.Security.NonceVerification

	if ( mb_strlen( $query ) < 2 ) {
		wp_send_json_success(
			array(
				'suggestions' => array(),
				'html'        => '',
				'found'       => 0,
			)
		);
	}

	if ( 'suggest' === $mode ) {
		wp_send_json_success(
			array(
				'suggestions' => minka_search_suggestions( $query ),
				'html'        => '',
				'found'       => 0,
			)
		);
	}

	$found = minka_search_products( $query, 12 );
	$html  = '';

	foreach ( $found['products'] as $product ) {
		ob_start();
		get_template_part( 'template-parts/card', 'product', array( 'product' => $product ) );
		$html .= ob_get_clean();
	}

	wp_send_json_success(
		array(
			'suggestions' => array(),
			'html'        => $html,
			'found'       => $found['total'],
		)
	);
}
add_action( 'wp_ajax_minka_search', 'minka_search_response' );
add_action( 'wp_ajax_nopriv_minka_search', 'minka_search_response' );
