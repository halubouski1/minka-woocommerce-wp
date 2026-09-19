<?php
/**
 * MINKA theme bootstrap.
 */

defined( 'ABSPATH' ) || exit;

define( 'MINKA_VERSION', '1.0.0' );
define( 'MINKA_URI', get_template_directory_uri() );

require_once get_template_directory() . '/inc/slugs.php';
require_once get_template_directory() . '/inc/media.php';
require_once get_template_directory() . '/inc/svg-upload.php';
require_once get_template_directory() . '/inc/admin-columns.php';
require_once get_template_directory() . '/inc/yoast-acf.php';

/**
 * URI of a file inside the theme (images, icons, fonts).
 */
function minka_asset( $path ) {
	return MINKA_URI . '/' . ltrim( $path, '/' );
}

/**
 * Версия файла стилей или скрипта — время его изменения.
 *
 * С постоянной версией браузер держит в кэше старый CSS или JS и правки
 * не видны, пока не почистишь кэш вручную.
 */
function minka_asset_version( $path ) {
	$file = get_template_directory() . '/' . ltrim( $path, '/' );

	return file_exists( $file ) ? (string) filemtime( $file ) : MINKA_VERSION;
}

/**
 * Permalinks for the static pages the layout links to.
 *
 * Пока соответствующие страницы не созданы в админке, ссылки ведут на /<slug>/
 * и начнут работать сразу после их создания с такими же слагами.
 */
function minka_page_url( $key ) {
	$slugs = array(
		'home'      => '',
		'catalog'   => 'catalog',
		'about'     => 'about-us',
		'care'      => 'care',
		'contacts'  => 'contacts',
		'blog'      => 'blog',
		'favorites' => 'favorites',
		'privacy'   => 'privacy-policy',
		'terms'     => 'terms-and-conditions',
	);

	// Каталог — это витрина WooCommerce, как только магазин будет подключён.
	if ( 'catalog' === $key && function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop ) {
			return $shop;
		}
	}

	// Блог — страница записей, если она назначена в «Настройки → Чтение».
	if ( 'blog' === $key ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		if ( $posts_page ) {
			return get_permalink( $posts_page );
		}
	}

	$slug = isset( $slugs[ $key ] ) ? $slugs[ $key ] : $key;

	return home_url( '/' . ( $slug ? $slug . '/' : '' ) );
}

/**
 * Контактный телефон в одном месте: [0] — для tel:, [1] — для показа.
 */
function minka_phone() {
	return array( '+375257028538', '+375 (25) 702-85-38' );
}

/**
 * Theme setup.
 */
