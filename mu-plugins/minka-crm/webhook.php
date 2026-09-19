<?php
/**
 * Приём вебхуков EspoCRM.
 *
 * EspoCRM дёргает этот эндпоинт при смене статуса лида и стадии сделки. Оба
 * события Meta — QualifiedLead и Purchase — рождаются здесь: у сайта таких
 * событий нет, продажа происходит офлайн в шоуруме.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Секретные ключи вебхуков (Администрирование → Вебхуки в EspoCRM).
 *
 * Ключей несколько — по одному на вебхук, поэтому список через запятую.
 */
function minka_crm_webhook_secrets() {
	$raw = minka_crm_config( 'MINKA_ESPO_WEBHOOK_SECRETS' );

	if ( '' === $raw ) {
		return array();
	}

	return array_filter( array_map( 'trim', explode( ',', $raw ) ) );
}

/**
 * Проверяет подпись запроса.
 *
 * EspoCRM шлёт заголовок Signature вида base64("<id вебхука>:<hex hmac>").
 * Идентификатор вебхука заранее не известен, поэтому проверяем против всех
 * настроенных секретов — их единицы.
 *
 * @param string $signature Значение заголовка Signature.
 * @param string $payload   Сырое тело запроса.
 * @return bool
 */
function minka_crm_webhook_verify( $signature, $payload ) {
	$secrets = minka_crm_webhook_secrets();

	if ( ! $secrets || '' === $signature ) {
		return false;
	}

	$decoded = base64_decode( $signature, true );

	if ( false === $decoded || ! str_contains( $decoded, ':' ) ) {
		return false;
	}

	list( $webhook_id, $hash ) = explode( ':', $decoded, 2 );

	foreach ( $secrets as $secret ) {
		$expected = hash_hmac( 'sha256', $payload, $secret );

		if ( hash_equals( $expected, $hash ) ) {
			return true;
		}
	}

	unset( $webhook_id );

	return false;
}

/**
 * Лид перешёл в статус Qualified → событие QualifiedLead.
 *
 * Браузерной пары у события нет, поэтому event_id генерируется здесь.
 * Заполненный metaQualifiedSentAt означает, что событие уже ушло: статус
 * могут вернуть назад и снова поставить, а Meta не должна получить дубль.
 */
function minka_crm_handle_lead( $id, $status ) {
	// Статус берём из самого вебхука, а не из текущего состояния записи.
	// Очередь разбирается с задержкой, и лид успевает уйти дальше по воронке:
	// если менеджер квалифицировал и сразу сконвертировал, перечитанная запись
	// вернула бы «Converted», и событие «Qualified» потерялось бы совсем.
	if ( 'Qualified' !== $status ) {
		return;
	}

	$lead = minka_crm_espo_get( 'Lead', $id );

	if ( ! $lead ) {
		return;
	}

	// Оповещение отправляем до проверок Meta и независимо от них: менеджеру
	// сообщение нужно в любом случае, даже если человек отказал в маркетинге
	// и событие в рекламный кабинет не уйдёт.
	if ( minka_crm_telegram_send( minka_crm_telegram_qualified_message( $lead ) ) ) {
		minka_crm_log( 'Telegram: лид ' . $id . ' квалифицирован — оповещение отправлено' );
	}

	if ( ! empty( $lead['metaQualifiedSentAt'] ) ) {
		return;
	}

	$event_id = wp_generate_uuid4();

	$custom = array();
	if ( ! empty( $lead['productSku'] ) ) {
		$custom['content_ids']  = array( $lead['productSku'] );
		$custom['content_type'] = 'product';
	}
	if ( ! empty( $lead['productName'] ) ) {
		$custom['content_name'] = $lead['productName'];
	}

	// system_generated — событие породила внутренняя система, а не действие
	// пользователя в браузере.
	if ( ! minka_crm_capi_event( 'QualifiedLead', $event_id, $lead, 'system_generated', '', $custom ) ) {
		return;
	}

	minka_crm_espo_update(
		'Lead',
		$id,
		array(
			'metaQualifiedEventId' => $event_id,
			'metaQualifiedSentAt'  => gmdate( 'Y-m-d H:i:s' ),
		)
	);
}

/**
 * Сделка закрыта успешно → событие Purchase.
 *
 * event_time — момент закрытия, а не дата лида: CAPI отвергает события старше
 * семи дней, а цикл сделки в шоуруме заметно длиннее.
 */
