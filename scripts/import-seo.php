<?php
/**
 * Переносит SEO-теги из исходных HTML-макетов в Yoast.
 *
 * Макеты в корне репозитория — первоисточник: там уже написаны заголовки и
 * описания под каждую страницу. Переписывать их руками в админке смысла нет.
 *
 * Осознанно НЕ переносятся:
 *   canonical — в макетах он указывает на minka.by, то есть на другой домен.
 *               Скопировать его значит сказать поисковику, что канонический
 *               адрес страницы живёт не здесь, и выбить сайт из выдачи.
 *   og:image  — ссылается на minka-gilt.vercel.app, чужой превью-хост.
 *               Картинки надо залить в медиатеку и назначить вручную.
 *
 * Запускать через scripts/import-seo.sh
 */

require '/var/www/html/wp-load.php';

if ( ! defined( 'WPSEO_VERSION' ) ) {
	fwrite( STDERR, "Yoast SEO не активен.\n" );
	exit( 1 );
}

global $wpdb;

$dir = '/tmp/minka-html';
$dry = in_array( '--dry-run', $argv, true );

/**
 * Какой макет какой странице соответствует.
 *
 * Главная, каталог и блог ищутся по настройкам WordPress, а не по слагу:
 * они назначаются в настройках и слаг у них может быть любым.
 */
$map = array(
	'index.html'                => array( 'option' => 'page_on_front' ),
	'catalog.html'              => array( 'option' => 'woocommerce_shop_page_id' ),
	'blog.html'                 => array( 'option' => 'page_for_posts' ),
	'contacts.html'             => array( 'slug' => 'contacts' ),
	'about-us.html'             => array( 'slug' => 'about-us' ),
	'care.html'                 => array( 'slug' => 'care' ),
	'privacy-policy.html'       => array( 'slug' => 'privacy-policy' ),
	'terms-and-conditions.html' => array( 'slug' => 'terms-and-conditions' ),
	'favorites.html'            => array( 'slug' => 'favorites' ),
);

/**
 * Кладёт картинку из темы в медиатеку и возвращает её ID.
 *
 * Повторный запуск ничего не дублирует: у загруженного файла остаётся метка
 * с исходным именем, по ней вложение и находится.
 *
 * @param string $filename Имя файла в theme/assets/img.
 * @return int ID вложения либо 0.
 */
function minka_seo_upload_image( $filename ) {
	global $wpdb;

	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_minka_og_source' AND meta_value = %s LIMIT 1",
			$filename
		)
	);

	if ( $existing && get_post( $existing ) ) {
		return (int) $existing;
	}

	$source = get_template_directory() . '/assets/img/' . $filename;

	if ( ! file_exists( $source ) ) {
		return 0;
	}

	$upload = wp_upload_bits( $filename, null, file_get_contents( $source ) );

	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$type = wp_check_filetype( $filename, null );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $type['type'],
			'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

	update_post_meta( $attachment_id, '_minka_og_source', $filename );

	return (int) $attachment_id;
}

/**
 * Приводит значение из макета к чистому тексту.
 */
