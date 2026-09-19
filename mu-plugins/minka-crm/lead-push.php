<?php
/**
 * Передача заявок в EspoCRM.
 *
 * Точка входа — gform_after_submission: он срабатывает после того, как
 * Gravity Forms проверила и сохранила запись, и не зависит от AJAX-слоя темы.
 * В этом же запросе ещё доступен $_POST, поэтому атрибуция берётся оттуда —
 * в саму запись Gravity Forms её добавлять не нужно.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Каналы связи из формы в значения enum EspoCRM.
 */
function minka_crm_channel_map() {
	return array(
		'Телефон'  => 'Phone',
		'WhatsApp' => 'WhatsApp',
		'Вайбер'   => 'Viber',
		'Telegram' => 'Telegram',
	);
}

/**
 * Ключ формы по её ID в Gravity Forms.
 */
function minka_crm_form_key( $form_id ) {
	$map = (array) get_option( 'minka_form_ids', array() );

	foreach ( $map as $key => $id ) {
		if ( (int) $id === (int) $form_id ) {
			return $key;
		}
	}

	return '';
}

/**
 * Значения записи по adminLabel.
 *
 * Маппинг именно по adminLabel, а не по числовым ID: тема заполняет форму так
 * же, и при переносе полей в админке связь не потеряется.
 */
function minka_crm_entry_values( $entry, $form ) {
	$values = array();

	if ( empty( $form['fields'] ) ) {
		return $values;
	}

	foreach ( $form['fields'] as $field ) {
		$name = isset( $field->adminLabel ) ? $field->adminLabel : '';

		if ( '' === $name ) {
			continue;
		}

		$value = rgar( $entry, (string) $field->id );

		// У чекбокса значение лежит во вложенном входе <id>.1.
		if ( '' === $value && 'checkbox' === $field->type ) {
			$value = rgar( $entry, $field->id . '.1' );
		}

		$values[ $name ] = is_string( $value ) ? trim( $value ) : $value;
	}

	return $values;
}

/**
 * Разбор имени в firstName / lastName.
 *
 * Поле в форме одно и свободное. Если пользователь ввёл одно слово, оно идёт
 * в фамилию: у Lead в EspoCRM именно lastName участвует в отображаемом имени.
 */
function minka_crm_split_name( $raw ) {
	$raw   = trim( preg_replace( '/\s+/u', ' ', (string) $raw ) );
	$parts = '' === $raw ? array() : explode( ' ', $raw, 2 );

	if ( ! $parts ) {
		return array( '', '' );
	}

	if ( 1 === count( $parts ) ) {
		return array( '', $parts[0] );
	}

	return array( $parts[0], $parts[1] );
}

/**
 * Разбор желаемой даты визита.
 *
 * Поле «Дата» в форме — свободный текст, а в CRM это дата. Понимаем привычные
 * числовые записи; всё остальное («сегодня», «на выходных») возвращается как
 * пустое и уезжает в описание лида, где менеджер прочитает исходный текст.
 *
 * @param string $raw Значение из формы.
 * @return string Дата в формате Y-m-d либо пустая строка.
 */
function minka_crm_parse_date( $raw ) {
	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		return '';
	}

	// 2026-09-15
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m ) ) {
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $raw : '';
	}

	// 15.09.2026, 15/09/26, 15-9-2026, 15.09
	if ( preg_match( '#^(\d{1,2})[./-](\d{1,2})(?:[./-](\d{2,4}))?$#', $raw, $m ) ) {
		$day   = (int) $m[1];
		$month = (int) $m[2];

		if ( isset( $m[3] ) && '' !== $m[3] ) {
			$year = (int) $m[3];

			if ( $year < 100 ) {
				$year += 2000;
			}
		} else {
			// Без года: ближайшее наступление этой даты.
			$year = (int) gmdate( 'Y' );

			if ( checkdate( $month, $day, $year ) && sprintf( '%04d-%02d-%02d', $year, $month, $day ) < gmdate( 'Y-m-d' ) ) {
				++$year;
			}
		}

		if ( checkdate( $month, $day, $year ) ) {
			return sprintf( '%04d-%02d-%02d', $year, $month, $day );
		}
	}

	return '';
}

/**
 * Данные модели по URL страницы, с которой пришла заявка.
 *
 * Формы «Узнать наличие» и «Намёк о подарке» открываются с карточки товара,
 * поэтому URL — надёжный источник: он даёт и ID, и артикул, и цену, тогда как
 * в форме лежит только заголовок.
 */
