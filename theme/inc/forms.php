<?php
/**
 * Заявки с сайта уходят в Gravity Forms.
 *
 * Вёрстка и проверка полей остаются свои: на странице показывается форма из
 * макета с её валидацией (JustValidate). Когда проверка пройдена, скрипт
 * отправляет данные сюда, а мы передаём их в Gravity Forms — там появляется
 * запись и оттуда же уходят письма. Так в админке остаются заявки, экспорт и
 * настройка уведомлений, а внешний вид формы не зависит от плагина.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Описание форм: заголовок и поля с их подписями.
 *
 * Ключ поля — атрибут name в разметке, подпись — название поля в Gravity Forms.
 */
function minka_form_definitions() {
	return array(
		'showroom' => array(
			'title'       => 'Записаться в шоурум',
			'description' => 'Заявки из попапа «Записаться в шоурум».',
			'fields'      => array(
				'name'    => array( 'label' => 'Имя', 'type' => 'text', 'required' => true ),
				// Дата и время визита — одним полем: человеку проще написать
				// «20.09 после 17:00», чем раскладывать это по двум строкам.
				'visit'   => array( 'label' => 'Дата и время визита', 'type' => 'text' ),
				'phone'   => array( 'label' => 'Телефон', 'type' => 'phone', 'required' => true ),
				'email'   => array( 'label' => 'Email', 'type' => 'email' ),
				'comment' => array( 'label' => 'Комментарий', 'type' => 'textarea' ),
				'contact' => array(
					'label'    => 'Где связаться',
					'type'     => 'radio',
					'required' => true,
					'choices'  => array( 'Телефон', 'WhatsApp', 'Вайбер', 'Telegram' ),
				),
				'agree'   => array(
					'label'    => 'Согласие на обработку данных',
					'type'     => 'checkbox',
					'required' => true,
					'choices'  => array( 'Согласен' ),
				),
				'page'    => array( 'label' => 'Страница отправки', 'type' => 'text' ),
			),
		),
		'gift'     => array(
			'title'       => 'Намёк о подарке',
			'description' => 'Заявки из попапа «Намекните близкому о подарке» на карточке товара.',
			'fields'      => array(
				'recipient_name'  => array( 'label' => 'Кому намекнуть — имя', 'type' => 'text', 'required' => true ),
				'recipient_email' => array( 'label' => 'Кому намекнуть — почта', 'type' => 'email', 'required' => true ),
				'sender_name'     => array( 'label' => 'От кого — имя', 'type' => 'text', 'required' => true ),
				'sender_email'    => array( 'label' => 'От кого — почта', 'type' => 'email', 'required' => true ),
				'agree'           => array(
					'label'    => 'Согласие на обработку данных',
					'type'     => 'checkbox',
					'required' => true,
					'choices'  => array( 'Согласен' ),
				),
				'product'         => array( 'label' => 'Модель', 'type' => 'text' ),
				'page'            => array( 'label' => 'Страница отправки', 'type' => 'text' ),
			),
		),
		'contacts' => array(
			'title'       => 'Вопрос со страницы контактов',
			'description' => 'Заявки из формы «Остались вопросы?» на странице контактов.',
			'fields'      => array(
				'name'     => array( 'label' => 'Имя', 'type' => 'text', 'required' => true ),
				'phone'    => array( 'label' => 'Телефон', 'type' => 'phone', 'required' => true ),
				'email'    => array( 'label' => 'Email', 'type' => 'email' ),
				'question' => array( 'label' => 'Вопрос', 'type' => 'textarea' ),
				'contact'  => array(
					'label'    => 'Где связаться',
					'type'     => 'radio',
					'required' => true,
					'choices'  => array( 'Телефон', 'WhatsApp', 'Вайбер', 'Telegram' ),
				),
				'agree'    => array(
					'label'    => 'Согласие на обработку данных',
					'type'     => 'checkbox',
					'required' => true,
					'choices'  => array( 'Согласен' ),
				),
				'page'     => array( 'label' => 'Страница отправки', 'type' => 'text' ),
			),
		),
		'cta'      => array(
			'title'       => 'Оставить заявку',
			'description' => 'Заявки из попапа «Узнать наличие» / «Оставить заявку».',
			'fields'      => array(
				'name'    => array( 'label' => 'Имя', 'type' => 'text', 'required' => true ),
				'phone'   => array( 'label' => 'Телефон', 'type' => 'phone', 'required' => true ),
				'email'   => array( 'label' => 'Email', 'type' => 'email' ),
				'comment' => array( 'label' => 'Комментарий', 'type' => 'textarea' ),
				'contact' => array(
					'label'    => 'Где связаться',
					'type'     => 'radio',
					'required' => true,
					'choices'  => array( 'Телефон', 'WhatsApp', 'Вайбер', 'Telegram' ),
				),
				'agree'   => array(
					'label'    => 'Согласие на обработку данных',
					'type'     => 'checkbox',
					'required' => true,
					'choices'  => array( 'Согласен' ),
				),
				'page'    => array( 'label' => 'Страница отправки', 'type' => 'text' ),
			),
		),
	);
}

