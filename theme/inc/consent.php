<?php
/**
 * Согласие на cookie.
 *
 * Плашку показываем свою — ту, что в вёрстке. Всё остальное делает Complianz:
 * хранит выбор, блокирует скрипты до согласия, ведёт журнал и статистику.
 * Собственный баннер плагина при этом прячем, чтобы не было двух окон.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Установлен ли Complianz.
 */
function minka_has_complianz() {
	return defined( 'cmplz_version' ) || defined( 'cmplz_plugin' ) || class_exists( 'COMPLIANZ' );
}

/**
 * Прячем баннер плагина: его роль играет плашка из вёрстки.
 *
 * Скрываем именно контейнер баннера — окно настроек («изменить согласие»)
 * и остальные элементы плагина продолжают работать.
 */
function minka_hide_complianz_banner() {
	if ( ! minka_has_complianz() ) {
		return;
	}

	$css = '#cmplz-cookiebanner-container .cmplz-cookiebanner { display: none !important; }';

	// Плавающий язычок «Manage consent» — тоже баннер плагина. Убираем его:
	// изменить согласие можно ссылкой в подвале.
	$css .= '.cmplz-manage-consent { display: none !important; }';

	wp_add_inline_style( 'minka-theme', $css );
}
add_action( 'wp_enqueue_scripts', 'minka_hide_complianz_banner', 20 );
