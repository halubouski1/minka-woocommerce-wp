<?php
/**
 * Отключение торговых страниц WooCommerce.
 *
 * Корзины и оформления заказа в бизнес-модели нет: сайт — витрина, заявки
 * идут через формы, продажа закрывается в шоуруме. Но WooCommerce всё равно
 * создаёт страницы /cart/, /checkout/ и /my-account/, и они доступны снаружи.
 *
 * Страницы не удаляются: WooCommerce ссылается на них по ID и при удалении
 * начинает ругаться в админке, а при переустановке создаёт заново. Вместо
 * этого запрос на них уводится на каталог постоянным редиректом — так
 * поисковики склеят адреса, а не оставят их в индексе.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ID страниц, которые нужно закрыть.
 *
 * @return int[]
 */
function minka_disabled_shop_page_ids() {
	$ids = array();

	foreach ( array( 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id' ) as $option ) {
		$id = (int) get_option( $option );

		if ( $id ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Куда уводить — на каталог, а с ним что-то делать можно.
 */
function minka_shop_fallback_url() {
	$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
	$url     = $shop_id ? get_permalink( $shop_id ) : '';

	return $url ? $url : home_url( '/' );
}

/**
 * Постоянный редирект вместо пустой страницы.
 */
function minka_redirect_shop_pages() {
	if ( is_admin() ) {
		return;
	}

	$ids = minka_disabled_shop_page_ids();

	// Проверяем и по ID страницы, и штатными условными тегами WooCommerce:
	// эндпоинты вроде /my-account/lost-password/ по queried object не ловятся.
	$matched = ( $ids && is_page( $ids ) )
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() );

	if ( ! $matched ) {
		return;
	}

	wp_safe_redirect( minka_shop_fallback_url(), 301 );
	exit;
}
add_action( 'template_redirect', 'minka_redirect_shop_pages', 1 );

/**
 * Убирает страницы из карты сайта, чтобы поисковик не приходил на редирект.
 */
function minka_exclude_shop_pages_from_sitemap( $args ) {
	$ids = minka_disabled_shop_page_ids();

	if ( $ids ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $ids );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'minka_exclude_shop_pages_from_sitemap' );