function minka_crm_handle_opportunity( $id, $stage ) {
	// Стадия — из вебхука, по той же причине, что и статус лида выше.
	if ( 'Closed Won' !== $stage && 'Closed Lost' !== $stage ) {
		return;
	}

	$opportunity = minka_crm_espo_get( 'Opportunity', $id );

	if ( ! $opportunity ) {
		return;
	}

	// Провал тоже стоит показать: команда видит исход, а по метке кампании
	// сразу понятно, какой источник приносит пустые заявки.
	if ( 'Closed Lost' === $stage ) {
		if ( minka_crm_telegram_send( minka_crm_telegram_deal_message( $opportunity, false ) ) ) {
			minka_crm_log( 'Telegram: сделка ' . $id . ' провалена — оповещение отправлено' );
		}

		return;
	}

	if ( minka_crm_telegram_send( minka_crm_telegram_deal_message( $opportunity, true ) ) ) {
		minka_crm_log( 'Telegram: сделка ' . $id . ' закрыта успешно — оповещение отправлено' );
	}

	if ( ! empty( $opportunity['metaPurchaseSentAt'] ) ) {
		return;
	}

	// Телефон, имя и согласие живут на лиде, из которого выросла сделка: в
	// самой сделке их может не быть, а Meta без них почти не найдёт совпадений.
	// Согласие подтягивается тоже — иначе сделка, собранная вручную, никогда
	// не пройдёт проверку и Purchase не уйдёт.
	if ( ! empty( $opportunity['originalLeadId'] ) ) {
		$opportunity['externalId'] = $opportunity['originalLeadId'];

		$lead = minka_crm_espo_get( 'Lead', $opportunity['originalLeadId'] );

		if ( $lead ) {
			$inherited = array( 'phoneNormalized', 'emailAddress', 'firstName', 'lastName', 'marketingConsent' );

			foreach ( $inherited as $field ) {
				if ( empty( $opportunity[ $field ] ) && ! empty( $lead[ $field ] ) ) {
					$opportunity[ $field ] = $lead[ $field ];
				}
			}
		}
	}

	$event_id = wp_generate_uuid4();

	$custom = array(
		'value'    => isset( $opportunity['amount'] ) ? (float) $opportunity['amount'] : 0,
		'currency' => isset( $opportunity['amountCurrency'] ) ? $opportunity['amountCurrency'] : get_woocommerce_currency(),
	);

	if ( ! empty( $opportunity['productSku'] ) ) {
		$custom['content_ids']  = array( $opportunity['productSku'] );
		$custom['content_type'] = 'product';
	}
	if ( ! empty( $opportunity['productName'] ) ) {
		$custom['content_name'] = $opportunity['productName'];
	}

	// physical_store — продажа состоялась в шоуруме, а не на сайте. URL при
	// этом всё равно полезен: он показывает, с какой страницы всё началось.
	$source_url = '';
	foreach ( array( 'productUrl', 'landingPage' ) as $field ) {
		if ( ! empty( $opportunity[ $field ] ) ) {
			$source_url = $opportunity[ $field ];
			break;
		}
	}

	if ( ! minka_crm_capi_event( 'Purchase', $event_id, $opportunity, 'physical_store', $source_url, $custom ) ) {
		return;
	}

	minka_crm_espo_update(
		'Opportunity',
		$id,
		array(
			'metaPurchaseEventId' => $event_id,
			'metaPurchaseSentAt'  => gmdate( 'Y-m-d H:i:s' ),
		)
	);
}

/**
 * Обработчик эндпоинта.
 */
function minka_crm_webhook_handle( WP_REST_Request $request ) {
	$payload = $request->get_body();

	if ( ! minka_crm_webhook_verify( (string) $request->get_header( 'Signature' ), $payload ) ) {
		minka_crm_log( 'вебхук отклонён: подпись не совпала' );

		return new WP_REST_Response( array( 'error' => 'invalid signature' ), 401 );
	}

	$records = json_decode( $payload, true );

	if ( ! is_array( $records ) ) {
		return new WP_REST_Response( array( 'error' => 'bad payload' ), 400 );
	}

	foreach ( $records as $record ) {
		if ( empty( $record['id'] ) ) {
			continue;
		}

		try {
			// Тип события определяем по составу payload: EspoCRM присылает id
			// и то поле, которое изменилось.
			if ( array_key_exists( 'status', $record ) ) {
				minka_crm_handle_lead( $record['id'], (string) $record['status'] );
			} elseif ( array_key_exists( 'stage', $record ) ) {
				minka_crm_handle_opportunity( $record['id'], (string) $record['stage'] );
			}
		} catch ( Throwable $e ) {
			minka_crm_log( 'ошибка обработки вебхука: ' . $e->getMessage() );
		}
	}

	// EspoCRM считает доставку успешной по коду 200 и иначе будет повторять.
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Приём события «создан лид».
 *
 * Отдельный адрес, а не общий эндпоинт: EspoCRM не сообщает в payload, какое
 * событие его вызвало, — присылает только данные записи. Отличить создание
 * лида от смены его статуса по содержимому нельзя, поэтому разводим по URL.
 */
function minka_crm_lead_created_handle( WP_REST_Request $request ) {
	$payload = $request->get_body();

	if ( ! minka_crm_webhook_verify( (string) $request->get_header( 'Signature' ), $payload ) ) {
		minka_crm_log( 'вебхук создания лида отклонён: подпись не совпала' );

		return new WP_REST_Response( array( 'error' => 'invalid signature' ), 401 );
	}

	$records = json_decode( $payload, true );

	if ( ! is_array( $records ) ) {
		return new WP_REST_Response( array( 'error' => 'bad payload' ), 400 );
	}

	foreach ( $records as $record ) {
		if ( empty( $record['id'] ) ) {
			continue;
		}

		try {
			minka_crm_notify_new_lead( $record['id'] );
		} catch ( Throwable $e ) {
			minka_crm_log( 'ошибка оповещения о лиде: ' . $e->getMessage() );
		}
	}

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function minka_crm_register_webhook_route() {
	register_rest_route(
		'minka/v1',
		'/espo-webhook',
		array(
			'methods'             => 'POST',
			'callback'            => 'minka_crm_webhook_handle',
			'permission_callback' => '__return_true', // Доступ проверяется подписью.
		)
	);

	register_rest_route(
		'minka/v1',
		'/espo-lead-created',
		array(
			'methods'             => 'POST',
			'callback'            => 'minka_crm_lead_created_handle',
			'permission_callback' => '__return_true', // Доступ проверяется подписью.
		)
	);
}
add_action( 'rest_api_init', 'minka_crm_register_webhook_route' );
