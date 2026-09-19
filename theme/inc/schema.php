<?php
/**
 * Структурированные данные.
 *
 * Разметку страниц, хлебных крошек и статей отдаёт Yoast. Здесь добиваем то,
 * до чего он не дотягивается.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Включает разметку Product на карточках товара.
 *
 * WooCommerce собирает её сам — с ценой, артикулом, наличием и изображениями,
 * — но запускает генератор из хука woocommerce_single_product_summary. Шаблон
 * товара в теме собственный и этот хук не вызывает, поэтому разметка не
 * появлялась вовсе: в выдаче у моделей не было ни цены, ни наличия.
 *
 * Вызываем генератор напрямую. Вывод уже подключён самим WooCommerce к
 * wp_footer, а тема вызывает get_footer(), так что печатать ничего не нужно.
 *
 * Данные берутся из карточки товара, отдельно редактировать их негде и не
 * нужно: меняется цена или наличие — меняется и разметка.
 */
function minka_generate_product_schema() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! isset( WC()->structured_data ) ) {
		return;
	}

	$product = wc_get_product( get_queried_object_id() );

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	WC()->structured_data->generate_product_data( $product );
}
add_action( 'wp', 'minka_generate_product_schema' );

/**
 * Дополняет разметку товара тем, чего не знает WooCommerce.
 *
 * Он собирает название, цену, артикул и наличие, но бренд, цвет, материал
 * и размерный ряд лежат в атрибутах — Google использует их в товарных
 * сниппетах и при подборе по характеристикам. Заодно подставляем все фото
 * галереи и связываем продавца с узлом организации, чтобы граф был цельным.
 *
 * @param array      $data    Разметка WooCommerce.
 * @param WC_Product $product Товар.
 * @return array
 */
function minka_product_schema( $data, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $data;
	}

	$specs = array();

	foreach ( minka_product_specs( $product ) as $spec ) {
		$specs[ $spec['label'] ] = $spec['value'];
	}

	$data['brand'] = array(
		'@type' => 'Brand',
		'name'  => get_bloginfo( 'name' ),
	);

	// Соответствие атрибутов свойствам schema.org.
	$map = array(
		'Цвет'     => 'color',
		'Мех'      => 'material',
		'Фасон'    => 'pattern',
		'Размер'   => 'size',
		'Капюшон'  => null,
	);

	foreach ( $map as $label => $property ) {
		if ( $property && ! empty( $specs[ $label ] ) ) {
			$data[ $property ] = $specs[ $label ];
		}
	}

	// Размерный ряд точнее, чем поле «Размер», если он заполнен.
	if ( ! empty( $specs['Размерный ряд'] ) ) {
		$data['size'] = $specs['Размерный ряд'];
	}

	// Остальные характеристики — парами свойство/значение: так их читают
	// и Google, и языковые модели, которые разбирают карточку.
	$additional = array();

	foreach ( $specs as $label => $value ) {
		if ( isset( $map[ $label ] ) || 'Размерный ряд' === $label ) {
			continue;
		}

		$additional[] = array(
			'@type' => 'PropertyValue',
			'name'  => $label,
			'value' => $value,
		);
	}

	if ( $additional ) {
		$data['additionalProperty'] = $additional;
	}

	// Все фотографии модели, а не только главная.
	$images = array();

	foreach ( minka_product_gallery( $product ) as $image ) {
		$images[] = $image['url'];
	}

	if ( count( $images ) > 1 ) {
		$data['image'] = $images;
	}

	if ( isset( $data['offers'] ) ) {
		foreach ( $data['offers'] as $index => $offer ) {
			$data['offers'][ $index ]['itemCondition'] = 'https://schema.org/NewCondition';
			$data['offers'][ $index ]['seller']        = array( '@id' => home_url( '/#organization' ) );
		}
	}

	return $data;
}
add_filter( 'woocommerce_structured_data_product', 'minka_product_schema', 10, 2 );

/**
 * Правит узел статьи: автор и объём текста.
 *
 * Автором WordPress ставит учётную запись, от которой опубликована запись
 * («admin»), — читателю выдачи это ничего не говорит. Статьи блога пишутся
 * от лица магазина, поэтому автор и издатель у них один и тот же.
 *
 * wordCount WordPress считает по полю записи, а текст статьи собран из
 * блоков ACF — в разметку уходило 11 слов вместо нескольких сотен.
 *
 * @param array $data Узел Article.
 * @return array
 */
function minka_article_schema( $data ) {
	$post = get_post();

	if ( $post ) {
		$words = preg_match_all( '/[\p{L}\p{N}]+/u', minka_post_plain_text( $post ) );

		if ( $words ) {
			$data['wordCount'] = $words;
		}
	}

	$data['author'] = array( '@id' => home_url( '/#organization' ) );

	return $data;
}
add_filter( 'wpseo_schema_article', 'minka_article_schema' );

