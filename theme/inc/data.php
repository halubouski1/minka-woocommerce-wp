<?php
/**
 * Контент шапки, подвала и главной.
 *
 * Каждый блок берётся из ACF, а если ACF выключен или поле не заполнено —
 * возвращается дефолт из вёрстки. Дефолты же используются при первичном
 * заполнении полей (inc/seed.php), поэтому тексты живут в одном месте.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Значение поля на странице опций.
 */
function minka_option( $name, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, 'option' );

	return ( null === $value || '' === $value || array() === $value ) ? $default : $value;
}

/**
 * Приводит значение ACF-поля «Изображение» к виду, удобному для вывода.
 * При пустом значении подставляет картинку из вёрстки.
 */
function minka_image( $value, $fallback_path = '', $width = 0, $height = 0 ) {
	if ( is_array( $value ) && ! empty( $value['url'] ) ) {
		return array(
			'url'    => $value['url'],
			'width'  => ! empty( $value['width'] ) ? (int) $value['width'] : $width,
			'height' => ! empty( $value['height'] ) ? (int) $value['height'] : $height,
			'alt'    => isset( $value['alt'] ) ? $value['alt'] : '',
		);
	}

	if ( ! $fallback_path ) {
		return null;
	}

	return array(
		'url'    => minka_asset( $fallback_path ),
		'width'  => $width,
		'height' => $height,
		'alt'    => '',
	);
}

/**
 * Приводит значение ACF-поля «Ссылка» к массиву title/url/target.
 */
function minka_link( $value ) {
	if ( ! is_array( $value ) || empty( $value['url'] ) ) {
		return null;
	}

	return array(
		'title'  => isset( $value['title'] ) ? $value['title'] : '',
		'url'    => $value['url'],
		'target' => ! empty( $value['target'] ) ? $value['target'] : '',
	);
}

/* -------------------------------------------------------------------------
 * Шапка
 * ---------------------------------------------------------------------- */

/**
 * Телефон в шапке или null, если он отключён в настройках.
 */
function minka_header_phone() {
	if ( function_exists( 'get_field' ) && ! get_field( 'header_phone_show', 'option' ) ) {
		return null;
	}

	$defaults = minka_phone();
	$number   = minka_option( 'header_phone_number', $defaults[0] );
	$label    = minka_option( 'header_phone_label', $defaults[1] );

	if ( ! $number && ! $label ) {
		return null;
	}

	return array(
		'number' => $number ? $number : $label,
		'label'  => $label ? $label : $number,
	);
}

/**
 * Дефолтное меню из вёрстки.
 */
function minka_default_menu_items() {
	return array(
		array(
			'title'      => 'Каталог',
			'url'        => minka_page_url( 'catalog' ),
			'text'       => 'Более 200 моделей норковых шуб в наличии и под заказ. От сдержанной классики до актуальных фасонов — каждая модель прошла отбор по качеству меха, кроя и фурнитуры. Коллекция обновляется регулярно.',
			'image_path' => 'assets/img/menu-card-1.webp',
		),
		array(
			'title'      => 'О нас',
			'url'        => minka_page_url( 'about' ),
			'text'       => 'MINKA — это 12 лет работы с натуральной норкой. Наша специализация — только норковые шубы. Закупаем мех напрямую на международных аукционах, шьём на собственном производстве. Без посредников и лишних наценок.',
			'image_path' => 'assets/img/menu-card-2.webp',
		),
		array(
			'title'      => 'Уход за шубой',
			'url'        => minka_page_url( 'care' ),
			'text'       => 'Норка — живой мех. Несколько простых привычек сохранят блеск, форму и густоту подпушка на много сезонов. Здесь — всё, что мы советуем нашим клиентам: от хранения до поведения в дождь и снег.',
			'image_path' => 'assets/img/menu-card-3.webp',
		),
		array(
			'title'      => 'Контакты',
			'url'        => minka_page_url( 'contacts' ),
			'text'       => 'Шоурум для индивидуальной примерки, телефон для записи и любых вопросов. Приезжайте — покажем модели вживую, поможем с размером и фасоном. Мы на связи удобным вам способом.',
			'image_path' => 'assets/img/menu-card-4.webp',
		),
		array(
			'title'      => 'Блог',
			'url'        => minka_page_url( 'blog' ),
			'text'       => 'Статьи о мехе, выборе норковой шубы и тонкостях, которые полезно знать перед покупкой. Опыт, наблюдения и рекомендации от команды MINKA.',
			'image_path' => 'assets/img/menu-card-5.webp',
		),
	);
}