function minka_setup() {
	load_theme_textdomain( 'minka', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// Магазин.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'minka_setup' );

/**
 * Какие постраничные наборы стилей нужны текущей странице.
 * Имя набора совпадает с парой файлов css/<набор>.css и css/media-<набор>.css.
 */
function minka_page_stylesheets() {
	$sheets = array();

	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		$sheets[] = 'catalog';
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		$sheets[] = 'single';
	}

	// Избранное показывает то список, то пустое состояние — стили нужны оба.
	if ( minka_is_favorites_page() ) {
		$sheets[] = 'favorites';
		$sheets[] = 'favorites-empty';
	}

	if ( minka_is_care_page() ) {
		$sheets[] = 'care';
	}

	if ( minka_is_about_page() ) {
		$sheets[] = 'about-us';
	}

	if ( minka_is_contacts_page() ) {
		$sheets[] = 'contacts';
	}

	if ( is_home() || is_archive() ) {
		$sheets[] = 'blog';
	}

	if ( is_singular( 'post' ) ) {
		$sheets[] = 'post';
	}

	if ( is_404() ) {
		$sheets[] = '404';
	}

	if ( is_page_template( 'template-legal.php' ) ) {
		$sheets[] = 'policy';
	}

	return apply_filters( 'minka_page_stylesheets', $sheets );
}

/**
 * Styles and scripts. Порядок подключения повторяет вёрстку.
 */
function minka_assets() {
	wp_enqueue_style( 'aos', minka_asset( 'css/aos.css' ), array(), minka_asset_version( 'css/aos.css' ) );
	wp_enqueue_style( 'swiper', minka_asset( 'css/swiper-bundle.min.css' ), array(), minka_asset_version( 'css/swiper-bundle.min.css' ) );
	wp_enqueue_style( 'normalize', minka_asset( 'css/normalize.css' ), array(), minka_asset_version( 'css/normalize.css' ) );
	wp_enqueue_style( 'minka-style', minka_asset( 'css/style.css' ), array( 'normalize' ), minka_asset_version( 'css/style.css' ) );
	wp_enqueue_style( 'minka-media', minka_asset( 'css/media.css' ), array( 'minka-style' ), minka_asset_version( 'css/media.css' ) );

	// Постраничные стили из вёрстки: базовый файл плюс его медиазапросы.
	foreach ( minka_page_stylesheets() as $minka_sheet ) {
		wp_enqueue_style( 'minka-' . $minka_sheet, minka_asset( 'css/' . $minka_sheet . '.css' ), array( 'minka-media' ), minka_asset_version( 'css/' . $minka_sheet . '.css' ) );

		// У некоторых страниц (например, 404) отдельного файла медиазапросов нет.
		if ( file_exists( get_template_directory() . '/css/media-' . $minka_sheet . '.css' ) ) {
			wp_enqueue_style( 'minka-media-' . $minka_sheet, minka_asset( 'css/media-' . $minka_sheet . '.css' ), array( 'minka-' . $minka_sheet ), minka_asset_version( 'css/media-' . $minka_sheet . '.css' ) );
		}
	}

	wp_enqueue_style( 'minka-theme', get_stylesheet_uri(), array( 'minka-media' ), minka_asset_version( 'style.css' ) );

	wp_enqueue_script( 'aos', minka_asset( 'js/aos.js' ), array(), minka_asset_version( 'js/aos.js' ), true );
	wp_enqueue_script( 'lenis', minka_asset( 'js/lenis.min.js' ), array(), minka_asset_version( 'js/lenis.min.js' ), true );
	wp_enqueue_script( 'swiper', minka_asset( 'js/swiper-bundle.min.js' ), array(), minka_asset_version( 'js/swiper-bundle.min.js' ), true );
	wp_enqueue_script( 'just-validate', minka_asset( 'js/just-validate.production.min.js' ), array(), minka_asset_version( 'js/just-validate.production.min.js' ), true );
	wp_enqueue_script(
		'minka-main',
		minka_asset( 'js/main.js' ),
		array( 'aos', 'lenis', 'swiper', 'just-validate' ),
		minka_asset_version( 'js/main.js' ),
		true
	);

	// Сердечки работают на любой странице с карточками, поэтому скрипт общий.
	wp_enqueue_script( 'minka-favorites', minka_asset( 'js/favorites.js' ), array( 'minka-main' ), minka_asset_version( 'js/favorites.js' ), true );
	wp_localize_script(
		'minka-favorites',
		'minkaFavorites',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'page'    => minka_favorites_url(),
		)
	);

	// Плашка про cookie: вид из вёрстки, согласие ведёт Complianz.
	wp_enqueue_script( 'minka-consent', minka_asset( 'js/consent.js' ), array( 'minka-main' ), minka_asset_version( 'js/consent.js' ), true );

	// Кнопка «Показать ещё» в блоге догружает статьи без перезагрузки.
	if ( is_home() ) {
		wp_enqueue_script( 'minka-blog', minka_asset( 'js/blog.js' ), array( 'minka-main' ), minka_asset_version( 'js/blog.js' ), true );
	}

	// Фильтры каталога живут отдельно: скрипт нужен только на страницах товаров.
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		wp_enqueue_script( 'minka-catalog-filters', minka_asset( 'js/catalog-filters.js' ), array( 'minka-main' ), minka_asset_version( 'js/catalog-filters.js' ), true );
		wp_localize_script(
			'minka-catalog-filters',
			'minkaCatalog',
			array(
				'base'   => minka_catalog_base_url(),
				'bounds' => minka_catalog_price_bounds(),
			)
		);
	}

	// Пути для разметки, которую main.js собирает на лету (поиск, карточки).
	wp_localize_script(
		'minka-main',
		'minkaTheme',
		array(
			'assets'  => untrailingslashit( minka_asset( 'assets' ) ),
			'home'    => home_url( '/' ),
			'catalog' => minka_page_url( 'catalog' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'minka_assets' );

/**
 * --fixed-vh нужен до первой отрисовки, поэтому уходит в <head> инлайном.
 */
function minka_fixed_vh() {
	echo '<script>document.documentElement.style.setProperty("--fixed-vh", window.innerHeight + "px");</script>' . "\n";
}
add_action( 'wp_head', 'minka_fixed_vh', 2 );

/**
 * Фавиконки и манифест из вёрстки.
 */
function minka_favicons() {
	$icons = minka_asset( 'assets/icons' );
	?>
	<link rel="icon" href="<?php echo esc_url( $icons . '/favicon.ico' ); ?>" sizes="any">
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $icons . '/favicon-32x32.png' ); ?>">
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( $icons . '/favicon-16x16.png' ); ?>">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $icons . '/apple-touch-icon.png' ); ?>">
	<link rel="manifest" href="<?php echo esc_url( $icons . '/site.webmanifest' ); ?>">
	<meta name="theme-color" content="#ffffff">
	<?php
}
add_action( 'wp_head', 'minka_favicons', 3 );

/**
 * Есть ли на сайте SEO-плагин, который сам печатает description / Open Graph.
 */
function minka_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' )        // Yoast SEO.
		|| class_exists( 'RankMath' )        // Rank Math.
		|| defined( 'SEOPRESS_VERSION' )     // SEOPress.
		|| defined( 'AIOSEO_VERSION' );      // All in One SEO.
}

