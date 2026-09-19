<?php
/**
 * Страница «О нас».
 *
 * Тексты берутся из настроек (MINKA → О нас); дефолты повторяют вёрстку
 * и используются при первичном заполнении полей.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Открыта ли сейчас страница «О нас».
 */
function minka_is_about_page() {
	$page = get_page_by_path( 'about-us' );

	return $page && is_page( $page->ID );
}

/**
 * Абзацы вступления по умолчанию.
 */
function minka_about_default_paragraphs() {
	return array(
		'Создавая MINKA, мы сознательно отказались от формата <br> «всего понемногу». Мы выбрали одну специализацию — натуральные норковые шубы — и отдали ей всё внимание.',
		'Мы убеждены: дорогая вещь должна радовать не один сезон, <br> а долгие годы. Поэтому каждая модель проходит тщательный отбор — от качества меха и кроя до фурнитуры и мельчайших деталей. Для нас важнее не количество, а уверенность в каждой единице.',
	);
}

/**
 * Верхний блок: крупная фраза и абзацы под ней.
 */
function minka_about_intro() {
	$rows = minka_option( 'about_paragraphs' );

	$paragraphs = array();

	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! empty( $row['about_paragraph'] ) ) {
				$paragraphs[] = $row['about_paragraph'];
			}
		}
	}

	return array(
		'title'      => minka_option( 'about_title', 'Только натуральная норка. Только то, в чём мы разбираемся.' ),
		'paragraphs' => $paragraphs ? $paragraphs : minka_about_default_paragraphs(),
	);
}

/**
 * Нижний блок: как отбираем мех, фото слева и большое фото справа.
 */
function minka_about_selection() {
	return array(
		'subtitle' => minka_option( 'about_subtitle', 'Как мы отбираем мех' ),
		'desc'     => minka_option( 'about_desc', 'Работаем напрямую с фабриками, которые шьют из меха международных пушных аукционов (Saga Furs, Kopenhagen Fur) и проверенных европейских выделок. Каждую модель отсматриваем вживую: плотность меха, блеск и мягкость подпуши, ровность кроя, качество подклада и фурнитуры.' ),
		'image'    => minka_image( minka_option( 'about_image' ), 'assets/img/about-us-left.webp', 631, 516 ),
		'cover'    => minka_image( minka_option( 'about_cover' ), 'assets/img/about-us-right.webp', 761, 1152 ),
	);
}

/**
 * Цифры по умолчанию: значение, приписка и подпись.
 */
function minka_about_default_stats() {
	return array(
		array( 'number' => '12', 'suffix' => '', 'label' => 'лет на рынке' ),
		array( 'number' => '5000', 'suffix' => '+', 'label' => 'клиенток' ),
		array( 'number' => '200', 'suffix' => '+', 'label' => 'моделей в наличии <br class="about__label-br"> и под заказ' ),
		array( 'number' => '1', 'suffix' => 'год', 'label' => 'гарантия <br class="about__label-br"> на каждое изделие' ),
	);
}

/**
 * Блок с цифрами.
 */
function minka_about_stats() {
	$rows = minka_option( 'about_stats' );

	if ( ! is_array( $rows ) ) {
		return minka_about_default_stats();
	}

	$stats = array();

	foreach ( $rows as $row ) {
		$number = isset( $row['stat_number'] ) ? $row['stat_number'] : '';

		if ( '' === $number ) {
			continue;
		}

		$stats[] = array(
			'number' => $number,
			'suffix' => isset( $row['stat_suffix'] ) ? $row['stat_suffix'] : '',
			'label'  => isset( $row['stat_label'] ) ? $row['stat_label'] : '',
		);
	}

	return $stats ? $stats : minka_about_default_stats();
}

/**
 * Вопросы и ответы на странице «О нас»: свои или те же, что на главной.
 */
function minka_about_faq_items() {
	$inherit = true;

	if ( function_exists( 'get_field' ) ) {
		$inherit = (bool) get_field( 'about_faq_inherit', 'option' );
	}

	if ( $inherit ) {
		return minka_faq_items();
	}

	$items = minka_faq_from_rows( minka_option( 'about_faq' ) );

	return $items ? $items : minka_faq_items();
}
