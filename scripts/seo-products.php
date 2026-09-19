<?php
/**
 * Базовые SEO и Open Graph для карточек товара.
 *
 * Заголовки и описания собираются из реальных данных WooCommerce: названия,
 * цвета, фасона, длины, размерного ряда и артикула. Это заготовка, которую
 * потом правят руками в админке.
 *
 * Товары, у которых SEO уже заполнено, по умолчанию пропускаются — чтобы
 * повторный запуск не затёр ручные правки. Перезаписать всё: --force.
 *
 * Цена в описание намеренно не попадает: она меняется, а мета-описание
 * останется старым и будет вводить в заблуждение.
 *
 * Запускать через scripts/seo-products.sh
 */

require '/var/www/html/wp-load.php';

if ( ! defined( 'WPSEO_VERSION' ) || ! class_exists( 'WPSEO_Meta' ) ) {
	fwrite( STDERR, "Yoast SEO не активен.\n" );
	exit( 1 );
}

if ( ! function_exists( 'wc_get_products' ) ) {
	fwrite( STDERR, "WooCommerce не активен.\n" );
	exit( 1 );
}

$dry   = in_array( '--dry-run', $argv, true );
$force = in_array( '--force', $argv, true );

const MINKA_TITLE_LIMIT = 60;
const MINKA_DESC_LIMIT  = 156;

/**
 * Значение атрибута товара одной строкой.
 */
function minka_product_attr( WC_Product $product, $slug ) {
	$attributes = $product->get_attributes();

	if ( ! isset( $attributes[ $slug ] ) ) {
		return '';
	}

	$attribute = $attributes[ $slug ];

	$values = $attribute->is_taxonomy()
		? wp_list_pluck( wp_get_post_terms( $product->get_id(), $attribute->get_name() ), 'name' )
		: $attribute->get_options();

	return $values ? trim( (string) reset( $values ) ) : '';
}

/**
 * Собирает строку из частей, отбрасывая те, что не влезли в лимит.
 *
 * Обрезать посреди слова нельзя: в выдаче это выглядит как ошибка. Поэтому
 * лишние части просто не добавляются.
 */
function minka_fit( array $parts, $limit, $suffix = '' ) {
	$out = '';

	foreach ( array_filter( $parts ) as $part ) {
		$candidate = '' === $out ? $part : $out . ', ' . $part;

		if ( mb_strlen( $candidate . $suffix ) > $limit ) {
			continue;
		}

		$out = $candidate;
	}

	return $out . $suffix;
}

$updated = 0;
$skipped = 0;

foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'objects' ) ) as $product ) {
	$id   = $product->get_id();
	$name = $product->get_name();

	if ( ! $force && WPSEO_Meta::get_value( 'title', $id ) ) {
		++$skipped;
		continue;
	}

	$color  = mb_strtolower( minka_product_attr( $product, 'pa_color' ) );
	$fason  = mb_strtolower( minka_product_attr( $product, 'pa_fason' ) );
	$length = mb_strtolower( minka_product_attr( $product, 'pa_length' ) );
	$sizes  = minka_product_attr( $product, 'razmernyy-ryad' );
	$sku    = $product->get_sku();

	// «Норковая шуба Aurora — коричневая, прямая | MINKA»
	$title = minka_fit(
		array( $name, $color, $fason ),
		MINKA_TITLE_LIMIT,
		' | MINKA'
	);

	$desc = minka_fit(
		array_filter(
			array(
				$name . ': натуральная норка',
				$color ? 'цвет ' . $color : '',
				$fason ? 'фасон ' . $fason : '',
				$length ? 'длина ' . $length : '',
				$sizes ? 'размеры ' . $sizes : '',
				$sku ? 'артикул ' . $sku : '',
			)
		),
		MINKA_DESC_LIMIT - 2,
		'.'
	);

	$data = array(
		'title'                 => $title,
		'metadesc'              => $desc,
		'opengraph-title'       => $name . ' — MINKA',
		'opengraph-description' => $desc,
	);

	// Картинка соцсетей — главное изображение товара.
	$image_id = $product->get_image_id();

	if ( $image_id ) {
		$url = wp_get_attachment_url( $image_id );

		$data['opengraph-image']    = $url;
		$data['opengraph-image-id'] = (string) $image_id;
		$data['twitter-image']      = $url;
		$data['twitter-image-id']   = (string) $image_id;
	}

	printf( "%s (id %d)\n", $name, $id );
	printf( "  title    [%2d] %s\n", mb_strlen( $data['title'] ), $data['title'] );
	printf( "  metadesc [%3d] %s\n", mb_strlen( $data['metadesc'] ), $data['metadesc'] );
	printf( "  og-image      %s\n", $image_id ? basename( (string) $url ) : 'нет изображения' );

	if ( $dry ) {
		continue;
	}

	foreach ( $data as $key => $value ) {
		WPSEO_Meta::set_value( $key, $value, $id );
	}

	// Indexables Yoast пересобираются лениво, старую строку убираем.
	$GLOBALS['wpdb']->delete(
		$GLOBALS['wpdb']->prefix . 'yoast_indexable',
		array( 'object_id' => $id, 'object_type' => 'post' )
	);

	++$updated;
}

if ( $dry ) {
	echo "\nПробный запуск, ничего не сохранено.\n";
} else {
	echo "\nЗаполнено товаров: $updated";
	echo $skipped ? ", пропущено (SEO уже есть): $skipped" : '';
	echo "\n";
}