/**
 * Description / canonical / Open Graph для главной — ровно как в вёрстке.
 * Отключается само, как только появится SEO-плагин.
 */
function minka_front_page_meta() {
	if ( ! is_front_page() || minka_has_seo_plugin() ) {
		return;
	}

	$description = 'Норковые шубы MINKA из натурального меха: прямые поставки с пушных аукционов, более 200 моделей. Примерка в шоуруме, гарантия и доставка по Беларуси.';
	$og_title    = 'Норковые шубы MINKA — натуральный мех, примерка в шоуруме';
	$og_desc     = '200+ моделей норковых шуб. Прямые поставки, примерка в шоуруме, гарантия и доставка по Беларуси.';
	$og_image    = minka_asset( 'assets/img/og.jpg' );
	?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>">
	<link rel="canonical" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo esc_url( home_url( '/' ) ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $og_title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $og_desc ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $og_image ); ?>">
	<meta property="og:image:width" content="1200">
	<meta property="og:image:height" content="630">
	<meta property="og:image:alt" content="MINKA — норковые шубы">
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:locale" content="ru_RU">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="Норковые шубы MINKA — натуральный мех">
	<meta name="twitter:description" content="<?php echo esc_attr( $og_desc ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( $og_image ); ?>">
	<?php
}
add_action( 'wp_head', 'minka_front_page_meta', 4 );

/**
 * <title> главной — как в вёрстке. Тоже уступает SEO-плагину, когда он появится.
 */
function minka_front_page_title( $parts ) {
	if ( is_front_page() && ! minka_has_seo_plugin() ) {
		$parts['title']  = 'Норковые шубы MINKA — купить натуральную шубу в Минске';
		unset( $parts['tagline'], $parts['site'] );
	}

	return $parts;
}
add_filter( 'document_title_parts', 'minka_front_page_title' );

require_once get_template_directory() . '/inc/data.php';
require_once get_template_directory() . '/inc/acf-fields.php';
require_once get_template_directory() . '/inc/catalog.php';
require_once get_template_directory() . '/inc/product.php';
require_once get_template_directory() . '/inc/favorites.php';
require_once get_template_directory() . '/inc/search.php';
require_once get_template_directory() . '/inc/care.php';
require_once get_template_directory() . '/inc/about.php';
require_once get_template_directory() . '/inc/contacts.php';
require_once get_template_directory() . '/inc/blog.php';
require_once get_template_directory() . '/inc/forms.php';
require_once get_template_directory() . '/inc/emails.php';
require_once get_template_directory() . '/inc/shop-pages.php';
require_once get_template_directory() . '/inc/schema.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/analytics.php';
require_once get_template_directory() . '/inc/consent.php';
require_once get_template_directory() . '/inc/seed.php';
require_once get_template_directory() . '/inc/seed-legal.php';
require_once get_template_directory() . '/inc/seed-catalog.php';

