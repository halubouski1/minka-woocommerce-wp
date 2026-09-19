<?php
/**
 * Загрузка SVG в медиатеку — нужна для иконок соцсетей.
 *
 * Разрешено только тем, кто и так может менять оформление сайта,
 * и каждый файл проходит чистку от скриптов и внешних ссылок.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Может ли текущий пользователь загружать SVG.
 */
function minka_can_upload_svg() {
	return current_user_can( 'edit_theme_options' );
}

/**
 * Разрешить тип файла.
 */
function minka_allow_svg_mime( $mimes ) {
	if ( minka_can_upload_svg() ) {
		$mimes['svg'] = 'image/svg+xml';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'minka_allow_svg_mime' );

/**
 * WP проверяет содержимое файла и для SVG не угадывает тип — подсказываем.
 */
function minka_fix_svg_filetype( $data, $file, $filename, $mimes ) {
	if ( ! minka_can_upload_svg() ) {
		return $data;
	}

	if ( '.svg' === strtolower( substr( $filename, -4 ) ) ) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'minka_fix_svg_filetype', 10, 4 );

/**
 * Чистка SVG перед сохранением: убираем скрипты, обработчики событий,
 * внешние ссылки и прочее исполняемое содержимое.
 */
function minka_sanitize_svg_upload( $file ) {
	if ( empty( $file['type'] ) || 'image/svg+xml' !== $file['type'] ) {
		return $file;
	}

	if ( ! minka_can_upload_svg() ) {
		$file['error'] = 'Загрузка SVG доступна только администраторам.';

		return $file;
	}

	$svg = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( false === $svg || '' === trim( $svg ) ) {
		$file['error'] = 'Не удалось прочитать SVG-файл.';

		return $file;
	}

	$clean = minka_sanitize_svg_markup( $svg );

	if ( null === $clean ) {
		$file['error'] = 'Файл не похож на корректный SVG.';

		return $file;
	}

	file_put_contents( $file['tmp_name'], $clean ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'minka_sanitize_svg_upload' );

/**
 * Возвращает безопасную разметку SVG или null, если разобрать файл не вышло.
 */
function minka_sanitize_svg_markup( $svg ) {
	// Внешние сущности и DTD в SVG нам не нужны — сразу отсекаем.
	$svg = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $svg );
	$svg = preg_replace( '/<!ENTITY[^>]*>/i', '', $svg );

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$loaded   = $dom->loadXML( $svg, LIBXML_NONET | LIBXML_NOENT );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->nodeName ) ) {
		return null;
	}

	$forbidden_tags = array( 'script', 'foreignobject', 'iframe', 'embed', 'object', 'handler', 'set', 'audio', 'video' );

	$xpath = new DOMXPath( $dom );
	foreach ( $xpath->query( '//*' ) as $node ) {
		if ( in_array( strtolower( $node->nodeName ), $forbidden_tags, true ) ) {
			$node->parentNode->removeChild( $node );
			continue;
		}

		if ( ! $node->hasAttributes() ) {
			continue;
		}

		// Идём с конца: удаление атрибута меняет коллекцию на ходу.
		for ( $i = $node->attributes->length - 1; $i >= 0; $i-- ) {
			$attr  = $node->attributes->item( $i );
			$name  = strtolower( $attr->nodeName );
			$value = preg_replace( '/\s+/', '', strtolower( $attr->nodeValue ) );

			$is_event   = 0 === strpos( $name, 'on' );
			$is_script  = false !== strpos( $value, 'javascript:' ) || false !== strpos( $value, 'data:text/html' );
			$is_foreign = in_array( $name, array( 'href', 'xlink:href' ), true ) && 0 !== strpos( $value, '#' );

			if ( $is_event || $is_script || $is_foreign ) {
				$node->removeAttribute( $attr->nodeName );
			}
		}
	}

	return $dom->saveXML();
}

/**
 * Показать SVG в списке медиафайлов и в превью ACF.
 */
function minka_svg_admin_preview() {
	if ( ! is_admin() ) {
		return;
	}

	echo '<style>.media-icon img[src$=".svg"], .acf-image-uploader img[src$=".svg"], img[src$=".svg"].attachment-thumbnail { width: 100% !important; height: auto !important; }</style>';
}
add_action( 'admin_head', 'minka_svg_admin_preview' );
