<?php
/**
 * Проверка связи с Meta Conversions API.
 *
 * Отправляет по одному событию каждого типа — Lead, QualifiedLead, Purchase —
 * на синтетических данных. В CRM ничего не создаётся и не меняется: скрипт
 * отвечает ровно на один вопрос — доходят ли события до Meta и как они там
 * выглядят.
 *
 * Запускать через scripts/capi-smoke-test.sh
 */

defined( 'ABSPATH' ) || require '/var/www/html/wp-load.php';

if ( ! function_exists( 'minka_crm_capi_ready' ) ) {
	fwrite( STDERR, "Плагин minka-crm не загружен.\n" );
	exit( 1 );
}

if ( ! minka_crm_capi_ready() ) {
	fwrite( STDERR, "Не заданы MINKA_META_PIXEL_ID и/или MINKA_META_CAPI_TOKEN — заполните .env и пересоздайте контейнер.\n" );
	exit( 1 );
}

$config = minka_crm_capi_config();

if ( '' === $config['test'] ) {
	fwrite( STDERR, "Не задан MINKA_META_TEST_EVENT_CODE — без него события не попадут в Test Events.\n" );
	exit( 1 );
}

echo "Pixel: ", $config['pixel'], "\nTest Events code: ", $config['test'], "\n\n";

// Показываем, что именно ушло и что ответила Meta.
add_filter(
	'http_response',
	function ( $response, $args, $url ) {
		if ( false === strpos( $url, 'graph.facebook.com' ) ) {
			return $response;
		}

		echo "  ответ Meta: ", wp_remote_retrieve_response_code( $response ), " ", wp_remote_retrieve_body( $response ), "\n\n";

		return $response;
	},
	10,
	3
);

/**
 * Синтетический человек. Телефон и почта заведомо несуществующие — они лишь
 * проверяют, что хэширование и сопоставление работают.
 */
$person = array(
	'id'               => 'smoke-test-lead',
	'firstName'        => 'Тест',
	'lastName'         => 'Проверкин',
	'phoneNormalized'  => '+375291112233',
	'emailAddress'     => 'smoke-test@example.com',
	'fbp'              => 'fb.1.' . ( time() * 1000 ) . '.1234567890',
	'fbc'              => 'fb.1.' . ( time() * 1000 ) . '.IwARsmokeTest',
	'clientIpAddress'  => '178.124.10.5',
	'clientUserAgent'  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/152.0.0.0 Safari/537.36',
	'marketingConsent' => true,
);

$product = array(
	'content_ids'  => array( 'ART-0001' ),
	'content_type' => 'product',
	'content_name' => 'Норковая шуба Aurora',
);

$site = get_option( 'siteurl' );

$cases = array(
	array(
		'name'   => 'Lead',
		'id'     => wp_generate_uuid4(),
		'source' => 'website',
		'url'    => $site . '/catalog/norkovaya-shuba-aurora/',
		'custom' => $product,
	),
	array(
		'name'   => 'QualifiedLead',
		'id'     => wp_generate_uuid4(),
		'source' => 'system_generated',
		'url'    => '',
		'custom' => $product,
	),
	array(
		'name'   => 'Purchase',
		'id'     => wp_generate_uuid4(),
		'source' => 'physical_store',
		'url'    => '',
		'custom' => array_merge( $product, array( 'value' => 1200, 'currency' => get_woocommerce_currency() ) ),
	),
);

$failed = 0;

foreach ( $cases as $case ) {
	echo $case['name'], " (action_source: ", $case['source'], ", event_id: ", $case['id'], ")\n";

	$ok = minka_crm_capi_event( $case['name'], $case['id'], $person, $case['source'], $case['url'], $case['custom'] );

	if ( ! $ok ) {
		++$failed;
		echo "  НЕ ОТПРАВЛЕНО — смотрите строку выше\n\n";
	}
}

if ( $failed ) {
	echo "Не отправлено событий: ", $failed, " из ", count( $cases ), "\n";
	exit( 1 );
}

echo "Все три события отправлены. Откройте Events Manager → Test Events.\n";
