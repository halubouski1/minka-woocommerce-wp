<?php
/**
 * Одноразовая подготовка магазина: адрес каталога, атрибуты товаров,
 * формат цены и демонстрационные товары.
 *
 * Демо-товары нужны, чтобы каталог и фильтры было на чём проверить.
 * Их можно спокойно удалить из админки — повторно они не появятся.
 */

defined( 'ABSPATH' ) || exit;

define( 'MINKA_SHOP_SEED_VERSION', 18 );

/**
 * Атрибуты товара и их значения — те же, что в макете фильтров.
 */
function minka_shop_attributes() {
	return array(
		'fason'  => array(
			'label' => 'Фасон',
			'terms' => array( 'Прямая', 'Приталенная', 'Трапеция', 'Оверсайз' ),
		),
		'color'  => array(
			'label' => 'Цвет',
			'terms' => array( 'Коричневый', 'Серый', 'Чёрный', 'Белый' ),
		),
		'size'   => array(
			'label' => 'Размер',
			'terms' => array( '42–44', '46–48', '50–52', '54–56' ),
		),
		'length' => array(
			'label' => 'Длина',
			'terms' => array( 'Короткая', 'Средняя', 'Длинная', 'Макси' ),
		),
	);
}

/**
 * Создаёт глобальные атрибуты и их термины.
 */
function minka_create_product_attributes() {
	foreach ( minka_shop_attributes() as $slug => $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $slug );

		if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
			wc_create_attribute(
				array(
					'name'         => $attribute['label'],
					'slug'         => $slug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);
		}

		// Таксономия регистрируется на init, а мы можем быть уже позже — регистрируем вручную.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy(
				$taxonomy,
				'product',
				array(
					'hierarchical' => false,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			);
		}

		foreach ( $attribute['terms'] as $term ) {
			$existing = get_term_by( 'name', $term, $taxonomy );

			if ( ! $existing ) {
				wp_insert_term( $term, $taxonomy, array( 'slug' => minka_translit_slug( $term ) ) );
				continue;
			}

			// Термины, созданные раньше, могли получить слаг из процентов.
			if ( false !== strpos( $existing->slug, '%' ) ) {
				wp_update_term( $existing->term_id, $taxonomy, array( 'slug' => minka_translit_slug( $term ) ) );
			}
		}
	}
}

/**
 * Страница избранного: адрес /favorites/, к которому ведёт ссылка в шапке.
 */
function minka_create_favorites_page() {
	$existing = get_page_by_path( 'favorites' );

	if ( $existing ) {
		update_post_meta( $existing->ID, '_wp_page_template', 'template-favorites.php' );

		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => 'Избранное',
			'post_name'    => 'favorites',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '',
		)
	);

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'template-favorites.php' );
	}
}

/**
 * Страницы темы, которые собираются своим шаблоном.
 */
function minka_create_template_page( $slug, $title, $template ) {
	$existing = get_page_by_path( $slug );

	if ( $existing ) {
		update_post_meta( $existing->ID, '_wp_page_template', $template );

		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '',
		)
	);

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', $template );
	}
}

/**
 * Блог должен жить по /blog/, а главная — оставаться главной.
 *
 * Для этого WordPress нужны две страницы: одна назначается главной,
 * вторая — страницей записей.
 */
