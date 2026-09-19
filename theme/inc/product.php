<?php
/**
 * Данные карточки товара.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Фотографии товара: главная плюс галерея.
 */
function minka_product_gallery( $product ) {
	$ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );

	$images = array();

	foreach ( $ids as $id ) {
		// full — загруженный файл без пересохранения в меньший размер.
		$src = wp_get_attachment_image_src( $id, 'full' );

		if ( ! $src ) {
			continue;
		}

		$images[] = array(
			'url'    => $src[0],
			'width'  => $src[1] ? $src[1] : 452,
			'height' => $src[2] ? $src[2] : 535,
			'alt'    => minka_attachment_alt( $id ),
		);
	}

	if ( ! $images ) {
		$images[] = array(
			'url'    => wc_placeholder_img_src( 'woocommerce_single' ),
			'width'  => 452,
			'height' => 535,
		);
	}

	return $images;
}

/**
 * Картинка для попапа заявки.
 *
 * На карточке товара показываем главное фото модели — человек видит в форме
 * ту же шубу, о которой спрашивает. Везде остальное остаётся фото из вёрстки,
 * его же берём и для товара без изображения.
 */
function minka_popup_image( $fallback = 'assets/img/popup-card-showroom.webp' ) {
	$default = array(
		'url'    => minka_asset( $fallback ),
		'width'  => 607,
		'height' => 737,
	);

	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $default;
	}

	$product = wc_get_product( get_queried_object_id() );

	if ( ! $product || ! $product->get_image_id() ) {
		return $default;
	}

	$images = minka_product_gallery( $product );

	return $images ? $images[0] : $default;
}

/**
 * Текущая и старая цена товара.
 *
 * Старая — это обычная цена WooCommerce, когда у товара задана цена со скидкой.
 * Считаем сами, а не через get_price_html(): там старая цена идёт перед новой и
 * своей разметкой, а в макете порядок обратный. Вариативные и прочие непростые
 * товары корректно описывает только сам WooCommerce («от 1200»), поэтому им
 * оставляем его вывод.
 */
function minka_product_prices( $product ) {
	if ( ! $product->is_type( 'simple' ) ) {
		return array(
			'current' => $product->get_price_html(),
			'old'     => '',
		);
	}

	$old = '';

	if ( $product->is_on_sale() && '' !== $product->get_regular_price() ) {
		$old = wc_price( $product->get_regular_price() );
	}

	return array(
		'current' => wc_price( $product->get_price() ),
		'old'     => $old,
	);
}

/**
 * Характеристики — видимые атрибуты товара, и глобальные, и произвольные.
 */
function minka_product_specs( $product ) {
	$specs = array();

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() ) {
			continue;
		}

		$values = array();

		if ( $attribute->is_taxonomy() ) {
			$terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
			$values = is_wp_error( $terms ) ? array() : $terms;
		} else {
			$values = $attribute->get_options();
		}

		if ( ! $values ) {
			continue;
		}

		$specs[] = array(
			'label' => wc_attribute_label( $attribute->get_name(), $product ),
			'value' => implode( ', ', $values ),
		);
	}

	return $specs;
}

/**
 * Описание товара абзацами: краткое, а если его нет — полное.
 *
 * Возвращается массив абзацев, потому что в вёрстке класс .single__desc
 * висит на самом <p> — обёртка вокруг них стиль бы не получила.
 */
function minka_product_description( $product ) {
	$html = $product->get_short_description();

	if ( $html ) {
		$html = apply_filters( 'woocommerce_short_description', $html );
	} else {
		$html = $product->get_description();
		$html = $html ? apply_filters( 'the_content', $html ) : '';
	}

	if ( ! $html ) {
		return array();
	}

	if ( preg_match_all( '#<p[^>]*>(.*?)</p>#is', $html, $matches ) ) {
		$paragraphs = $matches[1];
	} else {
		$paragraphs = preg_split( '/\n\s*\n/', wp_strip_all_tags( $html, false ) );
	}

	$paragraphs = array_filter( array_map( 'trim', $paragraphs ) );

	return array_values( $paragraphs );
}

/**
 * Похожие модели: выбранные вручную в карточке товара, а если там пусто —
 * подборка WooCommerce по категории и меткам.
 */
function minka_related_products( $product, $limit = 4 ) {
	$products = array();

	if ( function_exists( 'get_field' ) ) {
		$chosen = get_field( 'related_products', $product->get_id() );

		foreach ( (array) $chosen as $item ) {
			$id = is_object( $item ) ? $item->ID : (int) $item;

			if ( $id === $product->get_id() ) {
				continue;
			}

			$related = wc_get_product( $id );

			if ( $related && $related->is_visible() ) {
				$products[] = $related;
			}
		}
	}

	if ( $products ) {
		return array_slice( $products, 0, $limit );
	}

	foreach ( wc_get_related_products( $product->get_id(), $limit ) as $id ) {
		$related = wc_get_product( $id );

		if ( $related && $related->is_visible() ) {
			$products[] = $related;
		}
	}

	return $products;
}

/**
 * Вопросы и ответы на карточке товара: свой список или тот же, что на главной.
 */
function minka_product_faq_items() {
	$inherit = true;

	if ( function_exists( 'get_field' ) ) {
		$inherit = (bool) get_field( 'product_faq_inherit', 'option' );
	}

	if ( $inherit ) {
		return minka_faq_items();
	}

	$items = minka_faq_from_rows( minka_option( 'product_faq' ) );

	return $items ? $items : minka_faq_items();
}