function minka_crm_product_by_url( $url ) {
	if ( ! $url || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$post_id = url_to_postid( $url );

	if ( ! $post_id ) {
		return array();
	}

	$product = wc_get_product( $post_id );

	if ( ! $product ) {
		return array();
	}

	$data = array(
		'productWpId' => (int) $product->get_id(),
		'productName' => $product->get_name(),
		'productUrl'  => get_permalink( $post_id ),
	);

	$sku = $product->get_sku();
	if ( $sku ) {
		$data['productSku'] = $sku;
	}

	$price = $product->get_regular_price();
	if ( '' !== $price && null !== $price ) {
		$data['productPrice']         = (float) $price;
		$data['productPriceCurrency'] = get_woocommerce_currency();
	}

	return $data;
}

/**
 * Значение из $_POST — только атрибуция, которую ставит наш скрипт.
 */
function minka_crm_post( $name ) {
	return isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
}

/**
 * Собирает поля лида для EspoCRM.
 */
function minka_crm_build_lead( $entry, $form, $key ) {
	$values  = minka_crm_entry_values( $entry, $form );
	$channel = minka_crm_channel_map();

	// Страница отправки: поле формы надёжнее source_url — заявка уходит через
	// admin-ajax.php, и Gravity Forms может записать туда его адрес.
	$page = ! empty( $values['page'] ) ? $values['page'] : rgar( $entry, 'source_url' );

	$lead = array(
		'source'      => 'Web Site',
		'status'      => 'New',
		'wpFormKey'   => $key,
		'wpGfFormId'  => (int) rgar( $form, 'id' ),
		'wpGfEntryId' => (int) rgar( $entry, 'id' ),
		'pageUrl'     => $page,
	);

	// «Намёк о подарке»: лид — это отправитель, он на сайте и его атрибуция у
	// нас есть. Получателя намёка кладём в отдельные поля.
	if ( 'gift' === $key ) {
		list( $first, $last ) = minka_crm_split_name( rgar( $values, 'sender_name' ) );

		$lead['firstName']         = $first;
		$lead['lastName']          = $last;
		$lead['emailAddress']      = rgar( $values, 'sender_email' );
		$lead['giftRecipientName'] = rgar( $values, 'recipient_name' );
		$lead['giftRecipientEmail'] = rgar( $values, 'recipient_email' );
	} else {
		list( $first, $last ) = minka_crm_split_name( rgar( $values, 'name' ) );

		$lead['firstName'] = $first;
		$lead['lastName']  = $last;

		$raw_phone  = (string) rgar( $values, 'phone' );
		$normalized = minka_crm_normalize_phone( $raw_phone );

		// В EspoCRM включён международный формат: номер парсится библиотекой, и
		// «8 029 111-22-33» она отвергает, заваливая создание лида целиком.
		// Поэтому в phoneNumber уходит нормализованный номер, а нераспознанный
		// не отбрасывается, а переезжает в описание — заявка важнее формата.
		if ( '' !== $normalized ) {
			$lead['phoneNumber']     = $normalized;
			$lead['phoneNormalized'] = $normalized;
		} elseif ( '' !== $raw_phone ) {
			$unrecognized = 'Телефон не распознан: ' . $raw_phone;
		}

		$contact = rgar( $values, 'contact' );
		if ( isset( $channel[ $contact ] ) ) {
			$lead['preferredChannel'] = $channel[ $contact ];
		}

		// Email необязателен и есть не во всех заявках, но именно он заметно
		// поднимает качество сопоставления в Meta.
		$email = (string) rgar( $values, 'email' );
		if ( '' !== $email ) {
			$lead['emailAddress'] = $email;
		}
	}

	if ( 'showroom' === $key ) {
		// Одно поле на дату и время: «20.09.2026 после 17:00». Пробуем снять с
		// начала строки дату, остаток считаем временем. Если даты нет вовсе
		// («на выходных»), строка целиком уезжает во время либо в описание.
		$raw_visit = trim( (string) rgar( $values, 'visit' ) );

		if ( '' !== $raw_visit ) {
			$parts = preg_split( '/\s+/u', $raw_visit, 2 );
			$date  = minka_crm_parse_date( $parts[0] );
			$rest  = isset( $parts[1] ) ? trim( $parts[1] ) : '';

			if ( '' !== $date ) {
				$lead['requestedVisitDate'] = $date;

				if ( '' !== $rest ) {
					$lead['requestedVisitTime'] = $rest;
				}
			} elseif ( mb_strlen( $raw_visit ) <= 64 ) {
				$lead['requestedVisitTime'] = $raw_visit;
			} else {
				$unparsed_date = 'Желаемые дата и время визита: ' . $raw_visit;
			}
		}
	}

	// Описание собирается последним: сюда стекается всё, что не легло в
	// типизированные поля, — нераспознанный телефон и дата словами.
	$description = rgar( $values, 'comment' );
	if ( '' === $description ) {
		$description = rgar( $values, 'question' );
	}

	$notes = array_filter( array( $unrecognized ?? '', $unparsed_date ?? '' ) );
	if ( $notes ) {
		$prefix      = implode( "\n", $notes );
		$description = '' === $description ? $prefix : $prefix . "\n\n" . $description;
	}

	if ( '' !== $description ) {
		$lead['description'] = $description;
	}

	// Согласие из формы — правовое основание для связи с человеком.
	if ( '' !== (string) rgar( $values, 'agree' ) ) {
		$lead['personalDataConsent']   = true;
		$lead['personalDataConsentAt'] = gmdate( 'Y-m-d H:i:s' );
	}

	$product = minka_crm_product_by_url( $page );
	if ( $product ) {
		$lead = array_merge( $lead, $product );
	} elseif ( ! empty( $values['product'] ) ) {
		$lead['productName'] = $values['product'];
	}

	// Атрибуция: её собирает assets/attribution.js и кладёт в тот же POST.
	$attribution = array(
		'utmSource'   => 'utm_source',
		'utmMedium'   => 'utm_medium',
		'utmCampaign' => 'utm_campaign',
		'utmContent'  => 'utm_content',
		'utmTerm'     => 'utm_term',
		'fbclid'      => 'fbclid',
		'fbp'         => 'fbp',
		'fbc'         => 'fbc',
		'landingPage' => 'landing_page',
		'referrer'    => 'referrer',
		'metaEventId' => 'event_id',
	);

	foreach ( $attribution as $field => $post_key ) {
		$value = minka_crm_post( $post_key );

		if ( '' !== $value ) {
			$lead[ $field ] = $value;
		}
	}

	$lead['marketingConsent'] = ( '1' === minka_crm_post( 'marketing_consent' ) );

	// IP и User-Agent нужны Meta для сопоставления события с пользователем.
	$ip = rgar( $entry, 'ip' );
	if ( $ip ) {
		$lead['clientIpAddress'] = $ip;
	}

	$agent = rgar( $entry, 'user_agent' );
	if ( $agent ) {
		$lead['clientUserAgent'] = substr( $agent, 0, 500 );
	}

	return array_filter(
		$lead,
		static function ( $value ) {
			return '' !== $value && null !== $value;
		}
	);
}

/**
 * Серверное событие Lead в Meta.
 *
 * Пара к браузерному событию Pixel: оба уходят с одним event_id, поэтому Meta
 * засчитывает их как одну конверсию. Серверное нужно потому, что браузерное
 * теряется на блокировщиках и при отказе от cookie.
 *
 * @param string $lead_id ID лида в EspoCRM.
 * @param array  $payload Поля, отправленные при создании.
 */
function minka_crm_send_lead_event( $lead_id, $payload ) {
	if ( ! minka_crm_capi_ready() || empty( $payload['metaEventId'] ) ) {
		return;
	}

	$record       = $payload;
	$record['id'] = $lead_id;

	$custom = array();
	if ( ! empty( $payload['productSku'] ) ) {
		$custom['content_ids']  = array( $payload['productSku'] );
		$custom['content_type'] = 'product';
	}
	if ( ! empty( $payload['productName'] ) ) {
		$custom['content_name'] = $payload['productName'];
	}

	$sent = minka_crm_capi_event(
		'Lead',
		$payload['metaEventId'],
		$record,
		'website',
		isset( $payload['pageUrl'] ) ? $payload['pageUrl'] : '',
		$custom
	);

	if ( $sent ) {
		minka_crm_espo_update( 'Lead', $lead_id, array( 'metaLeadSentAt' => gmdate( 'Y-m-d H:i:s' ) ) );
	}
}

/**
 * Отправляет заявку в EspoCRM.
 *
 * Любая ошибка гасится в лог. Тема показывает «Спасибо» только по успешному
 * ответу minka_form, и уронить эту цепочку значит потерять живую заявку ради
 * телеметрии — CRM здесь вторична по отношению к самому обращению.
 */
function minka_crm_push_lead( $entry, $form ) {
	try {
		$key = minka_crm_form_key( rgar( $form, 'id' ) );

		if ( '' === $key ) {
			return;
		}

		if ( ! minka_crm_espo_ready() ) {
			return;
		}

		$entry_id = (int) rgar( $entry, 'id' );

		$existing = minka_crm_find_lead_by_entry( $entry_id );
		if ( $existing ) {
			minka_crm_log( 'запись ' . $entry_id . ' уже выгружена, лид ' . $existing );

			return;
		}

		$payload = minka_crm_build_lead( $entry, $form, $key );
		$lead_id = minka_crm_create_lead( $payload );

		if ( ! $lead_id ) {
			return;
		}

		minka_crm_log( 'запись ' . $entry_id . ' (' . $key . ') → лид ' . $lead_id );

		minka_crm_send_lead_event( $lead_id, $payload );
	} catch ( Throwable $e ) {
		minka_crm_log( 'необработанная ошибка: ' . $e->getMessage() );
	}
}
add_action( 'gform_after_submission', 'minka_crm_push_lead', 10, 2 );