function minka_setup_blog_page() {
	$blog = get_page_by_path( 'blog' );

	if ( ! $blog ) {
		$blog_id = wp_insert_post(
			array(
				'post_title'  => 'Блог',
				'post_name'   => 'blog',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
	} else {
		$blog_id = $blog->ID;
	}

	$home = get_page_by_path( 'home' );

	if ( ! $home ) {
		$home_id = wp_insert_post(
			array(
				'post_title'  => 'Главная',
				'post_name'   => 'home',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
	} else {
		$home_id = $home->ID;
	}

	if ( ! $blog_id || is_wp_error( $blog_id ) || ! $home_id || is_wp_error( $home_id ) ) {
		return;
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );
	update_option( 'page_for_posts', $blog_id );

	flush_rewrite_rules();
}

/**
 * Адреса: статьи лежат под /blog/, товары — под /catalog/.
 */
function minka_setup_permalinks() {
	if ( '/blog/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/blog/%postname%/' );
	}

	// У WooCommerce свой набор баз для товаров и таксономий.
	$permalinks = (array) get_option( 'woocommerce_permalinks', array() );

	if ( ! isset( $permalinks['product_base'] ) || '/catalog' !== $permalinks['product_base'] ) {
		$permalinks['product_base'] = '/catalog';
		update_option( 'woocommerce_permalinks', $permalinks );
	}

	flush_rewrite_rules();
}

/**
 * Демонстрационные статьи блога — чтобы список и статья были не пустыми.
 */
function minka_create_demo_posts() {
	$image = minka_import_theme_image( 'assets/img/post-img-main.webp' );

	$posts = array(
		array(
			'Как выбрать размер норковой шубы, если покупаете онлайн',
			'Снимите мерки и сверьтесь с таблицей — этого достаточно, чтобы не ошибиться с размером даже без примерки.',
		),
		array(
			'Чем отличается поперечный раскрой от продольного',
			'Раскрой меняет и силуэт, и то, как шуба ведёт себя в носке. Разбираем, кому что подойдёт.',
		),
		array(
			'Как хранить шубу летом, чтобы мех не потускнел',
			'Тепло, свет и сжатие вредят меху сильнее, чем мороз. Несколько правил хранения на тёплый сезон.',
		),
		array(
			'Аукционный мех: что означают Saga Furs и Kopenhagen Fur',
			'Что стоит за клеймами аукционов и почему это влияет на цену готового изделия.',
		),
		array(
			'Пять признаков, что шубе пора в профессиональную чистку',
			'Тусклый ворс, запах и свалявшийся мех на воротнике — сигналы, которые лучше не откладывать.',
		),
		array(
			'Норка, соболь или лиса: чем они отличаются в носке',
			'Сравниваем вес, теплоту и износостойкость, чтобы выбор был осознанным.',
		),
	);

	$body = '<h2>Коротко о главном</h2>
<p>Норка — живой материал: он реагирует на влагу, тепло и то, как вещь висит в шкафу. Ниже — то, что мы советуем клиентам MINKA из собственной практики.</p>
<ul>
<li>Храните изделие на широких мягких плечиках.</li>
<li>Используйте дышащий тканевый чехол вместо полиэтилена.</li>
<li>Сушите мех при комнатной температуре, без фена и батарей.</li>
</ul>
<h2>Что важно помнить</h2>
<p>Хорошая шуба служит десятилетиями, если за ней ухаживать. Раз в один-два сезона отдавайте изделие в профессиональную меховую чистку — обычная химчистка для меха не подходит.</p>';

	foreach ( $posts as $index => $item ) {
		list( $title, $excerpt ) = $item;

		$slug     = minka_translit_slug( $title );
		$existing = get_page_by_title( $title, OBJECT, 'post' );

		if ( $existing ) {
			// Записи, созданные раньше, получили слаг из процентов.
			if ( false !== strpos( $existing->post_name, '%' ) ) {
				wp_update_post(
					array(
						'ID'        => $existing->ID,
						'post_name' => $slug,
					)
				);
			}

			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_excerpt' => $excerpt,
				'post_content' => $body,
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $index * 3 ) . ' days' ) ),
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) && $image ) {
			set_post_thumbnail( $post_id, $image );
		}
	}
}

/**
 * Наполняет первую демо-статью блоками ровно как в вёрстке post.html:
 * заголовки, текст со списками, картинка с подписью и таблица.
 */
