<?php
/**
 * Подключение скрипта атрибуции.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Версия файла по времени изменения — как это сделано в теме.
 */
function minka_crm_asset_version( $file ) {
	$path = MINKA_CRM_DIR . '/' . ltrim( $file, '/' );

	return file_exists( $path ) ? (string) filemtime( $path ) : '0';
}

/**
 * Скрипт нужен на всех страницах, а не только там, где есть форма: атрибуцию
 * надо поймать на входе — на посадочной странице формы может не быть вовсе.
 */
function minka_crm_enqueue_attribution() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'minka-crm-attribution',
		MINKA_CRM_URL . '/assets/attribution.js',
		array(),
		minka_crm_asset_version( 'assets/attribution.js' ),
		false
	);
}
add_action( 'wp_enqueue_scripts', 'minka_crm_enqueue_attribution', 5 );