/**
 * Убирает узел автора-человека, на который больше никто не ссылается.
 *
 * Автором статьи стала организация, а Person с аватаром из Gravatar
 * остался бы в графе висеть сам по себе.
 */
function minka_remove_orphan_person( $graph ) {
	if ( ! is_array( $graph ) || ! is_singular( 'post' ) ) {
		return $graph;
	}

	foreach ( $graph as $index => $node ) {
		if ( isset( $node['@type'] ) && 'Person' === $node['@type'] ) {
			unset( $graph[ $index ] );
		}
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'minka_remove_orphan_person', 20 );

/**
 * Вопросы и ответы, которые показаны на текущей странице.
 *
 * Блок FAQ выводится почти везде, но список у каждой страницы свой —
 * повторяем ту же логику, что и в шаблонах. Разметка собирается в wp_head,
 * до того как отрисуется сам блок, поэтому спросить его напрямую нельзя.
 */
function minka_current_faq_items() {
	if ( is_front_page() ) {
		return minka_faq_items();
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		return minka_product_faq_items();
	}

	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return minka_catalog_faq_items();
	}

	// На странице ухода блоков вопросов два — в разметку идут оба.
	if ( minka_is_care_page() ) {
		$items = array();

		foreach ( array_merge( minka_care_faq_items(), minka_care_faq_second_items() ) as $item ) {
			if ( ! empty( $item['question'] ) ) {
				$items[ $item['question'] ] = $item;
			}
		}

		return array_values( $items );
	}

	if ( minka_is_about_page() ) {
		return minka_about_faq_items();
	}

	if ( minka_is_contacts_page() ) {
		return minka_contacts_faq_items();
	}

	if ( is_singular( 'post' ) ) {
		return minka_post_faq_items();
	}

	if ( is_home() ) {
		return minka_blog_faq_items();
	}

	if ( minka_is_favorites_page() ) {
		return minka_faq_items();
	}

	return array();
}

/**
 * Добавляет вопросы и ответы в граф Yoast.
 *
 * Страница объявляется одновременно WebPage и FAQPage, а вопросы вешаются
 * на неё через mainEntity — так же, как это делает собственный FAQ-блок
 * Yoast. Отдельный тег script не нужен: один граф на страницу.
 */
function minka_add_faq_schema( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}

	$items = minka_current_faq_items();

	if ( ! $items ) {
		return $graph;
	}

	$questions = array();
	$refs      = array();

	foreach ( $items as $index => $item ) {
		if ( empty( $item['question'] ) ) {
			continue;
		}

		$id = home_url( '/#faq-question-' . ( $index + 1 ) );

		$questions[] = array(
			'@type'          => 'Question',
			'@id'            => $id,
			'name'           => $item['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => isset( $item['answer'] ) ? $item['answer'] : '',
			),
		);

		$refs[] = array( '@id' => $id );
	}

	if ( ! $questions ) {
		return $graph;
	}

	foreach ( $graph as &$node ) {
		if ( ! isset( $node['@type'] ) ) {
			continue;
		}

		$types = (array) $node['@type'];

		if ( ! in_array( 'WebPage', $types, true ) && ! in_array( 'CollectionPage', $types, true ) && ! in_array( 'ItemPage', $types, true ) ) {
			continue;
		}

		$types[]          = 'FAQPage';
		$node['@type']    = array_values( array_unique( $types ) );
		$node['mainEntity'] = $refs;

		break;
	}

	unset( $node );

	return array_merge( $graph, $questions );
}
add_filter( 'wpseo_schema_graph', 'minka_add_faq_schema' );

/**
 * Данные организации для микроразметки.
 *
 * Телефон и почта берутся из тех же полей, что показываются на странице
 * контактов: раньше в разметке жили свои захардкоженные значения, и они
 * расходились с тем, что видел посетитель.
 *
 * @return array
 */
function minka_organization_data() {
	$phone = minka_option( 'contacts_phone_number' );

	if ( ! $phone ) {
		// Запасной вариант — то, что показано на странице, без форматирования.
		$phone = preg_replace( '/[^\d+]/', '', (string) minka_option( 'contacts_phone_label' ) );
	}

	return array(
		'description' => minka_option( 'org_description' ),
		'phone'       => $phone,
		'email'       => minka_option( 'contacts_email' ),
		'street'      => minka_option( 'org_street' ),
		'locality'    => minka_option( 'org_locality' ),
		'country'     => minka_option( 'org_country' ),
		'postal'      => minka_option( 'org_postal' ),
		'lat'         => minka_option( 'org_lat' ),
		'lng'         => minka_option( 'org_lng' ),
		'days'        => minka_option( 'org_days' ),
		'open'        => minka_option( 'org_open' ),
		'close'       => minka_option( 'org_close' ),
		'price_range' => minka_option( 'org_price_range' ),
	);
}

/**
 * Профили компании в соцсетях — свойство sameAs.
 *
 * Берём ссылки из подвала и со страницы контактов; заглушки вроде «#»
 * и внутренние ссылки в разметку не попадают: sameAs — это только
 * внешние страницы той же организации.
 */
