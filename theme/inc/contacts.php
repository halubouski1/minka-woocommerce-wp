<?php
/**
 * Страница «Контакты».
 */

defined( 'ABSPATH' ) || exit;

/**
 * Открыта ли сейчас страница контактов.
 */
function minka_is_contacts_page() {
	$page = get_page_by_path( 'contacts' );

	return $page && is_page( $page->ID );
}

/**
 * Верхний блок: заголовок, текст и два фото.
 */
function minka_contacts_intro() {
	return array(
		'title'  => minka_option( 'contacts_title', 'Связь с нами' ),
		'desc'   => minka_option( 'contacts_desc', 'Будем рады ответить на ваши вопросы и помочь с выбором <br> норковой шубы. Свяжитесь с нами удобным способом <br> или запишитесь на индивидуальную примерку в шоуруме.' ),
		'image'  => minka_image( minka_option( 'contacts_image' ), 'assets/img/contacts.webp', 607, 669 ),
		'image2' => minka_image( minka_option( 'contacts_image_second' ), 'assets/img/contacts-2.webp', 607, 669 ),
	);
}

/**
 * Телефон и почта страницы. Телефон здесь свой: в шапке может стоять другой.
 */
function minka_contacts_details() {
	$phone_label = minka_option( 'contacts_phone_label', '+375 (25) 702 8538' );
	$phone_raw   = minka_option( 'contacts_phone_number', '+375257028538' );

	return array(
		'phone_label' => $phone_label,
		'phone_href'  => 'tel:' . preg_replace( '/[^\d+]/', '', $phone_raw ? $phone_raw : $phone_label ),
		'email'       => minka_option( 'contacts_email', 'hello@minka-furs.com' ),
	);
}

/**
 * Кнопки мессенджеров и соцсетей по умолчанию.
 */
function minka_contacts_default_socials() {
	return array(
		array( 'label' => 'WhatsApp', 'url' => '#', 'icon_path' => 'assets/icons/whatsapp.svg', 'width' => 24, 'height' => 24 ),
		array( 'label' => 'Telegram', 'url' => '#', 'icon_path' => 'assets/icons/tg.svg', 'width' => 21, 'height' => 19 ),
		array( 'label' => 'Instagram', 'url' => '#', 'icon_path' => 'assets/icons/ig.svg', 'width' => 24, 'height' => 24 ),
	);
}

/**
 * Кнопки со ссылками на мессенджеры и соцсети.
 */
function minka_contacts_socials() {
	$rows = minka_option( 'contacts_socials' );

	if ( ! is_array( $rows ) ) {
		$socials = array();

		foreach ( minka_contacts_default_socials() as $item ) {
			$socials[] = array(
				'label' => $item['label'],
				'url'   => $item['url'],
				'icon'  => minka_image( null, $item['icon_path'], $item['width'], $item['height'] ),
			);
		}

		return $socials;
	}

	$socials = array();

	foreach ( $rows as $row ) {
		$icon = minka_image( isset( $row['social_icon'] ) ? $row['social_icon'] : null, '', 24, 24 );

		if ( ! $icon || empty( $row['social_label'] ) ) {
			continue;
		}

		$socials[] = array(
			'label' => $row['social_label'],
			'url'   => ! empty( $row['social_url'] ) ? $row['social_url'] : '#',
			'icon'  => $icon,
		);
	}

	return $socials;
}

/**
 * Блок «Шоурум».
 */
function minka_contacts_showroom() {
	return array(
		'title'  => minka_option( 'contacts_showroom_title', 'Шоурум' ),
		'desc'   => minka_option( 'contacts_showroom_desc', 'Приезжайте на примерку&nbsp;&mdash; покажем модели вживую, поможем с размером и фасоном. Запись по телефону обязательна.' ),
		'button' => minka_option( 'contacts_showroom_button', 'Записаться' ),
	);
}

/**
 * Блок с формой внизу страницы.
 */
function minka_contacts_form() {
	return array(
		'title' => minka_option( 'contacts_form_title', 'Остались вопросы?' ),
		'desc'  => minka_option( 'contacts_form_desc', 'Заполните форму — мы свяжемся с вами' ),
		'image' => minka_image( minka_option( 'contacts_form_image' ), 'assets/img/contact-form.webp', 607, 600 ),
	);
}

/**
 * Вопросы и ответы на контактах: свои или те же, что на главной.
 */
function minka_contacts_faq_items() {
	$inherit = true;

	if ( function_exists( 'get_field' ) ) {
		$inherit = (bool) get_field( 'contacts_faq_inherit', 'option' );
	}

	if ( $inherit ) {
		return minka_faq_items();
	}

	$items = minka_faq_from_rows( minka_option( 'contacts_faq' ) );

	return $items ? $items : minka_faq_items();
}
