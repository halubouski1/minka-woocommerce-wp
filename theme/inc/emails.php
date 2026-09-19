<?php
/**
 * Письма, которые сайт отправляет сам.
 *
 * Уведомления администратору настроены в Gravity Forms. Здесь — письмо
 * получателю намёка: его нельзя собрать тегами GF, потому что в нём
 * карточка модели с фотографией, артикулом и ссылкой.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Модель, о которой идёт речь в намёке.
 *
 * Форма присылает адрес страницы, с которой её отправили, — по нему и
 * находим товар. Если не нашли (например, попап открыли не с карточки),
 * пробуем по названию из скрытого поля.
 *
 * @param array $entry Запись Gravity Forms.
 * @param array $form  Форма.
 * @return array url, image, title, sku — или пустой массив.
 */
function minka_gift_product( $entry, $form ) {
	$values = array();

	foreach ( $form['fields'] as $field ) {
		$name = isset( $field->adminLabel ) ? $field->adminLabel : '';

		if ( $name ) {
			$values[ $name ] = rgar( $entry, (string) $field->id );
		}
	}

	$product_id = 0;

	if ( ! empty( $values['page'] ) ) {
		$product_id = (int) url_to_postid( $values['page'] );
	}

	if ( ! $product_id && ! empty( $values['product'] ) ) {
		$found = get_page_by_title( $values['product'], OBJECT, 'product' );

		if ( $found ) {
			$product_id = (int) $found->ID;
		}
	}

	$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

	if ( ! $product ) {
		return array();
	}

	// В письме картинка показывается шириной 300px — на ретине нужна вдвое
	// больше, поэтому берём размер woocommerce_single (600px).
	$image = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_single' );

	return array(
		'url'   => get_permalink( $product_id ),
		'image' => $image ? $image[0] : '',
		'title' => $product->get_name(),
		'sku'   => $product->get_sku(),
	);
}

/**
 * Контакты и соцсети для подвала письма.
 *
 * Ссылки берутся оттуда же, откуда их показывает страница контактов, —
 * заглушки вроде «#» в письмо не попадают: иконка остаётся, но без ссылки.
 */
function minka_email_contacts() {
	$known = array(
		'instagram' => 'instagram',
		'whatsapp'  => 'whatsapp',
		'telegram'  => 'telegram',
	);

	$socials = array();
	$rows    = array_merge( (array) minka_option( 'contacts_socials' ), (array) minka_option( 'footer_socials' ) );

	foreach ( $rows as $row ) {
		$label = isset( $row['social_label'] ) ? $row['social_label'] : '';
		$slug  = mb_strtolower( trim( (string) $label ) );

		if ( ! isset( $known[ $slug ] ) || isset( $socials[ $slug ] ) ) {
			continue;
		}

		$url = isset( $row['social_url'] ) ? trim( (string) $row['social_url'] ) : '';

		$socials[ $slug ] = array(
			'label' => $label,
			'icon'  => $known[ $slug ] . '.png',
			// Заглушки «#» ссылкой не делаем: в письме такая ссылка ведёт в никуда.
			'url'   => 0 === strpos( $url, 'http' ) ? $url : '',
		);
	}

	$phone = minka_option( 'contacts_phone_number' );

	if ( ! $phone ) {
		$phone = preg_replace( '/[^\d+]/', '', (string) minka_option( 'contacts_phone_label' ) );
	}

	return array(
		'socials'     => array_values( $socials ),
		'phone'       => $phone,
		'phone_label' => minka_option( 'contacts_phone_label' ),
		'email'       => minka_option( 'contacts_email', get_option( 'admin_email' ) ),
	);
}

/**
 * Собирает HTML письма из шаблона.
 */
function minka_gift_email_html( $recipient, $sender, $product ) {
	$home      = home_url( '/' );
	$preheader = sprintf( '%s мечтает о подарке из MINKA', $sender );
	$contacts  = minka_email_contacts();

	ob_start();
	include get_template_directory() . '/emails/gift-hint.php';

	return (string) ob_get_clean();
}

/**
 * Отправляет намёк получателю после успешной отправки формы.
 *
 * Отдельным письмом, а не уведомлением Gravity Forms: у GF в теле письма
 * только текст и теги, а здесь нужна вёрстка с карточкой модели.
 *
 * @param array $entry Запись.
 * @param array $form  Форма.
 */
function minka_send_gift_hint( $entry, $form ) {
	if ( ! function_exists( 'minka_form_id' ) || (int) $form['id'] !== minka_form_id( 'gift' ) ) {
		return;
	}

	$values = array();

	foreach ( $form['fields'] as $field ) {
		$name = isset( $field->adminLabel ) ? $field->adminLabel : '';

		if ( $name ) {
			$values[ $name ] = rgar( $entry, (string) $field->id );
		}
	}

	$to = isset( $values['recipient_email'] ) ? sanitize_email( $values['recipient_email'] ) : '';

	if ( ! $to || ! is_email( $to ) ) {
		return;
	}

	$recipient = isset( $values['recipient_name'] ) ? $values['recipient_name'] : '';
	$sender    = isset( $values['sender_name'] ) ? $values['sender_name'] : '';

	$html = minka_gift_email_html( $recipient, $sender, minka_gift_product( $entry, $form ) );

	// Отвечать человек будет магазину, а не тому, кто намекнул: вопросы
	// про модель, размер и цену адресованы нам.
	$from    = minka_option( 'contacts_email', get_option( 'admin_email' ) );
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), $from ),
		sprintf( 'Reply-To: %s', $from ),
	);

	wp_mail( $to, 'Вам намекнули о подарке — MINKA', $html, $headers );
}
add_action( 'gform_after_submission', 'minka_send_gift_hint', 10, 2 );