/**
 * Пункты меню: ссылка + карточка превью справа.
 */
function minka_menu_items() {
	$rows = minka_option( 'menu_items' );

	if ( ! is_array( $rows ) ) {
		$items = array();
		foreach ( minka_default_menu_items() as $i => $item ) {
			$items[] = array(
				'key'   => 'item-' . ( $i + 1 ),
				'title' => $item['title'],
				'url'   => $item['url'],
				'text'  => $item['text'],
				'image' => minka_image( null, $item['image_path'], 915, 583 ),
			);
		}

		return $items;
	}

	$items = array();
	foreach ( $rows as $i => $row ) {
		$link = minka_link( isset( $row['item_link'] ) ? $row['item_link'] : null );
		if ( ! $link || ! $link['title'] ) {
			continue;
		}

		$items[] = array(
			'key'   => 'item-' . ( $i + 1 ),
			'title' => $link['title'],
			'url'   => $link['url'],
			'text'  => isset( $row['item_text'] ) ? $row['item_text'] : '',
			'image' => minka_image( isset( $row['item_image'] ) ? $row['item_image'] : null, '', 915, 583 ),
		);
	}

	return $items;
}

/**
 * Карточка меню, которая показывается, пока никакой пункт не наведён.
 */
function minka_menu_default_card() {
	$text = minka_option( 'menu_default_text', 'MINKA — только натуральная норка. Прямые поставки с международных пушных аукционов, без посредников. Более 200 моделей в наличии и под заказ. Индивидуальная примерка в шоуруме, помощь с выбором <br> и бережный уход — всё, чтобы шуба радовала десятилетиями.' );

	return array(
		'text'  => $text,
		'image' => minka_image( minka_option( 'menu_default_image' ), 'assets/img/menu-card-0.webp', 915, 583 ),
	);
}

/* -------------------------------------------------------------------------
 * Страница «не найдено»
 * ---------------------------------------------------------------------- */

/**
 * Тексты и картинка страницы 404.
 */
function minka_notfound_content() {
	return array(
		'label'          => minka_option( 'notfound_label', 'страница не найдена' ),
		'title'          => minka_option( 'notfound_title', 'Возможно, она была перемещена или удалена' ),
		'text'           => minka_option( 'notfound_text', 'Перейдите в каталог — там собраны все актуальные модели' ),
		'catalog_button' => minka_option( 'notfound_catalog_button', 'В каталог' ),
		'home_button'    => minka_option( 'notfound_home_button', 'На главную' ),
		'image'          => minka_image( minka_option( 'notfound_image' ), 'assets/img/404.webp', 567, 287 ),
	);
}

/* -------------------------------------------------------------------------
 * Подвал
 * ---------------------------------------------------------------------- */

/**
 * Дефолтная навигация подвала (левая колонка).
 */
function minka_default_footer_nav() {
	return array(
		array( 'title' => 'Каталог', 'url' => minka_page_url( 'catalog' ) ),
		array( 'title' => 'О нас', 'url' => minka_page_url( 'about' ) ),
		array( 'title' => 'Уход за шубой', 'url' => minka_page_url( 'care' ) ),
		array( 'title' => 'Контакты', 'url' => minka_page_url( 'contacts' ) ),
		array( 'title' => 'Блог', 'url' => minka_page_url( 'blog' ) ),
	);
}

/**
 * Дефолтные правовые ссылки (правая колонка).
 */
function minka_default_footer_policy() {
	return array(
		array( 'title' => 'Политика конфиденциальности', 'url' => minka_page_url( 'privacy' ) ),
		array( 'title' => 'Публичная оферта', 'url' => minka_page_url( 'terms' ) ),
	);
}

/**
 * Общий разбор repeater'а со ссылками.
 */
function minka_links_from_rows( $rows, $sub_field, $defaults ) {
	if ( ! is_array( $rows ) ) {
		return $defaults;
	}

	$links = array();
	foreach ( $rows as $row ) {
		$link = minka_link( isset( $row[ $sub_field ] ) ? $row[ $sub_field ] : null );
		if ( $link && $link['title'] ) {
			$links[] = $link;
		}
	}

	return $links;
}