/**
 * ID формы Gravity Forms по её ключу.
 */
function minka_form_id( $key ) {
	$map = (array) get_option( 'minka_form_ids', array() );

	return isset( $map[ $key ] ) ? (int) $map[ $key ] : 0;
}

/**
 * Создаёт форму в Gravity Forms, если её ещё нет, и запоминает её ID.
 */
function minka_create_form( $key ) {
	$definitions = minka_form_definitions();

	if ( ! class_exists( 'GFAPI' ) || ! isset( $definitions[ $key ] ) ) {
		return 0;
	}

	$existing = minka_form_id( $key );

	if ( $existing && GFAPI::get_form( $existing ) ) {
		return $existing;
	}

	$definition = $definitions[ $key ];
	$fields     = array();
	$field_id   = 1;

	foreach ( $definition['fields'] as $name => $field ) {
		$entry = array(
			'id'         => $field_id,
			'type'       => $field['type'],
			'label'      => $field['label'],
			'isRequired' => ! empty( $field['required'] ),
			// по этой метке ищем поле при приёме заявки
			'adminLabel' => $name,
		);

		// Телефон: международный формат. У Gravity Forms формат «standard» —
		// это американская маска (555) 123-4567, и она отклоняет любой
		// белорусский номер. Причём если формат не задать, плагин сам
		// подставляет «standard» при первом сохранении формы в редакторе.
		if ( 'phone' === $field['type'] ) {
			$entry['phoneFormat'] = 'international';
		}

		if ( ! empty( $field['choices'] ) ) {
			$entry['choices'] = array();
			$entry['inputs']  = array();

			foreach ( $field['choices'] as $index => $choice ) {
				$entry['choices'][] = array(
					'text'  => $choice,
					'value' => $choice,
				);

				// у чекбоксов каждый вариант — отдельный ввод вида 7.1
				if ( 'checkbox' === $field['type'] ) {
					$entry['inputs'][] = array(
						'id'    => $field_id . '.' . ( $index + 1 ),
						'label' => $choice,
					);
				}
			}

			if ( 'checkbox' !== $field['type'] ) {
				unset( $entry['inputs'] );
			}
		}

		$fields[] = $entry;
		$field_id++;
	}

	$form_id = GFAPI::add_form(
		array(
			'title'       => $definition['title'],
			'description' => $definition['description'],
			'labelPlacement' => 'top_label',
			'button'      => array( 'type' => 'text', 'text' => 'Отправить заявку' ),
			'fields'      => $fields,
			'notifications' => array(),
		)
	);

	if ( is_wp_error( $form_id ) ) {
		return 0;
	}

	// Письмо администратору — как у обычной формы Gravity Forms.
	$form = GFAPI::get_form( $form_id );

	$form['notifications'] = array(
		uniqid( '', true ) => array(
			'id'      => uniqid( '', true ),
			'isActive' => true,
			'name'    => 'Уведомление администратору',
			'event'   => 'form_submission',
			'to'      => '{admin_email}',
			'toType'  => 'email',
			'subject' => 'Новая заявка: ' . $definition['title'],
			'message' => '{all_fields}',
		),
	);

	GFAPI::update_form( $form );

	$map         = (array) get_option( 'minka_form_ids', array() );
	$map[ $key ] = (int) $form_id;
	update_option( 'minka_form_ids', $map );

	return (int) $form_id;
}

