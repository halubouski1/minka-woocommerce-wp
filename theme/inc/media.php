<?php
/**
 * Изображения: без пережатия.
 *
 * WordPress не трогает загруженный файл, но каждый производный размер
 * (thumbnail, large, woocommerce_single и прочие) пересохраняет заново
 * с качеством 82 — на мехе это заметно по мылу и «грязи» на ворсе.
 * Здесь пересохранение остаётся, но без потери качества.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Описание картинки из медиатеки.
 *
 * Берём то, что записано в поле «Атрибут alt» у вложения: его редактируют
 * в админке для каждого файла. Если поле пустое, возвращаем запасной текст —
 * лучше название товара или статьи, чем пустой alt на содержательном фото.
 */
function minka_attachment_alt( $attachment_id, $fallback = '' ) {
	$alt = $attachment_id ? get_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', true ) : '';
	$alt = trim( (string) $alt );

	return '' !== $alt ? $alt : $fallback;
}

/**
 * Качество пересохранения — максимальное.
 *
 * jpeg_quality отвечает за JPEG, wp_editor_set_quality — за все форматы,
 * которые обрабатывает редактор изображений, включая WebP.
 */
function minka_image_quality() {
	return 100;
}
add_filter( 'jpeg_quality', 'minka_image_quality' );
add_filter( 'wp_editor_set_quality', 'minka_image_quality' );

/**
 * Не уменьшать большие оригиналы.
 *
 * По умолчанию всё, что шире или выше 2560px, WordPress ужимает до этого
 * размера и дальше показывает уменьшенную копию (файл с суффиксом -scaled),
 * а не оригинал. Для съёмок изделий это лишняя потеря детализации.
 */
add_filter( 'big_image_size_threshold', '__return_false' );