function minka_footer_nav() {
	return minka_links_from_rows( minka_option( 'footer_nav' ), 'nav_link', minka_default_footer_nav() );
}

function minka_footer_policy_links() {
	return minka_links_from_rows( minka_option( 'footer_policy' ), 'policy_link', minka_default_footer_policy() );
}

/**
 * Дефолтные соцсети из вёрстки.
 */
function minka_default_socials() {
	return array(
		array( 'label' => 'Instagram', 'url' => '#', 'icon_path' => 'assets/icons/ig.svg', 'width' => 24, 'height' => 24 ),
		array( 'label' => 'WhatsApp', 'url' => '#', 'icon_path' => 'assets/icons/whatsapp.svg', 'width' => 24, 'height' => 24 ),
		array( 'label' => 'Telegram', 'url' => '#', 'icon_path' => 'assets/icons/tg.svg', 'width' => 21, 'height' => 19 ),
	);
}

/**
 * Соцсети подвала. Иконка — любой загруженный файл, в том числе SVG.
 */
function minka_socials() {
	$rows = minka_option( 'footer_socials' );

	if ( ! is_array( $rows ) ) {
		$socials = array();
		foreach ( minka_default_socials() as $item ) {
			$socials[] = array(
				'label' => $item['label'],
				'url'   => $item['url'],
				'icon'  => minka_image( null, $item['icon_path'], $item['width'], $item['height'] ),
			);
		}

		return $socials;
	}

	$socials = array();
	foreach ( $rows as $row ) {
		$label = isset( $row['social_label'] ) ? $row['social_label'] : '';
		$icon  = minka_image( isset( $row['social_icon'] ) ? $row['social_icon'] : null, '', 24, 24 );

		if ( ! $icon ) {
			continue;
		}

		$socials[] = array(
			'label' => $label,
			'url'   => isset( $row['social_url'] ) && $row['social_url'] ? $row['social_url'] : '#',
			'icon'  => $icon,
		);
	}

	return $socials;
}

/* -------------------------------------------------------------------------
 * Главная
 * ---------------------------------------------------------------------- */

/**
 * Дефолтный FAQ из вёрстки.
 */
function minka_default_faq() {
	return array(
		array(
			'question' => 'Как отличить натуральную норку от подделки?',
			'answer'   => 'У натуральной норки мягкая эластичная мездра и живой блеск ворса, густая упругая подпушь; изделие лёгкое и тёплое. Искусственный мех жёстче, ворс одинаковой длины и «стеклянно» блестит. При покупке просите документы и гарантию на изделие.',
		),
		array(
			'question' => 'Можно ли примерить перед покупкой?',
			'answer'   => 'Да. В нашем шоуруме можно примерить любую понравившуюся модель и оценить посадку, цвет и длину. Запишитесь по телефону — заранее подберём варианты под ваш размер и фасон.',
		),
		array(
			'question' => 'Как выбрать размер, если я в другом городе?',
			'answer'   => 'Снимите мерки (обхват груди, талии, бёдер, длину рукава и изделия) и пришлите нам — поможем с размером. При необходимости отправим дополнительные фото и видео модели, а обмен по размеру согласуем заранее.',
		),
		array(
			'question' => 'Даёте ли гарантию и возможен ли возврат?',
			'answer'   => 'На каждое изделие действует гарантия. Возврат и обмен товара надлежащего качества возможны в сроки, установленные законодательством, при сохранении товарного вида, ярлыков и пломб.',
		),
		array(
			'question' => 'Как ухаживать за норковой шубой?',
			'answer'   => 'Храните шубу на широких плечиках в дышащем чехле, сушите при комнатной температуре без фена и батарей, снег стряхивайте сразу. Не наносите на мех духи и лак. Раз в 1–2 сезона — профессиональная меховая чистка.',
		),
	);
}

/**
 * Разбирает строки repeater'а с вопросами.
 */
function minka_faq_from_rows( $rows ) {
	$items = array();

	foreach ( (array) $rows as $row ) {
		$question = isset( $row['faq_question'] ) ? $row['faq_question'] : '';

		if ( $question ) {
			$items[] = array(
				'question' => $question,
				'answer'   => isset( $row['faq_answer'] ) ? $row['faq_answer'] : '',
			);
		}
	}

	return $items;
}

