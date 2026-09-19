<?php
/**
 * Отправка событий в Meta Conversions API.
 *
 * Пока не заданы MINKA_META_PIXEL_ID и MINKA_META_CAPI_TOKEN, весь код здесь
 * молча ничего не делает: интеграция должна быть готова до появления
 * рекламного аккаунта, но не падать без него.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Версия Graph API. На сентябрь 2026 актуальна v26.0.
 */
function minka_crm_capi_version() {
	$version = minka_crm_config( 'MINKA_META_GRAPH_VERSION' );

	return '' !== $version ? $version : 'v26.0';
}

function minka_crm_capi_config() {
	return array(
		// Тот же источник, что и у браузерного пикселя: поле в админке, затем окружение.
		'pixel' => function_exists( 'minka_crm_pixel_id' ) ? minka_crm_pixel_id() : minka_crm_config( 'MINKA_META_PIXEL_ID' ),
		'token' => minka_crm_config( 'MINKA_META_CAPI_TOKEN' ),
		'test'  => minka_crm_config( 'MINKA_META_TEST_EVENT_CODE' ),
	);
}

function minka_crm_capi_ready() {
	$config = minka_crm_capi_config();

	return '' !== $config['pixel'] && '' !== $config['token'];
}

/**
 * Требовать ли маркетинговое согласие для серверных событий.
 *
 * По умолчанию — да. Юридический мастер Complianz на сайте не пройден до
 * конца, поэтому строгий режим безопаснее: событие не уходит вовсе, если
 * человек отказал в маркетинге. Ослабить можно переменной окружения, но это
 * осознанное решение владельца, а не настройка по умолчанию.
 */
function minka_crm_capi_requires_marketing_consent() {
	// Строго по умолчанию: отключается только явным '0'.
	return minka_crm_config_flag( 'MINKA_CAPI_REQUIRE_MARKETING_CONSENT', true );
}

/**
 * SHA-256 по правилам Meta: обрезать пробелы и привести к нижнему регистру.
 */
function minka_crm_hash( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	return hash( 'sha256', mb_strtolower( $value, 'UTF-8' ) );
}

/**
 * Телефон хэшируется без плюса и разделителей — только цифры.
 */
function minka_crm_hash_phone( $value ) {
	$digits = preg_replace( '/\D+/', '', (string) $value );

	return '' === $digits ? '' : hash( 'sha256', $digits );
}

/**
 * Собирает user_data из записи EspoCRM (лида или сделки).
 *
 * @param array $record  Запись из API EspoCRM.
 * @param bool  $consent Есть ли маркетинговое согласие.
 * @return array
 */
function minka_crm_capi_user_data( $record, $consent ) {
	$data = array();

	$phone = isset( $record['phoneNormalized'] ) ? $record['phoneNormalized'] : '';
	if ( $phone ) {
		$data['ph'] = array( minka_crm_hash_phone( $phone ) );
	}

	$email = isset( $record['emailAddress'] ) ? $record['emailAddress'] : '';
	if ( $email ) {
		$data['em'] = array( minka_crm_hash( $email ) );
	}

	if ( ! empty( $record['firstName'] ) ) {
		$data['fn'] = array( minka_crm_hash( $record['firstName'] ) );
	}

	if ( ! empty( $record['lastName'] ) ) {
		$data['ln'] = array( minka_crm_hash( $record['lastName'] ) );
	}

	// external_id должен быть одинаковым во всех событиях одного человека,
	// иначе Meta посчитает Lead и Purchase за разных пользователей. Поэтому у
	// сделки берётся id исходного лида, а не её собственный.
	$external = ! empty( $record['externalId'] ) ? $record['externalId'] : ( isset( $record['id'] ) ? $record['id'] : '' );

	if ( $external ) {
		$data['external_id'] = array( minka_crm_hash( $external ) );
	}

	// _fbp и _fbc — идентификаторы рекламной cookie. Прикладываем их только
	// при маркетинговом согласии, даже если само событие разрешено отправить.
	if ( $consent ) {
		if ( ! empty( $record['fbp'] ) ) {
			$data['fbp'] = $record['fbp'];
		}

		if ( ! empty( $record['fbc'] ) ) {
			$data['fbc'] = $record['fbc'];
		}
	}

	if ( ! empty( $record['clientIpAddress'] ) ) {
		$data['client_ip_address'] = $record['clientIpAddress'];
	}

	if ( ! empty( $record['clientUserAgent'] ) ) {
		$data['client_user_agent'] = $record['clientUserAgent'];
	}

	return $data;
}

/**
 * Отправляет одно событие.
 *
 * @param array $event Готовое событие в формате Meta.
 * @return bool Ушло ли событие.
 */
function minka_crm_capi_send( $event ) {
	if ( ! minka_crm_capi_ready() ) {
		minka_crm_log( 'CAPI пропущен: не заданы MINKA_META_PIXEL_ID / MINKA_META_CAPI_TOKEN' );

		return false;
	}

	$config = minka_crm_capi_config();

	$body = array( 'data' => array( $event ) );

	if ( '' !== $config['test'] ) {
		$body['test_event_code'] = $config['test'];
	}

	$url = sprintf(
		'https://graph.facebook.com/%s/%s/events?access_token=%s',
		minka_crm_capi_version(),
		rawurlencode( $config['pixel'] ),
		rawurlencode( $config['token'] )
	);

	$response = wp_remote_post(
		$url,
		array(
			'timeout' => 10,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		minka_crm_log( 'CAPI ' . $event['event_name'] . ' — ошибка соединения: ' . $response->get_error_message() );

		return false;
	}

	$code = wp_remote_retrieve_response_code( $response );

	if ( $code < 200 || $code >= 300 ) {
		minka_crm_log( 'CAPI ' . $event['event_name'] . ' — HTTP ' . $code . ': ' . wp_remote_retrieve_body( $response ) );

		return false;
	}

	minka_crm_log( 'CAPI ' . $event['event_name'] . ' отправлено, event_id ' . $event['event_id'] );

	return true;
}

/**
 * Собирает событие и отправляет его.
 *
 * @param string $name        Имя события (Lead, QualifiedLead, Purchase).
 * @param string $event_id    Идентификатор для дедупликации.
 * @param array  $record      Запись EspoCRM.
 * @param string $source      action_source по классификации Meta.
 * @param string $source_url  URL страницы, только для action_source = website.
 * @param array  $custom_data Данные события (value, currency, content_ids).
 * @return bool
 */
function minka_crm_capi_event( $name, $event_id, $record, $source, $source_url = '', $custom_data = array() ) {
	$consent = ! empty( $record['marketingConsent'] );

	if ( minka_crm_capi_requires_marketing_consent() && ! $consent ) {
		minka_crm_log( 'CAPI ' . $name . ' не отправлен: нет маркетингового согласия' );

		return false;
	}

	$event = array(
		'event_name'    => $name,
		'event_time'    => time(),
		'event_id'      => $event_id,
		'action_source' => $source,
		'user_data'     => minka_crm_capi_user_data( $record, $consent ),
	);

	// Для website-событий Meta требует event_source_url, для остальных он
	// необязателен, но допустим. Шлём везде, где URL известен: у офлайн-покупки
	// это карточка модели, с которой всё началось.
	if ( $source_url ) {
		$event['event_source_url'] = $source_url;
	}

	if ( $custom_data ) {
		$event['custom_data'] = $custom_data;
	}

	return minka_crm_capi_send( $event );
}
