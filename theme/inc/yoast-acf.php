<?php
/**
 * Анализ Yoast для содержимого из ACF.
 *
 * Записи, юридические страницы и карточки товара собираются из полей ACF,
 * поэтому post_content у них пустой и Yoast считал «0 слов». Скрипт ниже
 * отдаёт ему значения полей прямо из редактора; дополнительно тот же текст
 * подставляем в серверные фильтры Yoast, которые ищут в контенте картинки.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Скрипт-мост в редакторе записи.
 */
function minka_yoast_acf_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	// Без Yoast и без ACF мост не нужен.
	if ( ! defined( 'WPSEO_VERSION' ) || ! function_exists( 'get_field' ) ) {
		return;
	}

	wp_enqueue_script(
		'minka-yoast-acf',
		minka_asset( 'js/admin-yoast-acf.js' ),
		array( 'wp-api-fetch' ),
		minka_asset_version( 'js/admin-yoast-acf.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'minka_yoast_acf_assets' );

/**
 * Содержимое блоков ACF как HTML — для серверной части Yoast.
 *
 * Возвращаем ту же разметку, что видит посетитель: заголовки, абзацы,
 * ссылки, таблицы и картинки с alt.
 */
function minka_post_blocks_html( $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$blocks = get_field( 'post_blocks', $post_id );

	if ( ! $blocks ) {
		return '';
	}

	$html = '';

	foreach ( (array) $blocks as $block ) {
		$type = isset( $block['acf_fc_layout'] ) ? $block['acf_fc_layout'] : '';

		if ( 'heading2' === $type ) {
			$html .= '<h2>' . esc_html( $block['heading'] ) . '</h2>';
		} elseif ( 'heading3' === $type ) {
			$html .= '<h3>' . esc_html( $block['heading'] ) . '</h3>';
		} elseif ( 'text' === $type ) {
			$html .= $block['text'];
		} elseif ( 'image' === $type && ! empty( $block['image']['url'] ) ) {
			$html .= sprintf(
				'<img src="%s" alt="%s">',
				esc_url( $block['image']['url'] ),
				esc_attr( isset( $block['image']['alt'] ) ? $block['image']['alt'] : '' )
			);
		} elseif ( 'table' === $type ) {
			foreach ( (array) ( isset( $block['rows'] ) ? $block['rows'] : array() ) as $row ) {
				$cells = array( isset( $row['title'] ) ? $row['title'] : '' );

				foreach ( (array) ( isset( $row['cells'] ) ? $row['cells'] : array() ) as $cell ) {
					$cells[] = isset( $cell['cell'] ) ? $cell['cell'] : '';
				}

				$html .= '<p>' . esc_html( implode( ' — ', array_filter( $cells ) ) ) . '</p>';
			}
		}
	}

	return $html;
}

/**
 * Yoast ищет в контенте изображения (для og:image и подсказок анализа) —
 * пусть видит и те, что лежат в блоках ACF.
 */
function minka_yoast_analysis_content( $content, $post ) {
	if ( ! $post || ! isset( $post->ID ) ) {
		return $content;
	}

	return $content . minka_post_blocks_html( $post->ID );
}
add_filter( 'wpseo_pre_analysis_post_content', 'minka_yoast_analysis_content', 10, 2 );