function minka_seo_clean( $value ) {
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	// Неразрывный пробел из &nbsp; в мета-теге не нужен.
	$value = str_replace( "\xC2\xA0", ' ', $value );

	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

/**
 * Достаёт один тег из разметки.
 */
function minka_seo_grab( $html, $pattern ) {
	return preg_match( $pattern, $html, $m ) ? minka_seo_clean( $m[1] ) : '';
}

$total = 0;

foreach ( $map as $file => $target ) {
	$path = $dir . '/' . $file;

	if ( ! file_exists( $path ) ) {
		echo "$file: макет не найден\n";
		continue;
	}

	// Достаточно head: дальше идёт разметка страницы.
	$html = file_get_contents( $path, false, null, 0, 12000 );

	$data = array(
		'_yoast_wpseo_title'                   => minka_seo_grab( $html, '~<title[^>]*>(.*?)</title>~is' ),
		'_yoast_wpseo_metadesc'                => minka_seo_grab( $html, '~<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']~is' ),
		'_yoast_wpseo_opengraph-title'         => minka_seo_grab( $html, '~<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']~is' ),
		'_yoast_wpseo_opengraph-description'   => minka_seo_grab( $html, '~<meta\s+property=["\']og:description["\']\s+content=["\'](.*?)["\']~is' ),
	);

	$data = array_filter( $data, static fn( $v ) => '' !== $v );

	// og:image в макете ведёт на превью-хост, но сам файл лежит в теме —
	// берём локальный и кладём в медиатеку.
	$og_src   = minka_seo_grab( $html, '~<meta\s+property=["\']og:image["\']\s+content=["\'](.*?)["\']~is' );
	$og_file  = $og_src ? basename( wp_parse_url( $og_src, PHP_URL_PATH ) ) : '';
	$image_id = 0;

	$page_id = isset( $target['option'] )
		? (int) get_option( $target['option'] )
		: (int) ( ( $p = get_page_by_path( $target['slug'] ) ) ? $p->ID : 0 );

	if ( ! $page_id ) {
		echo "$file: страница не найдена\n";
		continue;
	}

	echo "$file → «", get_the_title( $page_id ), "» (id $page_id)\n";

	foreach ( $data as $key => $value ) {
		$short = mb_strlen( $value ) > 70 ? mb_substr( $value, 0, 70 ) . '…' : $value;
		echo '  ', str_pad( str_replace( '_yoast_wpseo_', '', $key ), 24 ), $short, "\n";
	}

	if ( $og_file ) {
		echo '  ', str_pad( 'og-image', 24 ), $og_file, "\n";
	}

	if ( $dry ) {
		continue;
	}

	if ( $og_file ) {
		$image_id = minka_seo_upload_image( $og_file );

		if ( $image_id ) {
			$url = wp_get_attachment_url( $image_id );

			// Twitter заполняем тем же: отдельной картинки в макетах нет.
			$data['_yoast_wpseo_opengraph-image']    = $url;
			$data['_yoast_wpseo_opengraph-image-id'] = (string) $image_id;
			$data['_yoast_wpseo_twitter-image']      = $url;
			$data['_yoast_wpseo_twitter-image-id']   = (string) $image_id;
		}
	}

	// Пишем через WPSEO_Meta, а не update_post_meta: Yoast проверяет значения
	// своими правилами и поля с ID картинки, записанные напрямую, молча
	// отбрасывает. Без ID он не выводит og:image:width/height, и соцсети
	// показывают ссылку без превью, пока сами не скачают файл.
	foreach ( $data as $key => $value ) {
		WPSEO_Meta::set_value( str_replace( '_yoast_wpseo_', '', $key ), $value, $page_id );
	}

	// Yoast держит метатеги в собственной таблице indexables. Сохранение
	// записи её не всегда обновляет, поэтому строку проще удалить: при
	// следующем обращении к странице Yoast соберёт её заново из postmeta —
	// уже с размерами и типом картинки.
	$wpdb->delete(
		$wpdb->prefix . 'yoast_indexable',
		array(
			'object_id'   => $page_id,
			'object_type' => 'post',
		)
	);

	wp_update_post( array( 'ID' => $page_id ) );

	++$total;
}

// Запасная картинка для страниц без своей: её Yoast подставит в соцсети,
// иначе ссылка уйдёт вообще без превью.
if ( ! $dry ) {
	$fallback = minka_seo_upload_image( 'og.jpg' );

	if ( $fallback ) {
		$social = get_option( 'wpseo_social', array() );

		$social['og_default_image']    = wp_get_attachment_url( $fallback );
		$social['og_default_image_id'] = $fallback;

		update_option( 'wpseo_social', $social );

		echo "\nЗапасная картинка соцсетей: og.jpg (id $fallback)\n";
	}
}

echo $dry ? "\nПробный запуск, ничего не сохранено.\n" : "\nОбновлено страниц: $total\n";