function minka_seed_demo_post_blocks() {
	if ( ! function_exists( 'update_field' ) ) {
		return;
	}

	$post = get_page_by_title( 'Как выбрать размер норковой шубы, если покупаете онлайн', OBJECT, 'post' );

	if ( ! $post || get_field( 'post_blocks', $post->ID ) ) {
		return;
	}

	$lorem_long  = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent nulla dui, fringilla sed leo sed, vehicula sollicitudin dui. Mauris finibus ex metus, eu sollicitudin sem vehicula fringilla. Vivamus ut vulputate urna. Nulla non elit nulla. Nullam efficitur neque ut lorem pretium, nec finibus sem accumsan. Integer ipsum diam, mattis vel viverra nec, rutrum quis orci. Sed non urna eget ligula pharetra dictum. Fusce ullamcorper tincidunt sagittis. Praesent at mi vitae sem dapibus maximus';
	$lorem_short = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
	$lorem_mid   = 'Praesent nulla dui, fringilla sed leo sed, vehicula sollicitudin dui.';
	$cell        = 'Сушить феном, на батарее или на солнце';

	$columns = array(
		array( 'column' => $cell ),
		array( 'column' => $cell ),
		array( 'column' => $cell ),
	);

	$rows = array();
	for ( $i = 0; $i < 6; $i++ ) {
		$rows[] = array(
			'title' => $cell,
			'cells' => array(
				array( 'cell' => $cell ),
				array( 'cell' => $cell ),
				array( 'cell' => $cell ),
			),
		);
	}

	$blocks = array(
		array(
			'acf_fc_layout' => 'heading2',
			'heading'       => $lorem_short,
		),
		array(
			'acf_fc_layout' => 'text',
			'text'          => '<p>' . $lorem_long . ' <a href="#">ut ut ex.</a></p>
<p>Aenean eget elit in ex tempus eleifend in eget est. Nam id ultrices arcu, ut ullamcorper neque. Nullam vehicula erat quis dolor condimentum vulputate. Proin ultrices dui diam, a varius arcu mollis et. Fusce purus purus, dignissim nec dignissim vitae, efficitur et felis. Praesent erat dui, volutpat eget eros at, pharetra lacinia elit. Sed fermentum, sapien a tempor hendrerit, ante nibh consequat ex, id lacinia velit lectus vitae tellus. Fusce sed arcu sit amet enim aliquam hendrerit cursus id odio. Suspendisse pulvinar erat lacus, ut sollicitudin ligula elementum id. Mauris euismod eros non quam varius suscipit.</p>
<ul>
<li>' . $lorem_short . '</li>
<li>' . $lorem_mid . '</li>
<li>' . $lorem_short . '</li>
</ul>
<ol>
<li>' . $lorem_short . '</li>
<li>' . $lorem_mid . '</li>
<li>' . $lorem_short . '</li>
</ol>',
		),
		array(
			'acf_fc_layout' => 'heading3',
			'heading'       => $lorem_short,
		),
		array(
			'acf_fc_layout' => 'text',
			'text'          => '<p>' . $lorem_long . ' ut ut ex.</p>',
		),
		array(
			'acf_fc_layout' => 'image',
			'image'         => minka_import_theme_image( 'assets/img/post-img-1.webp' ),
			'caption'       => $lorem_short,
		),
		array(
			'acf_fc_layout' => 'text',
			'text'          => '<p>' . $lorem_long . ' ut ut ex.</p>',
		),
		array(
			'acf_fc_layout' => 'table',
			'columns'       => $columns,
			'rows'          => $rows,
		),
	);

	update_field( 'field_minka_post_blocks', $blocks, $post->ID );
}

/**
 * Каталог должен открываться по /catalog/, а не по /shop/.
 */
function minka_set_catalog_slug() {
	$shop_id = (int) get_option( 'woocommerce_shop_page_id' );

	if ( ! $shop_id ) {
		return;
	}

	$shop = get_post( $shop_id );

	if ( ! $shop || 'catalog' === $shop->post_name ) {
		return;
	}

	wp_update_post(
		array(
			'ID'         => $shop_id,
			'post_name'  => 'catalog',
			'post_title' => 'Каталог',
		)
	);

	// Иначе wc_get_page_permalink() в этом же запросе отдаст ещё старый адрес.
	clean_post_cache( $shop_id );
	flush_rewrite_rules();
}

/**
 * Формат цены под макет: «$ 1200».
 */
function minka_set_price_format() {
	update_option( 'woocommerce_currency', 'USD' );
	update_option( 'woocommerce_currency_pos', 'left_space' );
	update_option( 'woocommerce_price_num_decimals', 0 );
	update_option( 'woocommerce_price_thousand_sep', '' );
	update_option( 'woocommerce_default_catalog_orderby', 'popularity' );
}

/**
 * Свежая установка WooCommerce прячет витрину за заглушкой «Скоро открытие».
 * Каталогу она мешает; вернуть режим можно в WooCommerce → Настройки → Общие.
 */
function minka_disable_coming_soon() {
	update_option( 'woocommerce_coming_soon', 'no' );
}

/**
 * Список демонстрационных моделей: название, цена, фасон, цвет,
 * размер, длина и наличие.
 */
