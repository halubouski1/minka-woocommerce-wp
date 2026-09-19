<?php
/**
 * Оповещения о новых лидах в Telegram.
 *
 * Сообщения шлёт бот в группу отдела продаж. Источник события — вебхук
 * Lead.create из EspoCRM, а не форма на сайте: так уведомление приходит и по
 * заявкам с сайта, и по лидам, которые менеджер завёл в CRM руками.
 *
 * Пока не заданы MINKA_TG_BOT_TOKEN и MINKA_TG_CHAT_ID, код молчит.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Настройки бота.
 */
function minka_crm_telegram_config() {
	return array(
		'token'  => minka_crm_config( 'MINKA_TG_BOT_TOKEN' ),
		'chat'   => minka_crm_config( 'MINKA_TG_CHAT_ID' ),
		// Для групп с темами: id темы, куда писать. Необязательно.
		'thread' => minka_crm_config( 'MINKA_TG_THREAD_ID' ),
	);
}

function minka_crm_telegram_ready() {
	$config = minka_crm_telegram_config();

	return '' !== $config['token'] && '' !== $config['chat'];
}

/**
 * Адрес CRM для ссылки в сообщении.
 *
 * Отдельная переменная, а не адрес API: по ссылке будет ходить человек, и
 * host.docker.internal в браузере не откроется.
 */
function minka_crm_espo_ui_url() {
	return rtrim( minka_crm_config( 'MINKA_ESPO_UI_URL' ), '/' );
}

/**
 * Отправляет сообщение в группу.
 *
 * @param string $text Текст в разметке HTML.
 * @return bool
 */
