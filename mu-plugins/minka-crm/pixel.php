<?php
/**
 * Подключение Meta Pixel.
 *
 * Без MINKA_META_PIXEL_ID скрипт не подключается вовсе — до появления
 * рекламного аккаунта на страницах не должно быть ничего лишнего.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Данные товара для события ViewContent.
 *
 * content_ids собирается из артикула — тем же значением уходят QualifiedLead
 * и Purchase, иначе события не свяжутся с одним товаром в отчётах Meta.
 */
function minka_crm_pixel_product() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return null;
	}

	$product = wc_get_product( get_the_ID() );

	if ( ! $product ) {
		return null;
	}

	$sku = $product->get_sku();

	if ( ! $sku ) {
		return null;
	}

	return array(
		'sku'      => $sku,
		'name'     => $product->get_name(),
		'value'    => (float) $product->get_regular_price(),
		'currency' => get_woocommerce_currency(),
	);
}

/**
 * Идентификатор пикселя: сначала настройка в админке, затем окружение.
 *
 * Поле MINKA → Аналитика позволяет владельцу поменять пиксель без доступа
 * к серверу; MINKA_META_PIXEL_ID остаётся запасным вариантом и работает,
 * пока поле пустое. Тот же идентификатор использует серверная отправка
 * событий (CAPI), иначе браузерные и серверные события не свяжутся.
 */
function minka_crm_pixel_id() {
	$id = function_exists( 'minka_option' ) ? (string) minka_option( 'meta_pixel_id' ) : '';
	$id = trim( $id );

	if ( '' !== $id ) {
		return $id;
	}

	return trim( minka_crm_config( 'MINKA_META_PIXEL_ID' ) );
}

function minka_crm_enqueue_pixel() {
	if ( is_admin() ) {
		return;
	}

	$pixel_id = minka_crm_pixel_id();

	if ( '' === $pixel_id ) {
		return;
	}

	wp_enqueue_script(
		'minka-crm-pixel',
		MINKA_CRM_URL . '/assets/pixel.js',
		array( 'minka-crm-attribution' ),
		minka_crm_asset_version( 'assets/pixel.js' ),
		true
	);

	wp_localize_script(
		'minka-crm-pixel',
		'MINKA_PIXEL',
		array(
			'pixelId' => $pixel_id,
			'product' => minka_crm_pixel_product(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'minka_crm_enqueue_pixel', 6 );
