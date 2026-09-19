<?php
/**
 * Клиент REST API EspoCRM.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Адрес и ключ приходят из окружения (docker-compose → .env), а не из кода:
 * ключ не должен попадать в репозиторий.
 */
function minka_crm_espo_config() {
	return array(
		'url' => rtrim( minka_crm_config( 'MINKA_ESPO_API_URL' ), '/' ),
		'key' => minka_crm_config( 'MINKA_ESPO_API_KEY' ),
	);
}

function minka_crm_espo_ready() {
	$config = minka_crm_espo_config();

	return '' !== $config['url'] && '' !== $config['key'];
}

function minka_crm_log( $message ) {
	error_log( '[minka-crm] ' . $message );
}

/**
 * Тело последнего неуспешного ответа API.
 *
 * Нужно, чтобы отличить отказ по конкретному полю от общей ошибки и повторить
 * запрос без этого поля.
 */
function minka_crm_last_error( $set = null ) {
	static $last = '';

	if ( null !== $set ) {
		$last = $set;
	}

	return $last;
}

/**
 * Запрос к API.
 *
 * @param string     $method  GET|POST|PUT.
 * @param string     $path    Путь после /api/v1, например 'Lead'.
 * @param array|null $body    Тело запроса.
 * @param array      $query   Параметры строки запроса.
 * @param array      $headers Дополнительные заголовки.
 * @return array|null Ответ, разобранный из JSON, либо null при ошибке.
 */
function minka_crm_espo_request( $method, $path, $body = null, $query = array(), $headers = array() ) {
	if ( ! minka_crm_espo_ready() ) {
		minka_crm_log( 'пропуск запроса: не заданы MINKA_ESPO_API_URL / MINKA_ESPO_API_KEY' );

		return null;
	}

	$config = minka_crm_espo_config();
	$url    = $config['url'] . '/' . ltrim( $path, '/' );

	if ( $query ) {
		$url .= '?' . http_build_query( $query );
	}

	$args = array(
		'method'  => $method,
		'timeout' => 5,
		'headers' => array_merge(
			array(
				'X-Api-Key'    => $config['key'],
				'Content-Type' => 'application/json',
			),
			$headers
		),
	);

	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}

	$response = wp_remote_request( $url, $args );

	if ( is_wp_error( $response ) ) {
		minka_crm_log( $method . ' ' . $path . ' — ошибка соединения: ' . $response->get_error_message() );

		return null;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );

	if ( $code < 200 || $code >= 300 ) {
		minka_crm_last_error( $raw );
		minka_crm_log( $method . ' ' . $path . ' — HTTP ' . $code . ': ' . $raw );

		return null;
	}

	minka_crm_last_error( '' );

	$decoded = json_decode( $raw, true );

	return is_array( $decoded ) ? $decoded : array();
}

/**
 * Читает запись целиком.
 *
 * Вебхук EspoCRM присылает только id и изменившееся поле, а для события Meta
 * нужны телефон, имя, _fbp/_fbc и остальная атрибуция.
 *
 * @param string $entity Тип сущности, например 'Lead'.
 * @param string $id     Идентификатор записи.
 * @return array|null
 */
function minka_crm_espo_get( $entity, $id ) {
	return minka_crm_espo_request( 'GET', $entity . '/' . rawurlencode( $id ) );
}

/**
 * Обновляет запись.
 *
 * @param string $entity  Тип сущности.
 * @param string $id      Идентификатор записи.
 * @param array  $payload Изменяемые поля.
 * @return bool
 */
function minka_crm_espo_update( $entity, $id, $payload ) {
	$result = minka_crm_espo_request( 'PUT', $entity . '/' . rawurlencode( $id ), $payload );

	return null !== $result;
}

/**
 * Ищет лид, уже созданный по этой записи Gravity Forms.
 *
 * Gravity Forms может обработать повторную отправку (двойной клик, ретрай
 * фронтенда), а лид должен остаться один.
 *
 * @param int $entry_id ID записи Gravity Forms.
 * @return string|null ID существующего лида.
 */