/**
 * Снимаем с телефона американскую маску Gravity Forms.
 *
 * Нужно для форм, которые уже созданы: формат «standard» плагин выставляет
 * сам, и тогда заявка с белорусским номером отклоняется на сервере, хотя
 * в браузере проверка прошла. Фильтр правит форму в момент проверки, поэтому
 * трогать её в редакторе не требуется.
 */
function minka_form_phone_format( $form ) {
	$ours = array_map( 'intval', (array) get_option( 'minka_form_ids', array() ) );

	if ( ! in_array( (int) $form['id'], $ours, true ) ) {
		return $form;
	}

	foreach ( $form['fields'] as $field ) {
		if ( 'phone' === $field->type ) {
			$field->phoneFormat = 'international';
		}
	}

	return $form;
}
add_filter( 'gform_pre_validation', 'minka_form_phone_format' );

/**
 * Приём заявки: раскладываем поля по ID Gravity Forms и отдаём плагину.
 */
function minka_form_submit() {
	$key         = isset( $_POST['minka_form'] ) ? sanitize_key( wp_unslash( $_POST['minka_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$definitions = minka_form_definitions();

	if ( ! isset( $definitions[ $key ] ) || ! class_exists( 'GFAPI' ) ) {
		wp_send_json_error( array( 'message' => 'Форма не настроена.' ), 400 );
	}

	$form_id = minka_form_id( $key );
	$form    = $form_id ? GFAPI::get_form( $form_id ) : null;

	if ( ! $form ) {
		wp_send_json_error( array( 'message' => 'Форма не найдена.' ), 500 );
	}

	$values = array();

	foreach ( $form['fields'] as $field ) {
		$name = isset( $field->adminLabel ) ? $field->adminLabel : '';

		if ( ! $name || ! isset( $_POST[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			continue;
		}

		$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'textarea' === $field->type ) {
			$value = sanitize_textarea_field( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}

		// Телефон проверяем и приводим к +375291112233 здесь, а не только в
		// браузере: проверку в браузере можно обойти, а дальше номер уходит
		// в CRM и в Meta как ключ склейки — по мусору склеятся разные люди.
		// Заодно в заявке и в уведомлении оказывается один формат, а не пять.
		if ( 'phone' === $name && '' !== $value && function_exists( 'minka_crm_normalize_phone' ) ) {
			$normalized = minka_crm_normalize_phone( $value );

			if ( '' === $normalized ) {
				wp_send_json_error(
					array(
						'message' => 'Проверьте номер телефона: для Беларуси это +375 и девять цифр, для других стран — с кодом страны.',
						'field'   => 'phone',
					),
					422
				);
			}

			$value = $normalized;
		}

		// GFAPI::submit_form() ждёт значения так же, как их прислала бы сама
		// форма: input_2, а у чекбокса — input_7_1 (по номеру варианта).
		if ( 'checkbox' === $field->type && ! empty( $field->inputs ) ) {
			$input_id = str_replace( '.', '_', $field->inputs[0]['id'] );

			$values[ 'input_' . $input_id ] = $field->choices[0]['value'];
			continue;
		}

		$values[ 'input_' . $field->id ] = $value;
	}

	$result = GFAPI::submit_form( $form_id, $values );

	if ( is_wp_error( $result ) || empty( $result['is_valid'] ) ) {
		$message = 'Проверьте заполнение полей.';

		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		} elseif ( ! empty( $result['validation_messages'] ) ) {
			// Gravity Forms называет поле, которое не прошло. Человеку это
			// полезнее общей фразы, а нам — видно причину в консоли браузера.
			$parts = array_map( 'wp_strip_all_tags', (array) $result['validation_messages'] );
			$parts = array_filter( array_map( 'trim', $parts ) );

			if ( $parts ) {
				$message = implode( ' ', $parts );
			}
		}

		wp_send_json_error( array( 'message' => $message ), 422 );
	}

	wp_send_json_success( array( 'entry' => isset( $result['entry_id'] ) ? (int) $result['entry_id'] : 0 ) );
}
add_action( 'wp_ajax_minka_form', 'minka_form_submit' );
add_action( 'wp_ajax_nopriv_minka_form', 'minka_form_submit' );
