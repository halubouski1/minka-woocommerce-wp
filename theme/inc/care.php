<?php
/**
 * Страница «Уход за шубой».
 *
 * Тексты берутся из настроек (MINKA → Уход за шубой), а дефолты повторяют
 * вёрстку и используются же при первичном заполнении полей.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Открыта ли сейчас страница ухода.
 */
function minka_is_care_page() {
	$page = get_page_by_path( 'care' );

	return $page && is_page( $page->ID );
}

/**
 * Вступительный блок: заголовок, текст, врезка и фото.
 */
function minka_care_intro() {
	$defaults = array(
		'title' => 'Правила ухода за шубой',
		'desc'  => 'Норка — прочный и неприхотливый мех, но он живой. <br> Ему вредят тепло, прямой свет, сжатие и влага, оставленная надолго. Несколько простых привычек — и шуба сохранит блеск, форму и густоту подпушка на много сезонов.<br> Ниже — всё, что мы советуем нашим клиентам.',
		'note'  => 'При правильном уходе норковая шуба служит 10–15 сезонов',
	);

	return array(
		'title' => minka_option( 'care_title', $defaults['title'] ),
		'desc'  => minka_option( 'care_desc', $defaults['desc'] ),
		'note'  => minka_option( 'care_note', $defaults['note'] ),
		'image' => minka_image( minka_option( 'care_image' ), 'assets/img/care.webp', 761, 599 ),
	);
}

/**
 * Дефолтная таблица «Можно и нужно / Нельзя».
 */
function minka_care_default_rows() {
	return array(
		array( 'can' => 'Хранить на широких мягких плечиках', 'cannot' => 'Сушить феном, на батарее или на солнце' ),
		array( 'can' => 'Использовать дышащий тканевый чехол', 'cannot' => 'Хранить в полиэтиленовом/вакуумном чехле' ),
		array( 'can' => 'Сушить при комнатной температуре', 'cannot' => 'Брызгать духи и лак для волос на мех' ),
		array( 'can' => 'Стряхивать снег и капли сразу', 'cannot' => 'Носить тяжёлую сумку на одном плече' ),
		array( 'can' => 'Расчёсывать мех специальной щёткой по ворсу', 'cannot' => 'Стирать, гладить, отжимать' ),
		array( 'can' => 'Раз в 1–2 сезона — профессиональная меховая чистка', 'cannot' => 'Сдавливать в тесном шкафу надолго' ),
	);
}

/**
 * Таблица ухода: заголовки колонок и строки.
 */
function minka_care_table() {
	$rows = minka_option( 'care_rows' );

	if ( is_array( $rows ) ) {
		$rows = array_values(
			array_filter(
				array_map(
					static function ( $row ) {
						return array(
							'can'    => isset( $row['care_can'] ) ? $row['care_can'] : '',
							'cannot' => isset( $row['care_cannot'] ) ? $row['care_cannot'] : '',
						);
					},
					$rows
				),
				static function ( $row ) {
					return $row['can'] || $row['cannot'];
				}
			)
		);
	}

	return array(
		'head_left'  => minka_option( 'care_head_left', 'Можно и нужно' ),
		'head_right' => minka_option( 'care_head_right', 'Нельзя' ),
		'rows'       => $rows ? $rows : minka_care_default_rows(),
	);
}

/**
 * Дефолтные признаки, что шубе пора в чистку.
 */
function minka_care_default_steps() {
	return array(
		'Мех потерял блеск, выглядит тусклым.',
		'Появился стойкий запах (духи, дым, влага).',
		'Ворс свалялся в местах трения (воротник, манжеты).',
		'Заметные загрязнения <br> на подоле или рукавах.',
	);
}

/**
 * Блок «Когда шубе нужна профессиональная чистка».
 */
function minka_care_cleaning() {
	$rows  = minka_option( 'care_steps' );
	$steps = array();

	if ( is_array( $rows ) ) {
		$steps = array_values(
			array_filter(
				array_map(
					static function ( $row ) {
						return isset( $row['care_step'] ) ? $row['care_step'] : '';
					},
					$rows
				)
			)
		);
	}

	return array(
		'title' => minka_option( 'care_cleaning_title', 'Когда шубе нужна профессиональная чистка' ),
		'steps' => empty( $steps ) ? minka_care_default_steps() : $steps,
	);
}

/**
 * Нижний блок с заявкой.
 */
function minka_care_cta() {
	return array(
		'title' => minka_option( 'care_cta_title', 'Остались вопросы по уходу?' ),
		'text'  => minka_option( 'care_cta_text', 'Напишите нам — подскажем, как ухаживать именно за вашей моделью. Для клиентов MINKA консультация бесплатна' ),
	);
}

/**
 * Вопросы и ответы на странице ухода: свои или те же, что на главной.
 */
function minka_care_faq_items() {
	$inherit = true;

	if ( function_exists( 'get_field' ) ) {
		$inherit = (bool) get_field( 'care_faq_inherit', 'option' );
	}

	if ( $inherit ) {
		return minka_faq_items();
	}

	$items = minka_faq_from_rows( minka_option( 'care_faq' ) );

	return $items ? $items : minka_faq_items();
}

/**
 * Второй блок вопросов на странице ухода.
 *
 * В вёрстке блоков два: первый идёт после правил ухода, второй — после
 * признаков профессиональной чистки. Пока второй список не заполнен,
 * там показывается первый, как было раньше.
 */
function minka_care_faq_second_items() {
	$items = minka_faq_from_rows( minka_option( 'care_faq_second' ) );

	return $items ? $items : minka_care_faq_items();
}

/**
 * Весь текст статьи одной строкой: и обычный контент, и блоки ACF.
 *
 * Статьи собираются из гибкого поля post_blocks, поэтому post_content у них
 * пустой — считать по нему что-либо нельзя.
 */
function minka_post_plain_text( $post ) {
	$text = strip_shortcodes( $post->post_content );

	$blocks = function_exists( 'get_field' ) ? get_field( 'post_blocks', $post->ID ) : null;

	foreach ( (array) $blocks as $block ) {
		// Значения полей — строки (заголовок, текст, подпись) и вложенные
		// репитеры таблицы; служебный acf_fc_layout на счёт слов не влияет.
		array_walk_recursive(
			$block,
			function ( $value ) use ( &$text ) {
				if ( is_string( $value ) ) {
					$text .= ' ' . $value;
				}
			}
		);
	}

	return wp_strip_all_tags( $text );
}

/**
 * Время чтения статьи — как в вёрстке блока «Читайте также».
 */
function minka_reading_time( $post ) {
	// str_word_count считает только латиницу, поэтому слова ищем сами.
	$words   = preg_match_all( '/[\p{L}\p{N}]+/u', minka_post_plain_text( $post ) );
	$minutes = max( 1, (int) ceil( $words / 180 ) );

	$mod10  = $minutes % 10;
	$mod100 = $minutes % 100;

	if ( 1 === $mod10 && 11 !== $mod100 ) {
		$label = 'минута';
	} elseif ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 10 || $mod100 >= 20 ) ) {
		$label = 'минуты';
	} else {
		$label = 'минут';
	}

	return sprintf( 'Время чтения: %d %s', $minutes, $label );
}

/**
 * Последние статьи для блока «Читайте также».
 */
function minka_recent_posts( $limit = 3, $exclude = 0 ) {
	return get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => (int) $limit,
			'exclude'          => $exclude ? array( (int) $exclude ) : array(),
			'suppress_filters' => false,
		)
	);
}