function minka_demo_product_models() {
	return array(
		array( 'Норковая шуба Aurora', 1200, 'Прямая', 'Коричневый', '46–48', 'Средняя', 'instock' ),
		array( 'Норковая шуба Bellona', 1450, 'Приталенная', 'Чёрный', '42–44', 'Длинная', 'instock' ),
		array( 'Норковая шуба Cassia', 980, 'Трапеция', 'Серый', '50–52', 'Короткая', 'onbackorder' ),
		array( 'Норковая шуба Delia', 2100, 'Оверсайз', 'Белый', '54–56', 'Макси', 'instock' ),
		array( 'Норковая шуба Elara', 1750, 'Прямая', 'Чёрный', '46–48', 'Длинная', 'instock' ),
		array( 'Норковая шуба Fiora', 1320, 'Приталенная', 'Коричневый', '42–44', 'Средняя', 'onbackorder' ),
		array( 'Норковая шуба Gaia', 2450, 'Оверсайз', 'Серый', '50–52', 'Макси', 'instock' ),
		array( 'Норковая шуба Helia', 890, 'Трапеция', 'Белый', '46–48', 'Короткая', 'instock' ),
		array( 'Норковая шуба Iris', 1680, 'Прямая', 'Серый', '54–56', 'Длинная', 'onbackorder' ),
		array( 'Норковая шуба Juno', 1990, 'Приталенная', 'Чёрный', '50–52', 'Макси', 'instock' ),
		array( 'Норковая шуба Kira', 1150, 'Трапеция', 'Коричневый', '42–44', 'Средняя', 'instock' ),
		array( 'Норковая шуба Lumi', 2280, 'Оверсайз', 'Белый', '46–48', 'Длинная', 'instock' ),
	);
}

/**
 * Демонстрационные товары: разные атрибуты, цены и наличие,
 * чтобы фильтры и сортировка были видны в работе.
 */
