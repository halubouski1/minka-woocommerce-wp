<?php
/**
 * Страницы настроек и поля ACF.
 *
 * Поля регистрируются кодом, а не в админке: так они лежат в репозитории
 * и одинаковы на всех окружениях. Заполняются они по-прежнему в админке.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Страницы настроек: «MINKA» с разделами «Шапка и меню», «Подвал», «Главная».
 */
function minka_acf_options_pages() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => 'Настройки MINKA',
			'menu_title' => 'MINKA',
			'menu_slug'  => 'minka-settings',
			'capability' => 'edit_theme_options',
			'redirect'   => true,
			'icon_url'   => 'dashicons-store',
			'position'   => 3,
		)
	);

	foreach ( array(
		'minka-header'  => 'Шапка и меню',
		'minka-footer'  => 'Подвал',
		'minka-home'    => 'Главная страница',
		'minka-catalog' => 'Каталог',
		'minka-product' => 'Карточка товара',
		'minka-care'    => 'Уход за шубой',
		'minka-about'   => 'О нас',
		'minka-contacts' => 'Контакты',
		'minka-blog'     => 'Блог',
		'minka-404'      => 'Страница 404',
		'minka-analytics' => 'Аналитика',
	) as $slug => $title ) {
		acf_add_options_sub_page(
			array(
				'page_title'  => $title,
				'menu_title'  => $title,
				'menu_slug'   => $slug,
				'parent_slug' => 'minka-settings',
				'capability'  => 'edit_theme_options',
			)
		);
	}
}
add_action( 'acf/init', 'minka_acf_options_pages' );

/**
 * Поля.
 */
