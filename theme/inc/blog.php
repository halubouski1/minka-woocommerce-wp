<?php
/**
 * Блог: список статей и сама статья.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Заголовок раздела: берётся у страницы, назначенной страницей записей.
 */
function minka_blog_title() {
	$page_id = (int) get_option( 'page_for_posts' );

	return $page_id ? get_the_title( $page_id ) : 'Блог';
}

/**
 * Картинка записи: миниатюра, а если её нет — изображение из вёрстки.
 */
function minka_post_thumbnail_url( $post, $size = 'large' ) {
	$url = get_the_post_thumbnail_url( $post, $size );

	return $url ? $url : minka_asset( 'assets/img/blog-img-1.webp' );
}

/**
 * Открыт ли сейчас блог (список статей или отдельная статья).
 */
function minka_is_blog_page() {
	return is_home() || is_singular( 'post' ) || is_archive();
}

/**
 * Вопросы и ответы в блоге: свои или те же, что на главной.
 */
function minka_blog_faq_items() {
	$inherit = true;

	if ( function_exists( 'get_field' ) ) {
		$inherit = (bool) get_field( 'blog_faq_inherit', 'option' );
	}

	if ( $inherit ) {
		return minka_faq_items();
	}

	$items = minka_faq_from_rows( minka_option( 'blog_faq' ) );

	return $items ? $items : minka_faq_items();
}

/**
 * Вопросы и ответы под статьёй: свои или общий список блога.
 *
 * Список задаётся в самой записи, поэтому у каждой статьи он может быть свой.
 * Пока переключатель включён (по умолчанию) или свои вопросы не заполнены,
 * показываем то же, что и в списке блога.
 */
function minka_post_faq_items( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	if ( ! function_exists( 'get_field' ) || ! $post_id ) {
		return minka_blog_faq_items();
	}

	if ( (bool) get_field( 'post_faq_inherit', $post_id ) ) {
		return minka_blog_faq_items();
	}

	$items = minka_faq_from_rows( get_field( 'post_faq', $post_id ) );

	return $items ? $items : minka_blog_faq_items();
}

/**
 * Сколько карточек показывает вёрстка до нажатия «Показать ещё»:
 * девять на десктопе и пять на узких экранах (≤570px).
 */
define( 'MINKA_BLOG_CAP', 9 );
define( 'MINKA_BLOG_MOBILE_CAP', 5 );

/**
 * Запрос за очередной порцией карточек (кнопка «Показать ещё»).
 */
function minka_blog_is_ajax() {
	return ! empty( $_GET['minka_ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Номер страницы из адреса: основному запросу мы его сбрасываем.
 */
function minka_blog_logical_page( $set = null ) {
	static $page = 1;

	if ( null !== $set ) {
		$page = max( 1, (int) $set );
	}

	return $page;
}

/**
 * Статей за один запрос — ровно столько, сколько помещается до кнопки.
 *
 * Адрес /blog/page/2/ показывает всё до второй страницы включительно: иначе
 * после «Показать ещё» и обновления страницы начало списка пропадает.
 * Догрузка приходит с ?minka_ajax=1 — ей отдаём ровно одну порцию.
 */
function minka_blog_posts_per_page( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
		return;
	}

	$paged = max( 1, (int) $query->get( 'paged' ) );
	minka_blog_logical_page( $paged );

	if ( minka_blog_is_ajax() || $paged < 2 ) {
		$query->set( 'posts_per_page', MINKA_BLOG_CAP );

		return;
	}

	$query->set( 'posts_per_page', MINKA_BLOG_CAP * $paged );
	$query->set( 'paged', 1 );
	$query->set( 'offset', 0 );
}
add_action( 'pre_get_posts', 'minka_blog_posts_per_page' );

/**
 * Ссылка на следующую порцию — или пусто, если статьи кончились.
 */
function minka_blog_next_link() {
	global $wp_query;

	$page   = minka_blog_logical_page();
	$loaded = minka_blog_is_ajax() ? MINKA_BLOG_CAP * $page : (int) $wp_query->post_count;

	if ( (int) $wp_query->found_posts <= $loaded ) {
		return '';
	}

	// Без экранирования: иначе «&» станет «&#038;», и параметры после него
	// превратятся в якорь. Экранирует уже шаблон при выводе.
	return remove_query_arg( 'minka_ajax', get_pagenum_link( $page + 1, false ) );
}

/**
 * Отдаёт следующую порцию карточек — её подставляет blog.js вместо перехода
 * на вторую страницу. Запрос идёт по обычному адресу с ?minka_ajax=1,
 * поэтому выборку делает тот же основной запрос WordPress.
 */
function minka_blog_ajax_response() {
	if ( empty( $_GET['minka_ajax'] ) || ! is_home() ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}

	global $wp_query;

	ob_start();
	get_template_part( 'template-parts/blog-cards' );
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'  => $html,
			'next'  => minka_blog_next_link(),
			'found' => (int) $wp_query->post_count,
		)
	);
}
add_action( 'template_redirect', 'minka_blog_ajax_response' );