/**
 * Вопросы и ответы на главной.
 */
function minka_faq_items() {
	$rows = minka_option( 'faq_items' );

	if ( ! is_array( $rows ) ) {
		return minka_default_faq();
	}

	return minka_faq_from_rows( $rows );
}

/**
 * Вопросы и ответы в каталоге: либо свой список, либо тот же, что на главной.
 */
function minka_catalog_faq_items() {
	$inherit = true;

	if ( function_exists( 'get_field' ) ) {
		$inherit = (bool) get_field( 'catalog_faq_inherit', 'option' );
	}

	if ( $inherit ) {
		return minka_faq_items();
	}

	$items = minka_faq_from_rows( minka_option( 'catalog_faq' ) );

	return $items ? $items : minka_faq_items();
}

/**
 * Дефолтные отзывы из вёрстки: карточка с фото, карточка на двух покупателей,
 * ещё одна с фото и последняя — с кнопкой в каталог.
 */
function minka_default_reviews() {
	$lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent nulla dui, fringilla sed leo sed, vehicula sollicitudin dui. Mauris finibus ex metus, eu sollicitudin sem vehicula fringilla.';

	return array(
		array(
			'type'       => 'image',
			'name'       => 'Имя покупателя',
			'text'       => $lorem,
			'image_path' => 'assets/img/review-card-1.webp',
			'image_alt'  => 'Покупательница MINKA',
		),
		array(
			'type'   => 'double',
			'blocks' => array(
				array( 'name' => 'Имя покупателя2', 'text' => $lorem ),
				array( 'name' => 'Имя покупателя3', 'text' => $lorem ),
			),
		),
		array(
			'type'       => 'image',
			'name'       => 'Имя покупателя4',
			'text'       => $lorem,
			'image_path' => 'assets/img/review-card-2.webp',
			'image_alt'  => 'Покупательница MINKA',
		),
		array(
			'type' => 'cta',
			'name' => 'Имя покупателя5',
			'text' => $lorem,
			'link' => array( 'title' => 'Смотреть каталог', 'url' => minka_page_url( 'catalog' ), 'target' => '' ),
		),
	);
}

/**
 * Отзывы для слайдера «О наших шубах говорят».
 */
function minka_reviews() {
	$rows = minka_option( 'reviews_items' );

	if ( ! is_array( $rows ) ) {
		$reviews = array();
		foreach ( minka_default_reviews() as $row ) {
			if ( 'image' === $row['type'] ) {
				$row['image']        = minka_image( null, $row['image_path'], 452, 535 );
				$row['image']['alt'] = $row['image_alt'];
			}
			$reviews[] = $row;
		}

		return $reviews;
	}

	$reviews = array();
	foreach ( $rows as $row ) {
		$type = isset( $row['acf_fc_layout'] ) ? $row['acf_fc_layout'] : '';

		if ( 'image' === $type ) {
			$reviews[] = array(
				'type'  => 'image',
				'name'  => isset( $row['review_name'] ) ? $row['review_name'] : '',
				'text'  => isset( $row['review_text'] ) ? $row['review_text'] : '',
				'image' => minka_image( isset( $row['review_image'] ) ? $row['review_image'] : null, '', 452, 535 ),
			);
		} elseif ( 'double' === $type ) {
			$blocks = array();
			foreach ( (array) ( isset( $row['review_blocks'] ) ? $row['review_blocks'] : array() ) as $block ) {
				$blocks[] = array(
					'name' => isset( $block['review_name'] ) ? $block['review_name'] : '',
					'text' => isset( $block['review_text'] ) ? $block['review_text'] : '',
				);
			}

			$reviews[] = array(
				'type'   => 'double',
				'blocks' => $blocks,
			);
		} elseif ( 'cta' === $type ) {
			$link = minka_link( isset( $row['review_link'] ) ? $row['review_link'] : null );

			$reviews[] = array(
				'type' => 'cta',
				'name' => isset( $row['review_name'] ) ? $row['review_name'] : '',
				'text' => isset( $row['review_text'] ) ? $row['review_text'] : '',
				'link' => $link ? $link : array( 'title' => 'Смотреть каталог', 'url' => minka_page_url( 'catalog' ), 'target' => '' ),
			);
		}
	}

	return $reviews;
}
