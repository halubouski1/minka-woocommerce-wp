<?php
/**
 * SEO-правила уровня страницы: Open Graph товара, индексация служебных
 * страниц и чистка карты сайта.
 *
 * Структурированные данные живут отдельно — в inc/schema.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Тип Open Graph для карточки товара.
 *
 * Yoast без платного дополнения WooCommerce SEO отдаёт всем страницам
 * article. Meta и агрегаторы разбирают карточку по типу: для товара это
 * product, иначе цена и наличие ниже игнорируются, а каталог Meta не
 * сопоставит страницу с товаром из фида.
 */
function minka_og_type( $type ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		return 'product';
	}

	return $type;
}
add_filter( 'wpseo_opengraph_type', 'minka_og_type' );

/**
 * Цена, валюта и наличие в Open Graph.
 *
 * Те же данные, что в JSON-LD, но в формате, который читают Meta и
 * Pinterest. Печатаются в конце блока Yoast, внутри его же разметки.
 */
function minka_og_product_tags() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = wc_get_product( get_queried_object_id() );

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$price = wc_get_price_to_display( $product );

	if ( $price ) {
		printf(
			"<meta property=\"product:price:amount\" content=\"%s\" />\n",
			esc_attr( wc_format_decimal( $price, wc_get_price_decimals() ) )
		);
		printf(
			"<meta property=\"product:price:currency\" content=\"%s\" />\n",
			esc_attr( get_woocommerce_currency() )
		);
	}

	printf(
		"<meta property=\"product:availability\" content=\"%s\" />\n",
		esc_attr( $product->is_in_stock() ? 'in stock' : 'out of stock' )
	);

	if ( $product->get_sku() ) {
		printf( "<meta property=\"product:retailer_item_id\" content=\"%s\" />\n", esc_attr( $product->get_sku() ) );
	}

	$brand = get_bloginfo( 'name' );

	printf( "<meta property=\"product:brand\" content=\"%s\" />\n", esc_attr( $brand ) );
	printf( "<meta property=\"product:condition\" content=\"new\" />\n" );
}
add_action( 'wpseo_opengraph', 'minka_og_product_tags', 20 );

/**
 * Страницы, которым в индексе делать нечего.
 *
 * Возвращает ID страниц WordPress: корзина, оформление и кабинет остались
 * от WooCommerce (магазина нет, они переадресуются в каталог), избранное —
 * персональный список, для робота он всегда пуст.
 */
function minka_noindex_page_ids() {
	$ids = array();

	foreach ( array( 'cart', 'checkout', 'myaccount' ) as $key ) {
		$id = (int) wc_get_page_id( $key );

		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}

	$favorites = get_page_by_path( 'favorites' );

	if ( $favorites ) {
		$ids[] = (int) $favorites->ID;
	}

	return $ids;
}

/**
 * Открыт ли сейчас каталог с выбранными фильтрами или сортировкой.
 *
 * Комбинаций фильтров бесконечно много, и каждая — тот же каталог другим
 * порядком. Canonical у них и так ведёт на /catalog/, но noindex экономит
 * роботу обходы.
 */
function minka_is_filtered_catalog() {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_taxonomy() ) ) {
		return false;
	}

	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 0 === strpos( $key, 'filter_' ) || in_array( $key, array( 'min_price', 'max_price', 'orderby', 'query_type_cvet' ), true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Закрываем от индексации то, что дублирует основные страницы.
 *
 * Авторские архивы и рубрика по умолчанию слово в слово повторяют блог,
 * отфильтрованный каталог — каталог. В выдаче они конкурируют с оригиналом.
 */
function minka_robots( $robots ) {
	$noindex = false;

	if ( is_author() || is_search() || is_date() ) {
		$noindex = true;
	}

	// Рубрика «Без рубрики»: служебная, туда попадает всё неразобранное.
	if ( is_category( (int) get_option( 'default_category' ) ) ) {
		$noindex = true;
	}

	if ( is_page() && in_array( get_queried_object_id(), minka_noindex_page_ids(), true ) ) {
		$noindex = true;
	}

	if ( minka_is_filtered_catalog() ) {
		$noindex = true;
	}

	if ( $noindex ) {
		$robots['index'] = 'noindex';
		// follow оставляем: по ссылкам с этих страниц робот должен ходить.
		$robots['follow'] = 'follow';
	}

	return $robots;
}
add_filter( 'wpseo_robots_array', 'minka_robots' );

/**
 * Те же страницы убираем из карты сайта — она не должна вести на
 * переадресации и на закрытое от индексации.
 */
function minka_sitemap_exclude( $ids ) {
	return array_merge( (array) $ids, minka_noindex_page_ids() );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'minka_sitemap_exclude' );