function minka_crm_telegram_send( $text ) {
	if ( ! minka_crm_telegram_ready() ) {
		minka_crm_log( 'Telegram пропущен: не заданы MINKA_TG_BOT_TOKEN / MINKA_TG_CHAT_ID' );

		return false;
	}

	$config = minka_crm_telegram_config();

	$body = array(
		'chat_id'    => $config['chat'],
		'text'       => $text,
		'parse_mode' => 'HTML',
		// Ссылки на CRM и страницу товара не должны разворачиваться в превью.
		'link_preview_options' => wp_json_encode( array( 'is_disabled' => true ) ),
	);

	if ( '' !== $config['thread'] ) {
		$body['message_thread_id'] = $config['thread'];
	}

	$response = wp_remote_post(
		'https://api.telegram.org/bot' . $config['token'] . '/sendMessage',
		array(
			'timeout' => 10,
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		minka_crm_log( 'Telegram — ошибка соединения: ' . $response->get_error_message() );

		return false;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );

	if ( 200 !== $code ) {
		minka_crm_log( 'Telegram — HTTP ' . $code . ': ' . $raw );

		return false;
	}

	return true;
}

/**
 * Экранирует текст для разметки HTML в Telegram.
 *
 * Имя или комментарий вполне могут содержать «<» или «&» — без экранирования
 * Telegram отвергнет всё сообщение целиком.
 */
function minka_crm_tg_escape( $value ) {
	return htmlspecialchars( (string) $value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * Короткая подпись для ссылки на страницу.
 *
 * Полный адрес в сообщении занимает строку и ничего не проясняет — показываем
 * путь, по которому сразу видно, откуда пришёл человек.
 *
 * @param string $url Адрес страницы.
 * @return string
 */
function minka_crm_tg_page_label( $url ) {
	$path  = wp_parse_url( $url, PHP_URL_PATH );
	$query = wp_parse_url( $url, PHP_URL_QUERY );

	$label = $path ? $path : '/';

	if ( $query ) {
		$label .= '?' . $query;
	}

	return mb_strlen( $label ) > 60 ? mb_substr( $label, 0, 57 ) . '…' : $label;
}

/**
 * Человеческое название формы.
 */
function minka_crm_form_title( $key ) {
	$titles = array(
		'showroom' => 'Запись в шоурум',
		'cta'      => 'Узнать наличие',
		'contacts' => 'Вопрос со страницы контактов',
		'gift'     => 'Намёк о подарке',
	);

	return isset( $titles[ $key ] ) ? $titles[ $key ] : '';
}

/**
 * Собирает сообщение о новом лиде.
 *
 * Порядок важен: сверху то, что нужно, чтобы взять трубку, ниже — контекст
 * заявки, в самом низу маркетинговая атрибуция. Менеджеру обычно хватает
 * первых трёх строк.
 *
 * @param array $lead Запись лида из EspoCRM.
 * @return string
 */
function minka_crm_telegram_lead_message( $lead ) {
	$e = 'minka_crm_tg_escape';

	$form  = minka_crm_form_title( isset( $lead['wpFormKey'] ) ? $lead['wpFormKey'] : '' );
	$lines = array();

	$lines[] = '🔔 <b>Новая заявка</b>' . ( $form ? ' — ' . $e( $form ) : '' );
	$lines[] = '';

	$name = isset( $lead['name'] ) ? trim( $lead['name'] ) : '';
	if ( $name ) {
		$lines[] = '👤 <b>' . $e( $name ) . '</b>';
	}

	// Телефон отдаём ссылкой tel: — с телефона можно позвонить в одно касание.
	$phone = ! empty( $lead['phoneNormalized'] ) ? $lead['phoneNormalized'] : ( $lead['phoneNumber'] ?? '' );
	if ( $phone ) {
		$lines[] = '📞 <a href="tel:' . $e( $phone ) . '">' . $e( $phone ) . '</a>';
	}

	if ( ! empty( $lead['emailAddress'] ) ) {
		$lines[] = '✉️ ' . $e( $lead['emailAddress'] );
	}

	if ( ! empty( $lead['preferredChannel'] ) ) {
		$channels = array( 'Phone' => 'Телефон', 'WhatsApp' => 'WhatsApp', 'Viber' => 'Вайбер', 'Telegram' => 'Telegram' );
		$channel  = $channels[ $lead['preferredChannel'] ] ?? $lead['preferredChannel'];

		$lines[] = '💬 Связаться через: ' . $e( $channel );
	}

	// Модель, которой интересуются.
	$product = array_filter(
		array(
			$lead['productName'] ?? '',
			$lead['productSku'] ?? '',
			! empty( $lead['productPrice'] ) ? $lead['productPrice'] . ' ' . ( $lead['productPriceCurrency'] ?? '' ) : '',
		)
	);

	if ( $product ) {
		$lines[] = '';
		$lines[] = '🧥 ' . $e( implode( ' · ', array_map( 'trim', $product ) ) );
	}

	// Дата в CRM хранится как 2026-09-25 — менеджеру привычнее 25.09.2026.
	$visit_date = $lead['requestedVisitDate'] ?? '';
	if ( $visit_date && preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $visit_date, $m ) ) {
		$visit_date = $m[3] . '.' . $m[2] . '.' . $m[1];
	}

	$visit = array_filter( array( $visit_date, $lead['requestedVisitTime'] ?? '' ) );
	if ( $visit ) {
		$lines[] = '📅 Визит: ' . $e( implode( ', ', $visit ) );
	}

	if ( ! empty( $lead['giftRecipientName'] ) || ! empty( $lead['giftRecipientEmail'] ) ) {
		$recipient = array_filter( array( $lead['giftRecipientName'] ?? '', $lead['giftRecipientEmail'] ?? '' ) );

		$lines[] = '🎁 Намёк для: ' . $e( implode( ', ', $recipient ) );
	}

	if ( ! empty( $lead['description'] ) ) {
		$lines[] = '';
		$lines[] = '📝 ' . $e( $lead['description'] );
	}

	// Откуда пришёл: страница, с которой отправлена форма, и точка входа на
	// сайт. Вторую показываем только если она отличается — иначе это та же
	// строка дважды.
	$page    = $lead['pageUrl'] ?? '';
	$landing = $lead['landingPage'] ?? '';

	if ( $page || $landing ) {
		$lines[] = '';
	}

	if ( $page ) {
		$lines[] = '🔗 Страница заявки: <a href="' . $e( $page ) . '">' . $e( minka_crm_tg_page_label( $page ) ) . '</a>';
	}

	if ( $landing && untrailingslashit( $landing ) !== untrailingslashit( $page ) ) {
		$lines[] = '🚪 Зашёл на сайт с: <a href="' . $e( $landing ) . '">' . $e( minka_crm_tg_page_label( $landing ) ) . '</a>';
	}

	if ( ! empty( $lead['referrer'] ) ) {
		$host = wp_parse_url( $lead['referrer'], PHP_URL_HOST );

		if ( $host ) {
			$lines[] = '↩️ Переход с: ' . $e( $host );
		}
	}

	// Атрибуция — одной строкой, чтобы не растягивать сообщение.
	$utm = array_filter(
		array(
			$lead['utmSource'] ?? '',
			$lead['utmMedium'] ?? '',
			$lead['utmCampaign'] ?? '',
		)
	);

	if ( $utm ) {
		$lines[] = '📊 ' . $e( implode( ' / ', $utm ) );
	} elseif ( ! empty( $lead['source'] ) ) {
		$lines[] = '📊 ' . $e( $lead['source'] );
	}

	if ( empty( $lead['marketingConsent'] ) ) {
		$lines[] = '⚠️ Без согласия на маркетинг — события в рекламные кабинеты не уйдут';
	}

	$ui = minka_crm_espo_ui_url();

	if ( $ui && ! empty( $lead['id'] ) ) {
		$lines[] = '';
		$lines[] = '<a href="' . $e( $ui . '/#Lead/view/' . $lead['id'] ) . '">Открыть в CRM</a>';
	}

	return implode( "\n", $lines );
}

/**
 * Обрабатывает событие создания лида.
 *
 * @param string $id Идентификатор лида в EspoCRM.
 */
function minka_crm_notify_new_lead( $id ) {
	if ( ! minka_crm_telegram_ready() ) {
		minka_crm_log( 'Telegram пропущен: не заданы MINKA_TG_BOT_TOKEN / MINKA_TG_CHAT_ID' );

		return;
	}

	$lead = minka_crm_espo_get( 'Lead', $id );

	if ( ! $lead ) {
		return;
	}

	if ( minka_crm_telegram_send( minka_crm_telegram_lead_message( $lead ) ) ) {
		minka_crm_log( 'Telegram: оповещение по лиду ' . $id . ' отправлено' );
	}
}

/**
 * Ссылка на запись в CRM — строкой для вставки в сообщение.
 *
 * @param string $entity Тип сущности: Lead, Opportunity, Contact.
 * @param string $id     Идентификатор записи.
 * @param string $label  Текст ссылки.
 * @return string Пустая строка, если адрес CRM не задан.
 */
function minka_crm_tg_link( $entity, $id, $label ) {
	$ui = minka_crm_espo_ui_url();

	if ( ! $ui || ! $id ) {
		return '';
	}

	return '<a href="' . minka_crm_tg_escape( $ui . '/#' . $entity . '/view/' . $id ) . '">' . minka_crm_tg_escape( $label ) . '</a>';
}

/**
 * Лид признан качественным.
 *
 * Сообщение короткое: подробности уже приходили при создании лида, здесь важен
 * сам факт и кто теперь им занимается.
 *
 * @param array $lead Запись лида.
 * @return string
 */
function minka_crm_telegram_qualified_message( $lead ) {
	$e     = 'minka_crm_tg_escape';
	$lines = array( '✅ <b>Лид квалифицирован</b>', '' );

	if ( ! empty( $lead['name'] ) ) {
		$lines[] = '👤 <b>' . $e( $lead['name'] ) . '</b>';
	}

	$phone = ! empty( $lead['phoneNormalized'] ) ? $lead['phoneNormalized'] : ( $lead['phoneNumber'] ?? '' );
	if ( $phone ) {
		$lines[] = '📞 <a href="tel:' . $e( $phone ) . '">' . $e( $phone ) . '</a>';
	}

	$product = array_filter( array( $lead['productName'] ?? '', $lead['productSku'] ?? '' ) );
	if ( $product ) {
		$lines[] = '🧥 ' . $e( implode( ' · ', $product ) );
	}

	if ( ! empty( $lead['assignedUserName'] ) ) {
		$lines[] = '🧑‍💼 Ответственный: ' . $e( $lead['assignedUserName'] );
	}

	$link = minka_crm_tg_link( 'Lead', $lead['id'] ?? '', 'Открыть в CRM' );
	if ( $link ) {
		$lines[] = '';
		$lines[] = $link;
	}

	return implode( "\n", $lines );
}

/**
 * Сделка закрыта — успешно или нет.
 *
 * Сумму показываем только у выигранных: у проваленной она вводит в заблуждение,
 * денег не было.
 *
 * @param array $opportunity Запись сделки.
 * @param bool  $won         Успешно ли закрыта.
 * @return string
 */
function minka_crm_telegram_deal_message( $opportunity, $won ) {
	$e     = 'minka_crm_tg_escape';
	$lines = array( $won ? '🎉 <b>Продажа состоялась</b>' : '❌ <b>Сделка провалена</b>', '' );

	if ( ! empty( $opportunity['name'] ) ) {
		$lines[] = '📄 ' . $e( $opportunity['name'] );
	}

	if ( $won && ! empty( $opportunity['amount'] ) ) {
		$amount = rtrim( rtrim( number_format( (float) $opportunity['amount'], 2, ',', ' ' ), '0' ), ',' );

		$lines[] = '💰 <b>' . $e( $amount . ' ' . ( $opportunity['amountCurrency'] ?? '' ) ) . '</b>';
	}

	$product = array_filter( array( $opportunity['productName'] ?? '', $opportunity['productSku'] ?? '' ) );
	if ( $product ) {
		$lines[] = '🧥 ' . $e( implode( ' · ', $product ) );
	}

	if ( ! empty( $opportunity['contactName'] ) ) {
		$lines[] = '👤 ' . $e( $opportunity['contactName'] );
	}

	if ( ! empty( $opportunity['assignedUserName'] ) ) {
		$lines[] = '🧑‍💼 Ответственный: ' . $e( $opportunity['assignedUserName'] );
	}

	// Откуда пришёл клиент — видно только на закрытии, и это самое полезное
	// место: сразу понятно, какая кампания действительно приносит деньги.
	$utm = array_filter( array( $opportunity['utmSource'] ?? '', $opportunity['utmCampaign'] ?? '' ) );
	if ( $utm ) {
		$lines[] = '📊 ' . $e( implode( ' / ', $utm ) );
	}

	$link = minka_crm_tg_link( 'Opportunity', $opportunity['id'] ?? '', 'Открыть в CRM' );
	if ( $link ) {
		$lines[] = '';
		$lines[] = $link;
	}

	return implode( "\n", $lines );
}
