<?php
/**
 * Проверка связи с Telegram.
 *
 * Отправляет в группу тестовое сообщение и показывает ответ API. Ничего в CRM
 * не трогает. Нужен, чтобы отделить проблему с токеном или chat_id от проблемы
 * в цепочке вебхуков.
 */

require '/var/www/html/wp-load.php';

if ( ! function_exists( 'minka_crm_telegram_ready' ) ) {
	fwrite( STDERR, "Плагин minka-crm не загружен.\n" );
	exit( 1 );
}

if ( ! minka_crm_telegram_ready() ) {
	fwrite( STDERR, "Не заданы MINKA_TG_BOT_TOKEN и/или MINKA_TG_CHAT_ID — заполните .env и выполните docker compose up -d\n" );
	exit( 1 );
}

$config = minka_crm_telegram_config();

echo 'Чат: ', $config['chat'], $config['thread'] ? ' (тема ' . $config['thread'] . ')' : '', "\n\n";

add_filter(
	'http_response',
	function ( $r, $a, $url ) {
		if ( false !== strpos( $url, 'api.telegram.org' ) ) {
			echo '  ответ Telegram: ', wp_remote_retrieve_response_code( $r ), ' ',
				mb_substr( wp_remote_retrieve_body( $r ), 0, 300 ), "\n";
		}
		return $r;
	},
	10,
	3
);

// Берём последний лид, если он есть: так видно настоящее сообщение, а не рыбу.
$leads = minka_crm_espo_request( 'GET', 'Lead', null, array( 'maxSize' => 1, 'orderBy' => 'createdAt', 'order' => 'desc' ) );
$lead  = ! empty( $leads['list'][0] ) ? $leads['list'][0] : null;

$text = $lead
	? "🧪 <b>Проверка связи</b>\n\n" . minka_crm_telegram_lead_message( $lead )
	: "🧪 <b>Проверка связи</b>\n\nБот подключён к группе. Лидов в CRM пока нет, поэтому показать нечего.";

echo minka_crm_telegram_send( $text )
	? "\nСообщение отправлено — проверьте группу.\n"
	: "\nНе отправлено, смотрите ответ выше.\n";
