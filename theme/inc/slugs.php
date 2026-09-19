<?php
/**
 * Латинские адреса для русских названий.
 *
 * WordPress кодирует кириллицу процентами, и ссылка на статью или товар
 * превращается в нечитаемую строку. Здесь она транслитерируется.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Латинский слаг из русского названия.
 */
function minka_translit_slug( $name ) {
	$map = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
		'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
		'–' => '-', '—' => '-',
	);

	$slug = strtr( mb_strtolower( $name, 'UTF-8' ), $map );

	return sanitize_title( $slug );
}

/**
 * Транслитерируем то, из чего WordPress делает адрес.
 *
 * Фильтр срабатывает при сохранении записи, страницы, товара и термина:
 * заголовок «Норковая шуба Aurora» даёт /catalog/norkovaya-shuba-aurora/,
 * а не строку из процентов.
 */
function minka_translit_sanitize_title( $title, $raw_title = '', $context = 'display' ) {
	if ( 'save' !== $context ) {
		return $title;
	}

	// Уже латиница или пусто — трогать нечего.
	if ( '' === $title || ! preg_match( '/[а-яё]/iu', $raw_title ? $raw_title : $title ) ) {
		return $title;
	}

	$source = $raw_title ? $raw_title : $title;
	$map    = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
		'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
		'–' => '-', '—' => '-',
	);

	// Без рекурсии: sanitize_title_with_dashes чистит строку сам.
	return sanitize_title_with_dashes( strtr( mb_strtolower( $source, 'UTF-8' ), $map ), '', 'save' );
}
add_filter( 'sanitize_title', 'minka_translit_sanitize_title', 9, 3 );
