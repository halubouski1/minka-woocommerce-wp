<?php
/**
 * Первичное заполнение полей ACF содержимым из вёрстки.
 *
 * Выполняется один раз: дальше всё правится в админке и код сюда не лезет.
 * Чтобы прогнать заново (например, после сброса базы), удалите опцию
 * minka_seeded_version.
 */

defined( 'ABSPATH' ) || exit;

define( 'MINKA_SEED_VERSION', 7 );

/**
 * Копирует картинку из темы в медиатеку и возвращает ID вложения.
 * Повторный вызов отдаёт уже загруженный файл.
 */
function minka_import_theme_image( $relative_path ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_minka_source',   // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $relative_path,    // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);

	if ( $existing ) {
		return (int) $existing[0];
	}

	$file = get_template_directory() . '/' . ltrim( $relative_path, '/' );

	if ( ! file_exists( $file ) ) {
		return 0;
	}

	$upload = wp_upload_bits( basename( $file ), null, file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype   = wp_check_filetype( $upload['file'] );
	$attachment = array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_file_name( pathinfo( $file, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $upload['file'] );

	if ( ! $attach_id || is_wp_error( $attach_id ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
	update_post_meta( $attach_id, '_minka_source', $relative_path );

	return (int) $attach_id;
}

/**
 * Первичное заполнение полей страницы «Уход за шубой».
 */
function minka_seed_care_page( $faq_rows ) {
	$intro    = minka_care_intro();
	$table    = minka_care_table();
	$cleaning = minka_care_cleaning();
	$cta      = minka_care_cta();

	update_field( 'field_minka_care_title', $intro['title'], 'option' );
	update_field( 'field_minka_care_desc', $intro['desc'], 'option' );
	update_field( 'field_minka_care_note', $intro['note'], 'option' );
	update_field( 'field_minka_care_image', minka_import_theme_image( 'assets/img/care.webp' ), 'option' );

	update_field( 'field_minka_care_head_left', $table['head_left'], 'option' );
	update_field( 'field_minka_care_head_right', $table['head_right'], 'option' );

	$rows = array();
	foreach ( $table['rows'] as $row ) {
		$rows[] = array(
			'care_can'    => $row['can'],
			'care_cannot' => $row['cannot'],
		);
	}
	update_field( 'field_minka_care_rows', $rows, 'option' );

	update_field( 'field_minka_care_cleaning_title', $cleaning['title'], 'option' );

	$steps = array();
	foreach ( $cleaning['steps'] as $step ) {
		$steps[] = array( 'care_step' => $step );
	}
	update_field( 'field_minka_care_steps', $steps, 'option' );

	update_field( 'field_minka_care_cta_title', $cta['title'], 'option' );
	update_field( 'field_minka_care_cta_text', $cta['text'], 'option' );

	update_field( 'field_minka_care_faq_inherit', 1, 'option' );
	update_field( 'field_minka_care_faq', $faq_rows, 'option' );
}

/**
 * Первичное заполнение полей страницы «О нас».
 */
function minka_seed_about_page( $faq_rows ) {
	$intro     = minka_about_intro();
	$selection = minka_about_selection();

	update_field( 'field_minka_about_title', $intro['title'], 'option' );

	$paragraphs = array();
	foreach ( $intro['paragraphs'] as $paragraph ) {
		$paragraphs[] = array( 'about_paragraph' => $paragraph );
	}
	update_field( 'field_minka_about_paragraphs', $paragraphs, 'option' );

	update_field( 'field_minka_about_subtitle', $selection['subtitle'], 'option' );
	update_field( 'field_minka_about_desc', $selection['desc'], 'option' );
	update_field( 'field_minka_about_image', minka_import_theme_image( 'assets/img/about-us-left.webp' ), 'option' );
	update_field( 'field_minka_about_cover', minka_import_theme_image( 'assets/img/about-us-right.webp' ), 'option' );

	$stats = array();
	foreach ( minka_about_default_stats() as $stat ) {
		$stats[] = array(
			'stat_number' => $stat['number'],
			'stat_suffix' => $stat['suffix'],
			'stat_label'  => $stat['label'],
		);
	}
	update_field( 'field_minka_about_stats', $stats, 'option' );

	update_field( 'field_minka_about_faq_inherit', 1, 'option' );
	update_field( 'field_minka_about_faq', $faq_rows, 'option' );
}

/**
 * Первичное заполнение полей страницы «Контакты».
 */
function minka_seed_contacts_page( $faq_rows ) {
	$intro    = minka_contacts_intro();
	$details  = minka_contacts_details();
	$showroom = minka_contacts_showroom();
	$form     = minka_contacts_form();

	update_field( 'field_minka_contacts_title', $intro['title'], 'option' );
	update_field( 'field_minka_contacts_desc', $intro['desc'], 'option' );
	update_field( 'field_minka_contacts_image', minka_import_theme_image( 'assets/img/contacts.webp' ), 'option' );
	update_field( 'field_minka_contacts_image_second', minka_import_theme_image( 'assets/img/contacts-2.webp' ), 'option' );

	update_field( 'field_minka_contacts_phone_label', $details['phone_label'], 'option' );
	update_field( 'field_minka_contacts_phone_number', '+375257028538', 'option' );
	update_field( 'field_minka_contacts_email', $details['email'], 'option' );

	$socials = array();
	foreach ( minka_contacts_default_socials() as $social ) {
		$socials[] = array(
			'social_label' => $social['label'],
			'social_url'   => $social['url'],
			'social_icon'  => minka_import_theme_image( $social['icon_path'] ),
		);
	}
	update_field( 'field_minka_contacts_socials', $socials, 'option' );

	update_field( 'field_minka_contacts_showroom_title', $showroom['title'], 'option' );
	update_field( 'field_minka_contacts_showroom_desc', $showroom['desc'], 'option' );
	update_field( 'field_minka_contacts_showroom_button', $showroom['button'], 'option' );

	update_field( 'field_minka_contacts_form_title', $form['title'], 'option' );
	update_field( 'field_minka_contacts_form_desc', $form['desc'], 'option' );
	update_field( 'field_minka_contacts_form_image', minka_import_theme_image( 'assets/img/contact-form.webp' ), 'option' );

	update_field( 'field_minka_contacts_faq_inherit', 1, 'option' );
	update_field( 'field_minka_contacts_faq', $faq_rows, 'option' );
}

/**
 * Заполняет поля дефолтами из inc/data.php, если этого ещё не делали.
 */
function minka_seed_acf_defaults() {
	if ( ! function_exists( 'update_field' ) ) {
		return;
	}

	if ( (int) get_option( 'minka_seeded_version' ) >= MINKA_SEED_VERSION ) {
		return;
	}

	// Ставим флаг сразу: даже если что-то пойдёт не так, сид не зациклится.
	update_option( 'minka_seeded_version', MINKA_SEED_VERSION );

	$phone = minka_phone();
	update_field( 'field_minka_phone_show', 1, 'option' );
	update_field( 'field_minka_phone_number', $phone[0], 'option' );
	update_field( 'field_minka_phone_label', $phone[1], 'option' );

	$default_card = minka_menu_default_card();
	update_field( 'field_minka_menu_default_text', $default_card['text'], 'option' );
	update_field( 'field_minka_menu_default_image', minka_import_theme_image( 'assets/img/menu-card-0.webp' ), 'option' );

	$menu_rows = array();
	foreach ( minka_default_menu_items() as $item ) {
		$menu_rows[] = array(
			'item_link'  => array(
				'title'  => $item['title'],
				'url'    => $item['url'],
				'target' => '',
			),
			'item_text'  => $item['text'],
			'item_image' => minka_import_theme_image( $item['image_path'] ),
		);
	}
	update_field( 'field_minka_menu_items', $menu_rows, 'option' );

	$nav_rows = array();
	foreach ( minka_default_footer_nav() as $link ) {
		$nav_rows[] = array( 'nav_link' => array( 'title' => $link['title'], 'url' => $link['url'], 'target' => '' ) );
	}
	update_field( 'field_minka_footer_nav', $nav_rows, 'option' );

	$policy_rows = array();
	foreach ( minka_default_footer_policy() as $link ) {
		$policy_rows[] = array( 'policy_link' => array( 'title' => $link['title'], 'url' => $link['url'], 'target' => '' ) );
	}
	update_field( 'field_minka_footer_policy', $policy_rows, 'option' );

	$social_rows = array();
	foreach ( minka_default_socials() as $social ) {
		$social_rows[] = array(
			'social_label' => $social['label'],
			'social_url'   => $social['url'],
			'social_icon'  => minka_import_theme_image( $social['icon_path'] ),
		);
	}
	update_field( 'field_minka_socials', $social_rows, 'option' );

	$faq_rows = array();
	foreach ( minka_default_faq() as $item ) {
		$faq_rows[] = array(
			'faq_question' => $item['question'],
			'faq_answer'   => $item['answer'],
		);
	}
	update_field( 'field_minka_faq', $faq_rows, 'option' );

	// Каталог по умолчанию показывает те же вопросы, но свой список уже заполнен —
	// достаточно выключить переключатель, чтобы его править.
	update_field( 'field_minka_catalog_faq_inherit', 1, 'option' );
	update_field( 'field_minka_catalog_faq', $faq_rows, 'option' );
	update_field( 'field_minka_product_faq_inherit', 1, 'option' );
	update_field( 'field_minka_product_faq', $faq_rows, 'option' );

	minka_seed_care_page( $faq_rows );
	minka_seed_about_page( $faq_rows );
	minka_seed_contacts_page( $faq_rows );

	$notfound = minka_notfound_content();
	update_field( 'field_minka_404_image', minka_import_theme_image( 'assets/img/404.webp' ), 'option' );
	update_field( 'field_minka_404_label', $notfound['label'], 'option' );
	update_field( 'field_minka_404_title', $notfound['title'], 'option' );
	update_field( 'field_minka_404_text', $notfound['text'], 'option' );
	update_field( 'field_minka_404_catalog_button', $notfound['catalog_button'], 'option' );
	update_field( 'field_minka_404_home_button', $notfound['home_button'], 'option' );

	$review_rows = array();
	foreach ( minka_default_reviews() as $review ) {
		if ( 'image' === $review['type'] ) {
			$review_rows[] = array(
				'acf_fc_layout' => 'image',
				'review_image'  => minka_import_theme_image( $review['image_path'] ),
				'review_name'   => $review['name'],
				'review_text'   => $review['text'],
			);
		} elseif ( 'double' === $review['type'] ) {
			$blocks = array();
			foreach ( $review['blocks'] as $block ) {
				$blocks[] = array(
					'review_name' => $block['name'],
					'review_text' => $block['text'],
				);
			}

			$review_rows[] = array(
				'acf_fc_layout' => 'double',
				'review_blocks' => $blocks,
			);
		} elseif ( 'cta' === $review['type'] ) {
			$review_rows[] = array(
				'acf_fc_layout' => 'cta',
				'review_name'   => $review['name'],
				'review_text'   => $review['text'],
				'review_link'   => array(
					'title'  => $review['link']['title'],
					'url'    => $review['link']['url'],
					'target' => '',
				),
			);
		}
	}
	update_field( 'field_minka_reviews', $review_rows, 'option' );
}
add_action( 'admin_init', 'minka_seed_acf_defaults', 20 );