function minka_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	/* --------------------------------------------------------------- Шапка */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_header',
			'title'    => 'Шапка и меню',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-header' ) ) ),
			'fields'   => array(
				array(
					'key'           => 'field_minka_phone_show',
					'label'         => 'Показывать телефон в шапке',
					'name'          => 'header_phone_show',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
				),
				array(
					'key'               => 'field_minka_phone_number',
					'label'             => 'Номер для ссылки',
					'name'              => 'header_phone_number',
					'type'              => 'text',
					'instructions'      => 'В международном формате, без пробелов и скобок: +375291234567',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_phone_show', 'operator' => '==', 'value' => '1' ) ) ),
				),
				array(
					'key'               => 'field_minka_phone_label',
					'label'             => 'Как показывать номер',
					'name'              => 'header_phone_label',
					'type'              => 'text',
					'instructions'      => 'Текст на сайте: +375 (29) 123-45-67',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_phone_show', 'operator' => '==', 'value' => '1' ) ) ),
				),
				array(
					'key'   => 'field_minka_menu_tab',
					'label' => 'Меню',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_minka_menu_default_text',
					'label'        => 'Текст карточки по умолчанию',
					'name'         => 'menu_default_text',
					'type'         => 'textarea',
					'rows'         => 4,
					'instructions' => 'Показывается в меню, пока не наведён ни один пункт.',
					'new_lines'    => '',
				),
				array(
					'key'           => 'field_minka_menu_default_image',
					'label'         => 'Картинка карточки по умолчанию',
					'name'          => 'menu_default_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'          => 'field_minka_menu_items',
					'label'        => 'Пункты меню',
					'name'         => 'menu_items',
					'type'         => 'repeater',
					'instructions' => 'Порядок пунктов можно менять перетаскиванием. У каждого пункта своя карточка справа.',
					'layout'       => 'block',
					'button_label' => 'Добавить пункт',
					'sub_fields'   => array(
						array(
							'key'           => 'field_minka_menu_link',
							'label'         => 'Ссылка',
							'name'          => 'item_link',
							'type'          => 'link',
							'return_format' => 'array',
							'required'      => 1,
							'wrapper'       => array( 'width' => '40' ),
						),
						array(
							'key'           => 'field_minka_menu_image',
							'label'         => 'Картинка карточки',
							'name'          => 'item_image',
							'type'          => 'image',
							'return_format' => 'array',
							'preview_size'  => 'medium',
							'wrapper'       => array( 'width' => '60' ),
						),
						array(
							'key'       => 'field_minka_menu_text',
							'label'     => 'Текст карточки',
							'name'      => 'item_text',
							'type'      => 'textarea',
							'rows'      => 3,
							'new_lines' => '',
						),
					),
				),
				array(
					'key'   => 'field_minka_search_tab',
					'label' => 'Поиск',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_minka_search_interesting',
					'label'         => 'Блок «Может быть интересно»',
					'name'          => 'search_interesting',
					'type'          => 'relationship',
					'post_type'     => array( 'product' ),
					'filters'       => array( 'search' ),
					'elements'      => array( 'featured_image' ),
					'max'           => 4,
					'return_format' => 'id',
					'instructions'  => 'Четыре модели, которые показываются в панели поиска. Если ничего не выбрано, подставятся популярные.',
				),
			),
		)
	);

	/* -------------------------------------------------------------- Подвал */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_footer',
			'title'    => 'Подвал',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-footer' ) ) ),
			'fields'   => array(
				array(
					'key'          => 'field_minka_footer_nav',
					'label'        => 'Ссылки слева',
					'name'         => 'footer_nav',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Добавить ссылку',
					'sub_fields'   => array(
						array(
							'key'           => 'field_minka_footer_nav_link',
							'label'         => 'Ссылка',
							'name'          => 'nav_link',
							'type'          => 'link',
							'return_format' => 'array',
						),
					),
				),
				array(
					'key'          => 'field_minka_footer_policy',
					'label'        => 'Ссылки справа',
					'name'         => 'footer_policy',
					'type'         => 'repeater',
					'instructions' => 'Политика конфиденциальности, оферта и подобное.',
					'layout'       => 'table',
					'button_label' => 'Добавить ссылку',
					'sub_fields'   => array(
						array(
							'key'           => 'field_minka_footer_policy_link',
							'label'         => 'Ссылка',
							'name'          => 'policy_link',
							'type'          => 'link',
							'return_format' => 'array',
						),
					),
				),
				array(
					'key'          => 'field_minka_socials',
					'label'        => 'Социальные сети',
					'name'         => 'footer_socials',
					'type'         => 'repeater',
					'instructions' => 'Иконку можно загрузить в формате SVG или PNG.',
					'layout'       => 'table',
					'button_label' => 'Добавить соцсеть',
					'sub_fields'   => array(
						array(
							'key'          => 'field_minka_social_label',
							'label'        => 'Название',
							'name'         => 'social_label',
							'type'         => 'text',
							'instructions' => 'Видно только скринридерам и поисковикам.',
							'required'     => 1,
						),
						array(
							'key'      => 'field_minka_social_url',
							'label'    => 'Ссылка',
							'name'     => 'social_url',
							'type'     => 'url',
							'required' => 1,
						),
						array(
							'key'           => 'field_minka_social_icon',
							'label'         => 'Иконка',
							'name'          => 'social_icon',
							'type'          => 'image',
							'return_format' => 'array',
							'preview_size'  => 'thumbnail',
							'required'      => 1,
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------------------- Главная */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_home',
			'title'    => 'Главная страница',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-home' ) ) ),
			'fields'   => array(
				array(
					'key'   => 'field_minka_home_popular_tab',
					'label' => 'Популярные модели',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_minka_home_popular',
					'label'         => 'Блок «Популярные модели»',
					'name'          => 'home_popular',
					'type'          => 'relationship',
					'post_type'     => array( 'product' ),
					'filters'       => array( 'search' ),
					'elements'      => array( 'featured_image' ),
					'max'           => 4,
					'return_format' => 'id',
					'instructions'  => 'Четыре модели для слайдера на главной. Если ничего не выбрано, подставятся популярные по продажам.',
				),
				array(
					'key'   => 'field_minka_reviews_tab',
					'label' => 'Отзывы',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_minka_reviews',
					'label'        => 'Блок «О наших шубах говорят»',
					'name'         => 'reviews_items',
					'type'         => 'flexible_content',
					'instructions' => 'Каждый блок — отдельный слайд. Выберите тип карточки.',
					'button_label' => 'Добавить карточку',
					'layouts'      => array(
						'image'  => array(
							'key'        => 'layout_minka_review_image',
							'name'       => 'image',
							'label'      => 'Отзыв с фото',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'           => 'field_minka_review_image',
									'label'         => 'Фото',
									'name'          => 'review_image',
									'type'          => 'image',
									'return_format' => 'array',
									'preview_size'  => 'medium',
									'wrapper'       => array( 'width' => '30' ),
								),
								array(
									'key'     => 'field_minka_review_name',
									'label'   => 'Имя покупателя',
									'name'    => 'review_name',
									'type'    => 'text',
									'wrapper' => array( 'width' => '70' ),
								),
								array(
									'key'       => 'field_minka_review_text',
									'label'     => 'Отзыв',
									'name'      => 'review_text',
									'type'      => 'textarea',
									'rows'      => 4,
									'new_lines' => '',
								),
							),
						),
						'double' => array(
							'key'        => 'layout_minka_review_double',
							'name'       => 'double',
							'label'      => 'Два отзыва без фото',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'          => 'field_minka_review_blocks',
									'label'        => 'Отзывы',
									'name'         => 'review_blocks',
									'type'         => 'repeater',
									'min'          => 2,
									'max'          => 2,
									'layout'       => 'row',
									'button_label' => 'Добавить отзыв',
									'sub_fields'   => array(
										array(
											'key'   => 'field_minka_review_block_name',
											'label' => 'Имя покупателя',
											'name'  => 'review_name',
											'type'  => 'text',
										),
										array(
											'key'       => 'field_minka_review_block_text',
											'label'     => 'Отзыв',
											'name'      => 'review_text',
											'type'      => 'textarea',
											'rows'      => 4,
											'new_lines' => '',
										),
									),
								),
							),
						),
						'cta'    => array(
							'key'        => 'layout_minka_review_cta',
							'name'       => 'cta',
							'label'      => 'Отзыв с кнопкой',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'   => 'field_minka_review_cta_name',
									'label' => 'Имя покупателя',
									'name'  => 'review_name',
									'type'  => 'text',
								),
								array(
									'key'       => 'field_minka_review_cta_text',
									'label'     => 'Отзыв',
									'name'      => 'review_text',
									'type'      => 'textarea',
									'rows'      => 4,
									'new_lines' => '',
								),
								array(
									'key'           => 'field_minka_review_cta_link',
									'label'         => 'Кнопка',
									'name'          => 'review_link',
									'type'          => 'link',
									'return_format' => 'array',
								),
							),
						),
					),
				),
				array(
					'key'   => 'field_minka_faq_tab',
					'label' => 'Вопросы и ответы',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_minka_faq',
					'label'        => 'Вопросы и ответы',
					'name'         => 'faq_items',
					'type'         => 'repeater',
					'instructions' => 'Из этого же списка собирается микроразметка FAQ для поисковиков.',
					'layout'       => 'block',
					'button_label' => 'Добавить вопрос',
					'sub_fields'   => array(
						array(
							'key'      => 'field_minka_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------------------- Каталог */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_catalog',
			'title'    => 'Каталог',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-catalog' ) ) ),
			'fields'   => array(
				array(
					'key'           => 'field_minka_catalog_faq_inherit',
					'label'         => 'Показывать вопросы с главной',
					'name'          => 'catalog_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'instructions'  => 'Выключите, чтобы задать для каталога собственный список.',
				),
				array(
					'key'               => 'field_minka_catalog_faq',
					'label'             => 'Вопросы и ответы каталога',
					'name'              => 'catalog_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_catalog_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_catalog_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_catalog_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------- Карточка товара */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_product',
			'title'    => 'Карточка товара',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-product' ) ) ),
			'fields'   => array(
				array(
					'key'           => 'field_minka_product_faq_inherit',
					'label'         => 'Показывать вопросы с главной',
					'name'          => 'product_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'instructions'  => 'Выключите, чтобы задать для карточек товара собственный список.',
				),
				array(
					'key'               => 'field_minka_product_faq',
					'label'             => 'Вопросы и ответы на карточке товара',
					'name'              => 'product_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_product_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_product_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_product_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* -------------------------------------------------- Уход за шубой */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_care',
			'title'    => 'Уход за шубой',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-care' ) ) ),
			'fields'   => array(
				array(
					'key'   => 'field_minka_care_intro_tab',
					'label' => 'Вступление',
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_minka_care_title',
					'label' => 'Заголовок',
					'name'  => 'care_title',
					'type'  => 'text',
				),
				array(
					'key'          => 'field_minka_care_desc',
					'label'        => 'Текст',
					'name'         => 'care_desc',
					'type'         => 'textarea',
					'rows'         => 5,
					'new_lines'    => '',
					'instructions'  => 'Перенос строки можно поставить тегом &lt;br&gt;.',
				),
				array(
					'key'   => 'field_minka_care_note',
					'label' => 'Врезка под текстом',
					'name'  => 'care_note',
					'type'  => 'text',
				),
				array(
					'key'           => 'field_minka_care_image',
					'label'         => 'Фото',
					'name'          => 'care_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'   => 'field_minka_care_table_tab',
					'label' => 'Таблица',
					'type'  => 'tab',
				),
				array(
					'key'     => 'field_minka_care_head_left',
					'label'   => 'Заголовок левой колонки',
					'name'    => 'care_head_left',
					'type'    => 'text',
					'wrapper' => array( 'width' => '50' ),
				),
				array(
					'key'     => 'field_minka_care_head_right',
					'label'   => 'Заголовок правой колонки',
					'name'    => 'care_head_right',
					'type'    => 'text',
					'wrapper' => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_minka_care_rows',
					'label'        => 'Строки таблицы',
					'name'         => 'care_rows',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Добавить строку',
					'sub_fields'   => array(
						array(
							'key'   => 'field_minka_care_can',
							'label' => 'Можно и нужно',
							'name'  => 'care_can',
							'type'  => 'text',
						),
						array(
							'key'   => 'field_minka_care_cannot',
							'label' => 'Нельзя',
							'name'  => 'care_cannot',
							'type'  => 'text',
						),
					),
				),
				array(
					'key'   => 'field_minka_care_cleaning_tab',
					'label' => 'Чистка',
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_minka_care_cleaning_title',
					'label' => 'Заголовок блока',
					'name'  => 'care_cleaning_title',
					'type'  => 'text',
				),
				array(
					'key'          => 'field_minka_care_steps',
					'label'        => 'Признаки',
					'name'         => 'care_steps',
					'type'         => 'repeater',
					'instructions' => 'Нумерация проставляется сама.',
					'layout'       => 'table',
					'button_label' => 'Добавить признак',
					'sub_fields'   => array(
						array(
							'key'   => 'field_minka_care_step',
							'label' => 'Текст',
							'name'  => 'care_step',
							'type'  => 'text',
						),
					),
				),
				array(
					'key'   => 'field_minka_care_bottom_tab',
					'label' => 'Заявка и вопросы',
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_minka_care_cta_title',
					'label' => 'Заголовок блока с заявкой',
					'name'  => 'care_cta_title',
					'type'  => 'text',
				),
				array(
					'key'       => 'field_minka_care_cta_text',
					'label'     => 'Текст блока с заявкой',
					'name'      => 'care_cta_text',
					'type'      => 'textarea',
					'rows'      => 3,
					'new_lines' => '',
				),
				array(
					'key'           => 'field_minka_care_faq_inherit',
					'label'         => 'Показывать вопросы с главной',
					'name'          => 'care_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'instructions'  => 'Выключите, чтобы задать для страницы ухода собственный список.',
				),
				array(
					'key'               => 'field_minka_care_faq',
					'label'             => 'Вопросы и ответы — первый блок',
					'name'              => 'care_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_care_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_care_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_care_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
				array(
					'key'          => 'field_minka_care_faq_second',
					'label'        => 'Вопросы и ответы — второй блок',
					'name'         => 'care_faq_second',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => 'Добавить вопрос',
					'instructions' => 'Второй блок вопросов — тот, что идёт ниже, после признаков профессиональной чистки. Если оставить пустым, там повторится первый список.',
					'sub_fields'   => array(
						array(
							'key'      => 'field_minka_care_faq_second_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_care_faq_second_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------------------ О нас */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_about',
			'title'    => 'О нас',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-about' ) ) ),
			'fields'   => array(
				array(
					'key'   => 'field_minka_about_intro_tab',
					'label' => 'Вступление',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_minka_about_title',
					'label'        => 'Крупная фраза',
					'name'         => 'about_title',
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => '',
					'instructions' => 'Перенос строки можно поставить тегом &lt;br&gt;.',
				),
				array(
					'key'          => 'field_minka_about_paragraphs',
					'label'        => 'Абзацы',
					'name'         => 'about_paragraphs',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => 'Добавить абзац',
					'sub_fields'   => array(
						array(
							'key'       => 'field_minka_about_paragraph',
							'label'     => 'Текст',
							'name'      => 'about_paragraph',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
				array(
					'key'   => 'field_minka_about_selection_tab',
					'label' => 'Отбор меха и фото',
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_minka_about_subtitle',
					'label' => 'Заголовок блока',
					'name'  => 'about_subtitle',
					'type'  => 'text',
				),
				array(
					'key'       => 'field_minka_about_desc',
					'label'     => 'Текст блока',
					'name'      => 'about_desc',
					'type'      => 'textarea',
					'rows'      => 4,
					'new_lines' => '',
				),
				array(
					'key'           => 'field_minka_about_image',
					'label'         => 'Фото под текстом',
					'name'          => 'about_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_minka_about_cover',
					'label'         => 'Высокое фото справа',
					'name'          => 'about_cover',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'   => 'field_minka_about_stats_tab',
					'label' => 'Цифры',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_minka_about_stats',
					'label'        => 'Цифры о компании',
					'name'         => 'about_stats',
					'type'         => 'repeater',
					'layout'       => 'table',
					'max'          => 4,
					'button_label' => 'Добавить цифру',
					'sub_fields'   => array(
						array(
							'key'   => 'field_minka_about_stat_number',
							'label' => 'Число',
							'name'  => 'stat_number',
							'type'  => 'text',
						),
						array(
							'key'          => 'field_minka_about_stat_suffix',
							'label'        => 'Приписка',
							'name'         => 'stat_suffix',
							'type'         => 'text',
							'instructions' => '«+» встанет вплотную к числу, слово (например «года») — через пробел.',
						),
						array(
							'key'          => 'field_minka_about_stat_label',
							'label'        => 'Подпись',
							'name'         => 'stat_label',
							'type'         => 'text',
							'instructions' => 'Можно переносить строку тегом &lt;br class="about__label-br"&gt;.',
						),
					),
				),
				array(
					'key'   => 'field_minka_about_faq_tab',
					'label' => 'Вопросы и ответы',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_minka_about_faq_inherit',
					'label'         => 'Показывать вопросы с главной',
					'name'          => 'about_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
				),
				array(
					'key'               => 'field_minka_about_faq',
					'label'             => 'Вопросы и ответы страницы',
					'name'              => 'about_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_about_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_about_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_about_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* --------------------------------------------------------- Контакты */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_contacts',
			'title'    => 'Контакты',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-contacts' ) ) ),
			'fields'   => array(
				array(
					'key'   => 'field_minka_contacts_main_tab',
					'label' => 'Связь',
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_minka_contacts_title',
					'label' => 'Заголовок блока',
					'name'  => 'contacts_title',
					'type'  => 'text',
				),
				array(
					'key'          => 'field_minka_contacts_desc',
					'label'        => 'Текст под заголовком',
					'name'         => 'contacts_desc',
					'type'         => 'textarea',
					'rows'         => 4,
					'new_lines'    => '',
					'instructions' => 'Перенос строки — тегом &lt;br&gt;.',
				),
				array(
					'key'          => 'field_minka_contacts_phone_label',
					'label'        => 'Телефон',
					'name'         => 'contacts_phone_label',
					'type'         => 'text',
					'instructions' => 'Как показывать на странице: +375 (25) 702 8538. Оставьте пустым, чтобы убрать строку.',
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_minka_contacts_phone_number',
					'label'        => 'Телефон для ссылки',
					'name'         => 'contacts_phone_number',
					'type'         => 'text',
					'instructions' => 'В международном формате: +375257028538.',
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_minka_contacts_email',
					'label'        => 'Почта',
					'name'         => 'contacts_email',
					'type'         => 'email',
					'instructions' => 'Оставьте пустым, чтобы убрать строку.',
				),
				array(
					'key'     => 'field_minka_org_tab',
					'label'   => 'Организация',
					'type'    => 'tab',
				),
				array(
					'key'     => 'field_minka_org_note',
					'label'   => 'Данные для поисковых систем',
					'type'    => 'message',
					'message' => 'Эти поля не выводятся на страницах — они попадают в микроразметку, по которой Google показывает карточку компании. Телефон и почта берутся с вкладки «Связь».',
				),
				array(
					'key'          => 'field_minka_org_description',
					'label'        => 'Описание компании',
					'name'         => 'org_description',
					'type'         => 'textarea',
					'rows'         => 3,
					'instructions' => 'Одно-два предложения о том, чем занимается компания.',
				),
				array(
					'key'           => 'field_minka_org_logo',
					'label'         => 'Логотип для поисковых систем',
					'name'          => 'org_logo',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => 'Квадратный или близкий к нему, не меньше 112×112. Показывается в карточке компании и как издатель статей блога.',
				),
				array(
					'key'     => 'field_minka_org_locality',
					'label'   => 'Город',
					'name'    => 'org_locality',
					'type'    => 'text',
					'wrapper' => array( 'width' => '34' ),
				),
				array(
					'key'          => 'field_minka_org_street',
					'label'        => 'Улица и дом',
					'name'         => 'org_street',
					'type'         => 'text',
					'instructions' => 'Адрес шоурума. Можно оставить пустым.',
					'wrapper'      => array( 'width' => '33' ),
				),
				array(
					'key'          => 'field_minka_org_country',
					'label'        => 'Код страны',
					'name'         => 'org_country',
					'type'         => 'text',
					'instructions' => 'Две буквы: BY, RU, PL.',
					'wrapper'      => array( 'width' => '33' ),
				),
				array(
					'key'          => 'field_minka_org_postal',
					'label'        => 'Почтовый индекс',
					'name'         => 'org_postal',
					'type'         => 'text',
					'wrapper'      => array( 'width' => '34' ),
					'instructions' => 'Например, 220030. Можно оставить пустым.',
				),
				array(
					'key'          => 'field_minka_org_lat',
					'label'        => 'Широта',
					'name'         => 'org_lat',
					'type'         => 'text',
					'wrapper'      => array( 'width' => '33' ),
					'instructions' => 'Из Google Maps: правый клик по точке → первое число.',
				),
				array(
					'key'          => 'field_minka_org_lng',
					'label'        => 'Долгота',
					'name'         => 'org_lng',
					'type'         => 'text',
					'wrapper'      => array( 'width' => '33' ),
					'instructions' => 'Второе число оттуда же.',
				),
				array(
					'key'          => 'field_minka_org_days',
					'label'        => 'Дни работы шоурума',
					'name'         => 'org_days',
					'type'         => 'select',
					'choices'      => array(
						'all'  => 'Ежедневно',
						'mon6' => 'Пн–Сб',
						'mon5' => 'Пн–Пт',
					),
					'allow_null'   => 1,
					'wrapper'      => array( 'width' => '34' ),
					'instructions' => 'Попадает в микроразметку и в карточку компании в поиске.',
				),
				array(
					'key'     => 'field_minka_org_open',
					'label'   => 'Открытие',
					'name'    => 'org_open',
					'type'    => 'text',
					'wrapper' => array( 'width' => '33' ),
					'placeholder' => '10:00',
				),
				array(
					'key'     => 'field_minka_org_close',
					'label'   => 'Закрытие',
					'name'    => 'org_close',
					'type'    => 'text',
					'wrapper' => array( 'width' => '33' ),
					'placeholder' => '20:00',
				),
				array(
					'key'          => 'field_minka_org_price_range',
					'label'        => 'Ценовой сегмент',
					'name'         => 'org_price_range',
					'type'         => 'select',
					'choices'      => array(
						'$'    => '$ — бюджетный',
						'$$'   => '$$ — средний',
						'$$$'  => '$$$ — выше среднего',
						'$$$$' => '$$$$ — премиальный',
					),
					'allow_null'   => 1,
					'instructions' => 'Условное обозначение уровня цен для поисковых систем.',
				),
				array(
					'key'   => 'field_minka_contacts_extra_tab',
					'label' => 'Связь',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_minka_contacts_socials',
					'label'        => 'Кнопки мессенджеров и соцсетей',
					'name'         => 'contacts_socials',
					'type'         => 'repeater',
					'instructions' => 'Кнопки под контактами: подпись, ссылка и иконка (можно SVG).',
					'layout'       => 'table',
					'button_label' => 'Добавить кнопку',
					'sub_fields'   => array(
						array(
							'key'      => 'field_minka_contacts_social_label',
							'label'    => 'Подпись',
							'name'     => 'social_label',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'      => 'field_minka_contacts_social_url',
							'label'    => 'Ссылка',
							'name'     => 'social_url',
							'type'     => 'url',
							'required' => 1,
						),
						array(
							'key'           => 'field_minka_contacts_social_icon',
							'label'         => 'Иконка',
							'name'          => 'social_icon',
							'type'          => 'image',
							'return_format' => 'array',
							'preview_size'  => 'thumbnail',
							'required'      => 1,
						),
					),
				),
				array(
					'key'           => 'field_minka_contacts_image',
					'label'         => 'Фото слева',
					'name'          => 'contacts_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_minka_contacts_image_second',
					'label'         => 'Второе фото',
					'name'          => 'contacts_image_second',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'   => 'field_minka_contacts_blocks_tab',
					'label' => 'Шоурум и форма',
					'type'  => 'tab',
				),
				array(
					'key'   => 'field_minka_contacts_showroom_title',
					'label' => 'Заголовок блока «Шоурум»',
					'name'  => 'contacts_showroom_title',
					'type'  => 'text',
				),
				array(
					'key'       => 'field_minka_contacts_showroom_desc',
					'label'     => 'Текст блока «Шоурум»',
					'name'      => 'contacts_showroom_desc',
					'type'      => 'textarea',
					'rows'      => 3,
					'new_lines' => '',
				),
				array(
					'key'   => 'field_minka_contacts_showroom_button',
					'label' => 'Надпись на кнопке',
					'name'  => 'contacts_showroom_button',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_minka_contacts_form_title',
					'label' => 'Заголовок формы',
					'name'  => 'contacts_form_title',
					'type'  => 'text',
				),
				array(
					'key'       => 'field_minka_contacts_form_desc',
					'label'     => 'Текст над формой',
					'name'      => 'contacts_form_desc',
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
				),
				array(
					'key'           => 'field_minka_contacts_form_image',
					'label'         => 'Фото рядом с формой',
					'name'          => 'contacts_form_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'   => 'field_minka_contacts_faq_tab',
					'label' => 'Вопросы и ответы',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_minka_contacts_faq_inherit',
					'label'         => 'Показывать вопросы с главной',
					'name'          => 'contacts_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
				),
				array(
					'key'               => 'field_minka_contacts_faq',
					'label'             => 'Вопросы и ответы страницы',
					'name'              => 'contacts_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_contacts_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_contacts_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_contacts_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------------------- Блог */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_blog',
			'title'    => 'Блог',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-blog' ) ) ),
			'fields'   => array(
				array(
					'key'           => 'field_minka_blog_faq_inherit',
					'label'         => 'Показывать вопросы с главной',
					'name'          => 'blog_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'instructions'  => 'Действует и на список статей, и на страницу самой статьи.',
				),
				array(
					'key'               => 'field_minka_blog_faq',
					'label'             => 'Вопросы и ответы блога',
					'name'              => 'blog_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_blog_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_blog_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_blog_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------ Содержимое статьи */
	acf_add_local_field_group(
		array(
			'key'        => 'group_minka_post',
			'title'      => 'Содержимое страницы',
			'location'   => array(
				array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
				array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'template-legal.php' ) ),
			),
			'position'   => 'normal',
			'menu_order' => 5,
			'fields'     => array(
				array(
					'key'          => 'field_minka_post_blocks',
					'label'        => 'Блоки текста',
					'name'         => 'post_blocks',
					'type'         => 'flexible_content',
					'instructions' => 'Собирайте статью из блоков — каждый вставляется в вёрстку своим оформлением. Если блоков нет, покажется обычный текст из редактора.',
					'button_label' => 'Добавить блок',
					'layouts'      => array(
						'heading2' => array(
							'key'        => 'layout_minka_post_h2',
							'name'       => 'heading2',
							'label'      => 'Заголовок 2',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'      => 'field_minka_post_h2',
									'label'    => 'Текст заголовка',
									'name'     => 'heading',
									'type'     => 'text',
									'required' => 1,
								),
							),
						),
						'heading3' => array(
							'key'        => 'layout_minka_post_h3',
							'name'       => 'heading3',
							'label'      => 'Заголовок 3',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'      => 'field_minka_post_h3',
									'label'    => 'Текст заголовка',
									'name'     => 'heading',
									'type'     => 'text',
									'required' => 1,
								),
							),
						),
						'text'     => array(
							'key'        => 'layout_minka_post_text',
							'name'       => 'text',
							'label'      => 'Текст',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'          => 'field_minka_post_text',
									'label'        => 'Текст',
									'name'         => 'text',
									'type'         => 'wysiwyg',
									// all — две вкладки: «Визуально» и «Текст» (HTML).
									'tabs'         => 'all',
									'toolbar'      => 'basic',
									'media_upload' => 0,
									'instructions' => 'Абзацы, списки и ссылки. Между абзацами отступ ставится сам. Вкладка «Текст» показывает HTML — там же можно вставить готовую разметку.',
								),
							),
						),
						'image'    => array(
							'key'        => 'layout_minka_post_image',
							'name'       => 'image',
							'label'      => 'Изображение',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'           => 'field_minka_post_image',
									'label'         => 'Изображение',
									'name'          => 'image',
									'type'          => 'image',
									'return_format' => 'array',
									'preview_size'  => 'medium',
									'required'      => 1,
								),
								array(
									'key'   => 'field_minka_post_caption',
									'label' => 'Подпись',
									'name'  => 'caption',
									'type'  => 'text',
								),
							),
						),
						'table'    => array(
							'key'        => 'layout_minka_post_table',
							'name'       => 'table',
							'label'      => 'Таблица',
							'display'    => 'block',
							'sub_fields' => array(
								array(
									'key'          => 'field_minka_post_table_corner',
									'label'        => 'Заголовок первой колонки',
									'name'         => 'corner',
									'type'         => 'text',
									'instructions' => 'Колонка с заголовками строк. Оставьте пустым — угол таблицы будет пустым, как в вёрстке.',
								),
								array(
									'key'           => 'field_minka_post_table_first_left',
									'label'         => 'Первая колонка по левому краю',
									'name'          => 'first_column_left',
									'type'          => 'true_false',
									'ui'            => 1,
									'default_value' => 1,
									'instructions'  => 'Выключите, чтобы первая колонка была по центру, как остальные.',
								),
								array(
									'key'          => 'field_minka_post_table_head',
									'label'        => 'Заголовки остальных колонок',
									'name'         => 'columns',
									'type'         => 'repeater',
									'instructions' => 'Без первой колонки — её заголовок задаётся полем выше.',
									'layout'       => 'table',
									'button_label' => 'Добавить колонку',
									'sub_fields'   => array(
										array(
											'key'   => 'field_minka_post_table_column',
											'label' => 'Заголовок',
											'name'  => 'column',
											'type'  => 'text',
										),
									),
								),
								array(
									'key'          => 'field_minka_post_table_rows',
									'label'        => 'Строки',
									'name'         => 'rows',
									'type'         => 'repeater',
									'layout'       => 'block',
									'button_label' => 'Добавить строку',
									'sub_fields'   => array(
										array(
											'key'   => 'field_minka_post_table_row_title',
											'label' => 'Заголовок строки',
											'name'  => 'title',
											'type'  => 'text',
										),
										array(
											'key'          => 'field_minka_post_table_cells',
											'label'        => 'Ячейки',
											'name'         => 'cells',
											'type'         => 'repeater',
											'layout'       => 'table',
											'button_label' => 'Добавить ячейку',
											'sub_fields'   => array(
												array(
													'key'   => 'field_minka_post_table_cell',
													'label' => 'Значение',
													'name'  => 'cell',
													'type'  => 'text',
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------- Вопросы и ответы в статье */
	acf_add_local_field_group(
		array(
			'key'        => 'group_minka_post_faq',
			'title'      => 'Вопросы и ответы',
			'location'   => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) ),
			'position'   => 'normal',
			'menu_order' => 6,
			'fields'     => array(
				array(
					'key'           => 'field_minka_post_faq_inherit',
					'label'         => 'Показывать общие вопросы блога',
					'name'          => 'post_faq_inherit',
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 1,
					'instructions'  => 'Выключите, чтобы задать вопросы только для этой статьи. Общий список блога — в MINKA → Блог.',
				),
				array(
					'key'               => 'field_minka_post_faq',
					'label'             => 'Вопросы и ответы под статьёй',
					'name'              => 'post_faq',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => 'Добавить вопрос',
					'conditional_logic' => array( array( array( 'field' => 'field_minka_post_faq_inherit', 'operator' => '!=', 'value' => '1' ) ) ),
					'sub_fields'        => array(
						array(
							'key'      => 'field_minka_post_faq_question',
							'label'    => 'Вопрос',
							'name'     => 'faq_question',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'       => 'field_minka_post_faq_answer',
							'label'     => 'Ответ',
							'name'      => 'faq_answer',
							'type'      => 'textarea',
							'rows'      => 4,
							'new_lines' => '',
						),
					),
				),
			),
		)
	);

	/* ------------------------------------------------------ Страница 404 */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_404',
			'title'    => 'Страница 404',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-404' ) ) ),
			'fields'   => array(
				array(
					'key'           => 'field_minka_404_image',
					'label'         => 'Картинка',
					'name'          => 'notfound_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'   => 'field_minka_404_label',
					'label' => 'Подпись над заголовком',
					'name'  => 'notfound_label',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_minka_404_title',
					'label' => 'Заголовок',
					'name'  => 'notfound_title',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_minka_404_text',
					'label' => 'Текст под заголовком',
					'name'  => 'notfound_text',
					'type'  => 'text',
				),
				array(
					'key'     => 'field_minka_404_catalog_button',
					'label'   => 'Кнопка в каталог',
					'name'    => 'notfound_catalog_button',
					'type'    => 'text',
					'wrapper' => array( 'width' => '50' ),
				),
				array(
					'key'     => 'field_minka_404_home_button',
					'label'   => 'Ссылка на главную',
					'name'    => 'notfound_home_button',
					'type'    => 'text',
					'wrapper' => array( 'width' => '50' ),
				),
			),
		)
	);

	/* ---------------------------------------------------------- Аналитика */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_analytics',
			'title'    => 'Счётчики',
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'minka-analytics' ) ) ),
			'fields'   => array(
				array(
					'key'     => 'field_minka_analytics_note',
					'label'   => 'Как это работает',
					'type'    => 'message',
					'message' => 'Счётчики подключаются только после согласия посетителя в баннере cookie: Google Analytics и Метрика — по категории «статистика», Meta Pixel — по категории «маркетинг». Пустое поле означает, что счётчик не подключается вовсе.',
				),
				array(
					'key'          => 'field_minka_ga_id',
					'label'        => 'Google Analytics 4',
					'name'         => 'ga_measurement_id',
					'type'         => 'text',
					'placeholder'  => 'G-XXXXXXXXXX',
					'instructions' => 'Идентификатор потока данных из Google Analytics: Администратор → Потоки данных.',
				),
				array(
					'key'          => 'field_minka_meta_pixel',
					'label'        => 'Meta Pixel',
					'name'         => 'meta_pixel_id',
					'type'         => 'text',
					'placeholder'  => '000000000000000',
					'instructions' => 'Идентификатор пикселя из Meta Events Manager. Он же используется для серверной отправки событий (Conversions API), поэтому значение должно совпадать с тем, что указано в рекламном кабинете. Пиксель ждёт согласия на маркетинг, а не на статистику.',
				),
				array(
					'key'          => 'field_minka_metrika_id',
					'label'        => 'Яндекс.Метрика',
					'name'         => 'metrika_id',
					'type'         => 'text',
					'placeholder'  => '00000000',
					'instructions' => 'Номер счётчика. Можно оставить пустым, если Метрика не используется.',
				),
			),
		)
	);

	/* ------------------------------------------- Похожие модели у товара */
	acf_add_local_field_group(
		array(
			'key'      => 'group_minka_related',
			'title'    => 'Похожие модели',
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'product' ) ) ),
			'position' => 'normal',
			'menu_order' => 5,
			'fields'   => array(
				array(
					'key'           => 'field_minka_related_products',
					'label'         => 'Показывать в блоке «Похожие модели»',
					'name'          => 'related_products',
					'type'          => 'relationship',
					'post_type'     => array( 'product' ),
					'filters'       => array( 'search' ),
					'elements'      => array( 'featured_image' ),
					'max'           => 4,
					'return_format' => 'id',
					'instructions'  => 'Не больше четырёх моделей — столько помещается в блок. Если ничего не выбрано, WooCommerce подберёт похожие сам.',
				),
			),
		)
	);
}
add_action( 'acf/init', 'minka_acf_fields' );