function minka_crm_find_lead_by_entry( $entry_id ) {
	$result = minka_crm_espo_request(
		'GET',
		'Lead',
		null,
		array(
			'maxSize' => 1,
			'where'   => array(
				array(
					'type'      => 'equals',
					'attribute' => 'wpGfEntryId',
					'value'     => (int) $entry_id,
				),
			),
		)
	);

	if ( ! $result || empty( $result['list'] ) ) {
		return null;
	}

	return isset( $result['list'][0]['id'] ) ? $result['list'][0]['id'] : null;
}

/**
 * Создаёт лид.
 *
 * Встроенная проверка дублей EspoCRM отключена намеренно: она срабатывает по
 * совпадению имени и вернула бы 409 на вторую заявку того же человека. Для
 * шоурума это нормальный сценарий — спросить про несколько моделей, — а от
 * настоящих повторов защищает проверка по wpGfEntryId выше.
 *
 * @param array $payload Поля лида.
 * @return string|null ID созданного лида.
 */
/**
 * Поле, на котором EspoCRM отклонил запрос.
 */
function minka_crm_rejected_field( $error ) {
	$decoded = json_decode( (string) $error, true );

	if ( ! is_array( $decoded ) ) {
		return '';
	}

	$field = isset( $decoded['messageTranslation']['data']['field'] )
		? $decoded['messageTranslation']['data']['field']
		: '';

	return is_string( $field ) ? $field : '';
}

/**
 * Понятная подпись поля для описания лида.
 */
function minka_crm_field_label( $field ) {
	$labels = array(
		'phoneNumber'        => 'Телефон',
		'emailAddress'       => 'Email',
		'requestedVisitDate' => 'Желаемая дата визита',
		'requestedVisitTime' => 'Желаемое время визита',
		'pageUrl'            => 'Страница отправки',
		'landingPage'        => 'Страница входа',
		'referrer'           => 'Реферер',
		'productUrl'         => 'Ссылка на модель',
	);

	return isset( $labels[ $field ] ) ? $labels[ $field ] : $field;
}

/**
 * Создаёт лид.
 *
 * Встроенная проверка дублей EspoCRM отключена намеренно: она срабатывает по
 * совпадению имени и вернула бы 409 на вторую заявку того же человека. Для
 * шоурума это нормальный сценарий — спросить про несколько моделей, — а от
 * настоящих повторов защищает проверка по wpGfEntryId выше.
 *
 * Формы на сайте — свободный текст, а поля CRM типизированы: телефон должен
 * разбираться как международный номер, дата визита быть датой. Человек пишет
 * «today» или ошибается в номере — и EspoCRM отвергает запись целиком. Терять
 * из-за этого живую заявку недопустимо, поэтому отвергнутое поле убирается,
 * его значение переносится в описание, и запрос повторяется. Менеджер увидит
 * исходный текст и разберётся сам.
 *
 * @param array $payload Поля лида.
 * @return string|null ID созданного лида.
 */
function minka_crm_create_lead( $payload ) {
	$headers     = array( 'X-Skip-Duplicate-Check' => 'true' );
	$description = isset( $payload['description'] ) ? $payload['description'] : '';
	$dropped     = array();

	// Ограничение на случай, если EspoCRM отвергает поле, которого нет в
	// payload: цикл обязан завершиться.
	for ( $attempt = 0; $attempt < 5; $attempt++ ) {
		$result = minka_crm_espo_request( 'POST', 'Lead', $payload, array(), $headers );

		if ( $result && isset( $result['id'] ) ) {
			return $result['id'];
		}

		$field = minka_crm_rejected_field( minka_crm_last_error() );

		if ( '' === $field || ! isset( $payload[ $field ] ) ) {
			return null;
		}

		$dropped[ minka_crm_field_label( $field ) ] = $payload[ $field ];

		minka_crm_log( 'поле ' . $field . ' отвергнуто EspoCRM, повтор без него' );

		unset( $payload[ $field ] );

		// Нормализованный номер без основного поля смысла не имеет.
		if ( 'phoneNumber' === $field ) {
			unset( $payload['phoneNormalized'] );
		}

		$notes = array();
		foreach ( $dropped as $label => $value ) {
			$notes[] = $label . ' (не принято CRM): ' . $value;
		}

		$payload['description'] = '' === $description
			? implode( "\n", $notes )
			: implode( "\n", $notes ) . "\n\n" . $description;
	}

	return null;
}