function minka_create_demo_products() {
	$models = minka_demo_product_models();

	$main_image    = minka_import_theme_image( 'assets/img/popular-card-1.webp' );
	$hover_image   = minka_import_theme_image( 'assets/img/review-card-1.webp' );
	$attribute_map = array( 'fason', 'color', 'size', 'length' );

	foreach ( $models as $model ) {
		list( $name, $price, $fason, $color, $size, $length, $stock ) = $model;

		if ( get_page_by_title( $name, OBJECT, 'product' ) ) {
			continue;
		}

		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_slug( minka_translit_slug( $name ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( $price );
		$product->set_stock_status( $stock );
		$product->set_short_description( 'Натуральный мех норки, фабричный пошив, гарантия 12 месяцев.' );

		if ( $main_image ) {
			$product->set_image_id( $main_image );
		}

		if ( $hover_image ) {
			$product->set_gallery_image_ids( array( $hover_image ) );
		}

		$attributes = array();
		foreach ( array( $fason, $color, $size, $length ) as $index => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute_map[ $index ] );
			$term     = get_term_by( 'name', $value, $taxonomy );

			if ( ! $term ) {
				continue;
			}

			$attribute = new WC_Product_Attribute();
			$attribute->set_id( wc_attribute_taxonomy_id_by_name( $attribute_map[ $index ] ) );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( array( $term->term_id ) );
			$attribute->set_visible( true );
			$attribute->set_variation( false );

			$attributes[] = $attribute;
		}

		$product->set_attributes( $attributes );
		$product->save();
	}
}

/**
 * После смены адреса витрины ссылки, сохранённые в полях ACF, ведут на старый
 * /shop/. Разово переписываем их на актуальный адрес каталога.
 */
function minka_refresh_saved_catalog_links() {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$catalog = minka_catalog_base_url();
	$stale   = home_url( '/shop/' );

	$fix = static function ( $link ) use ( $catalog, $stale ) {
		if ( is_array( $link ) && isset( $link['url'] ) && untrailingslashit( $link['url'] ) === untrailingslashit( $stale ) ) {
			$link['url'] = $catalog;
		}

		return $link;
	};

	$menu = get_field( 'menu_items', 'option' );
	if ( is_array( $menu ) ) {
		foreach ( $menu as &$row ) {
			if ( isset( $row['item_link'] ) ) {
				$row['item_link'] = $fix( $row['item_link'] );
			}
		}
		unset( $row );
		update_field( 'field_minka_menu_items', $menu, 'option' );
	}

	$nav = get_field( 'footer_nav', 'option' );
	if ( is_array( $nav ) ) {
		foreach ( $nav as &$row ) {
			if ( isset( $row['nav_link'] ) ) {
				$row['nav_link'] = $fix( $row['nav_link'] );
			}
		}
		unset( $row );
		update_field( 'field_minka_footer_nav', $nav, 'option' );
	}

	$reviews = get_field( 'reviews_items', 'option' );
	if ( is_array( $reviews ) ) {
		foreach ( $reviews as &$row ) {
			if ( isset( $row['review_link'] ) ) {
				$row['review_link'] = $fix( $row['review_link'] );
			}
		}
		unset( $row );
		update_field( 'field_minka_reviews', $reviews, 'option' );
	}
}

/**
 * Дозаполняет карточки демо-товаров: артикул, галерея, характеристики,
 * описание и подборка похожих моделей.
 */
function minka_fill_demo_product_details() {
	$gallery_ids = array_filter(
		array(
			minka_import_theme_image( 'assets/img/review-card-1.webp' ),
			minka_import_theme_image( 'assets/img/review-card-2.webp' ),
		)
	);

	$descriptions = array(
		'Тёплая модель из скандинавской норки. Поперечный раскрой делает силуэт мягким и объёмным, а мех — визуально более густым. Лёгкая, несмотря на длину: комфортно носить весь день.',
		'Классический силуэт из аукционного меха высшей селекции. Ровный густой ворс, мягкая эластичная мездра и премиальная фурнитура. Модель хорошо садится по фигуре и не сковывает движения.',
		'Спокойная повседневная модель с продуманной посадкой. Мех прочёсан и подобран по оттенку шкурка к шкурке, подклад приятно скользит по одежде.',
	);

	// Характеристики из макета, которых нет среди фильтруемых атрибутов.
	$extra_specs = array(
		'Мех'            => 'Натуральная норка (скандинавская)',
		'Капюшон'        => 'Есть, съёмный',
		'Подклад'        => 'Вискоза / шёлк',
		'Размерный ряд'  => '42-54',
		'Страна выделки' => 'Греция',
	);

	$number   = 0;
	$products = array();

	foreach ( minka_demo_product_models() as $model ) {
		$existing = get_page_by_title( $model[0], OBJECT, 'product' );

		if ( ! $existing ) {
			continue;
		}

		$product = wc_get_product( $existing->ID );

		if ( ! $product ) {
			continue;
		}

		$number++;
		$products[] = $product->get_id();

		if ( ! $product->get_sku() ) {
			$product->set_sku( sprintf( 'ART-%04d', $number ) );
		}

		$product->set_short_description( $descriptions[ $number % count( $descriptions ) ] );
		$product->set_gallery_image_ids( $gallery_ids );

		// К фильтруемым атрибутам добавляем произвольные — они видны только в карточке.
		$attributes = $product->get_attributes();

		foreach ( $extra_specs as $label => $value ) {
			$key = sanitize_title( $label );

			if ( isset( $attributes[ $key ] ) ) {
				continue;
			}

			$attribute = new WC_Product_Attribute();
			$attribute->set_name( $label );
			$attribute->set_options( array( $value ) );
			$attribute->set_visible( true );
			$attribute->set_variation( false );

			$attributes[ $key ] = $attribute;
		}

		$product->set_attributes( $attributes );
		$product->save();
	}

	// Похожие модели: у каждого товара четыре следующих по списку.
	if ( function_exists( 'update_field' ) && count( $products ) > 4 ) {
		$total = count( $products );

		foreach ( $products as $index => $id ) {
			$related = array();

			for ( $shift = 1; $shift <= 4; $shift++ ) {
				$related[] = $products[ ( $index + $shift ) % $total ];
			}

			update_field( 'field_minka_related_products', $related, $id );
		}
	}
}

/**
 * Разовый прогон подготовки магазина.
 */
function minka_seed_shop() {
	if ( ! function_exists( 'wc_create_attribute' ) || ! class_exists( 'WC_Product_Simple' ) ) {
		return;
	}

	if ( (int) get_option( 'minka_shop_seeded_version' ) >= MINKA_SHOP_SEED_VERSION ) {
		return;
	}

	update_option( 'minka_shop_seeded_version', MINKA_SHOP_SEED_VERSION );

	minka_set_catalog_slug();
	minka_create_favorites_page();
	minka_create_template_page( 'care', 'Уход за шубой', 'template-care.php' );
	minka_create_template_page( 'about-us', 'О нас', 'template-about.php' );
	minka_create_template_page( 'contacts', 'Контакты', 'template-contacts.php' );
	minka_setup_blog_page();
	minka_setup_permalinks();
	minka_create_form( 'showroom' );
	minka_create_form( 'cta' );
	minka_create_form( 'contacts' );
	minka_create_form( 'gift' );
	minka_seed_legal_pages();
	minka_create_demo_posts();
	minka_seed_demo_post_blocks();
	minka_set_price_format();
	minka_disable_coming_soon();
	minka_create_product_attributes();
	minka_create_demo_products();
	minka_fill_demo_product_details();
	minka_refresh_saved_catalog_links();
	minka_flush_price_bounds();
}
add_action( 'admin_init', 'minka_seed_shop', 21 );