function minka_organization_same_as() {
	$urls = array();

	foreach ( array( minka_socials(), minka_option( 'contacts_socials' ) ) as $rows ) {
		foreach ( (array) $rows as $row ) {
			$url = '';

			if ( isset( $row['url'] ) ) {
				$url = $row['url'];
			} elseif ( isset( $row['social_url'] ) ) {
				$url = $row['social_url'];
			}

			$url = trim( (string) $url );

			if ( ! $url || 0 !== strpos( $url, 'http' ) ) {
				continue;
			}

			// Свой же домен профилем не считается.
			if ( false !== strpos( $url, wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
				continue;
			}

			$urls[] = $url;
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * Часы работы в формате schema.org.
 *
 * Google показывает их в карточке компании, поэтому дни отдаём списком,
 * а время — в 24-часовом виде.
 */
function minka_organization_hours( $data ) {
	if ( empty( $data['days'] ) || empty( $data['open'] ) || empty( $data['close'] ) ) {
		return array();
	}

	$sets = array(
		'all'  => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ),
		'mon6' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
		'mon5' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
	);

	if ( ! isset( $sets[ $data['days'] ] ) ) {
		return array();
	}

	return array(
		array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => $sets[ $data['days'] ],
			'opens'     => $data['open'],
			'closes'    => $data['close'],
		),
	);
}

/**
 * Добавляет карточку магазина в граф Yoast.
 *
 * Раньше блок ClothingStore печатала тема отдельным тегом script — рядом с
 * графом Yoast, но никак с ним не связанный. Поисковику это не запрещено, но
 * узлы не ссылались друг на друга, и данные приходилось держать в двух местах.
 *
 * Теперь узел встраивается в граф Yoast и объявляется издателем сайта, а
 * значения приходят из админки.
 *
 * @param array $graph Узлы графа.
 * @return array
 */
function minka_add_organization_schema( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}

	$data = minka_organization_data();
	$id   = home_url( '/#organization' );

	// Логотип: отдельный узел ImageObject, на него ссылаются и организация,
	// и статьи блога (publisher.logo).
	$logo_id  = (int) minka_option( 'org_logo' );
	$logo_src = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : null;

	$logo = array(
		'@type'      => 'ImageObject',
		'@id'        => home_url( '/#logo' ),
		'url'        => $logo_src ? $logo_src[0] : minka_asset( 'assets/img/og.jpg' ),
		'contentUrl' => $logo_src ? $logo_src[0] : minka_asset( 'assets/img/og.jpg' ),
		'caption'    => get_bloginfo( 'name' ),
	);

	if ( $logo_src ) {
		$logo['width']  = $logo_src[1];
		$logo['height'] = $logo_src[2];
	}

	$node = array(
		'@type' => 'ClothingStore',
		'@id'   => $id,
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
		'logo'  => array( '@id' => $logo['@id'] ),
		'image' => array( '@id' => $logo['@id'] ),
	);

	foreach ( array( 'description' => 'description', 'telephone' => 'phone', 'email' => 'email', 'priceRange' => 'price_range' ) as $key => $source ) {
		if ( ! empty( $data[ $source ] ) ) {
			$node[ $key ] = $data[ $source ];
		}
	}

	// Адрес добавляем, только если есть что сказать: пустой PostalAddress
	// валидаторы отмечают как ошибку.
	$address = array_filter(
		array(
			'streetAddress'   => $data['street'],
			'postalCode'      => $data['postal'],
			'addressLocality' => $data['locality'],
			'addressCountry'  => $data['country'],
		)
	);

	if ( $address ) {
		$node['address'] = array( '@type' => 'PostalAddress' ) + $address;
	}

	// Координаты шоурума — для локальной выдачи и карт.
	if ( ! empty( $data['lat'] ) && ! empty( $data['lng'] ) ) {
		$node['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $data['lat'],
			'longitude' => $data['lng'],
		);
	}

	$hours = minka_organization_hours( $data );

	if ( $hours ) {
		$node['openingHoursSpecification'] = $hours;
	}

	$same_as = minka_organization_same_as();

	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}

	$graph[] = $logo;
	$graph[] = $node;

	// Связываем: сайт, страница и статьи издаются этой организацией.
	// У Article издатель обязателен для расширенных результатов Google,
	// и через него подтягивается логотип.
	foreach ( $graph as &$item ) {
		if ( empty( $item['@type'] ) || ! empty( $item['publisher'] ) ) {
			continue;
		}

		$types = (array) $item['@type'];

		if ( array_intersect( $types, array( 'WebSite', 'WebPage', 'Article', 'BlogPosting', 'ItemPage', 'CollectionPage' ) ) ) {
			$item['publisher'] = array( '@id' => $id );
		}
	}

	unset( $item );

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'minka_add_organization_schema' );
