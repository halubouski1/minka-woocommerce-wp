<?php
/**
 * Шапка: <head>, хедер, оверлеи меню и поиска.
 */

defined( 'ABSPATH' ) || exit;

$minka_icons        = minka_asset( 'assets/icons' );
$minka_phone        = minka_header_phone();
$minka_menu_items   = minka_menu_items();
$minka_menu_default = minka_menu_default_card();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">Перейти к содержимому</a>

<header class="header">
	<div class="container header__container">
		<div class="header__left">
			<?php /* Подписи на узких экранах скрыты вёрсткой, поэтому название кнопки задаётся ещё и через aria-label — иначе для скринридера остаётся только иконка. */ ?>
			<button class="header__btn header__btn--burger" type="button" data-menu-open aria-label="Меню">
				<img src="<?php echo esc_url( $minka_icons . '/burger-menu.svg' ); ?>" alt="" width="15" height="5">
				<span>Меню</span>
			</button>
			<button class="header__btn header__btn--search" type="button" data-search-open aria-label="Поиск">
				<img src="<?php echo esc_url( $minka_icons . '/search-icon.svg' ); ?>" alt="" width="12" height="12">
				<span>Поиск</span>
			</button>
			<a class="header__btn header__btn--favorites" href="<?php echo esc_url( minka_page_url( 'favorites' ) ); ?>" aria-label="Избранное">
				<img src="<?php echo esc_url( $minka_icons . '/favorites.svg' ); ?>" alt="" width="12" height="12">
				<span>Избранное</span>
			</a>
		</div>

		<a class="header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( $minka_icons . '/logo.svg' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="145" height="26">
		</a>

		<?php if ( $minka_phone ) : ?>
			<a class="header__phone" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $minka_phone['number'] ) ); ?>" aria-label="Позвонить: <?php echo esc_attr( $minka_phone['label'] ); ?>">
				<img src="<?php echo esc_url( $minka_icons . '/call.svg' ); ?>" alt="" width="10" height="10">
				<span><?php echo esc_html( $minka_phone['label'] ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</header>

<div class="menu-overlay" data-menu-close></div>
<div class="menu" id="menu" data-lenis-prevent role="dialog" aria-modal="true" aria-label="Главное меню">
	<div class="menu__panel">
		<button class="menu__close" type="button" data-menu-close aria-label="Закрыть меню">
			<img src="<?php echo esc_url( $minka_icons . '/close.svg' ); ?>" alt="" width="18" height="18">
		</button>

		<div class="container">
			<a class="menu__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( $minka_icons . '/logo-dark.svg' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="261" height="47">
			</a>

			<div class="menu__body">
				<nav class="menu__nav">
					<?php foreach ( $minka_menu_items as $minka_item ) : ?>
						<a class="menu__link section-title" href="<?php echo esc_url( $minka_item['url'] ); ?>" data-menu-target="<?php echo esc_attr( $minka_item['key'] ); ?>"><?php echo esc_html( $minka_item['title'] ); ?></a>
					<?php endforeach; ?>
				</nav>

				<div class="menu__preview">
					<div class="menu__card menu__card--active" data-menu-card="default">
						<p class="menu__desc"><?php echo wp_kses_post( $minka_menu_default['text'] ); ?></p>
						<?php if ( $minka_menu_default['image'] ) : ?>
							<img class="menu__img" src="<?php echo esc_url( $minka_menu_default['image']['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_menu_default['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_menu_default['image']['height'] ); ?>">
						<?php endif; ?>
					</div>

					<?php foreach ( $minka_menu_items as $minka_item ) : ?>
						<div class="menu__card" data-menu-card="<?php echo esc_attr( $minka_item['key'] ); ?>">
							<p class="menu__desc"><?php echo wp_kses_post( $minka_item['text'] ); ?></p>
							<?php if ( $minka_item['image'] ) : ?>
								<img class="menu__img" src="<?php echo esc_url( $minka_item['image']['url'] ); ?>" alt="<?php echo esc_attr( $minka_item['image']['alt'] ); ?>" width="<?php echo esc_attr( $minka_item['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_item['image']['height'] ); ?>">
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="search-overlay" data-search-close></div>
<div class="search" id="search" data-lenis-prevent role="dialog" aria-modal="true" aria-label="Поиск по сайту">
	<div class="search__panel">
		<button class="search__close" type="button" data-search-close aria-label="Закрыть поиск">
			<img src="<?php echo esc_url( $minka_icons . '/close.svg' ); ?>" alt="" width="18" height="18">
		</button>

		<div class="container">
			<a class="search__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( $minka_icons . '/logo-dark.svg' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="261" height="47">
			</a>

			<div class="search__box">
				<form class="search__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
					<input class="search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Начните искать" aria-label="Поиск по каталогу" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="searchSuggest" aria-autocomplete="list">
					<button class="search__clear" type="button" data-search-clear>Очистить</button>
				</form>
				<div class="search__suggest" id="searchSuggest" role="listbox" aria-label="Варианты поиска"></div>
				<p class="search__empty" role="status" aria-live="polite"></p>
			</div>

			<div class="search__results">
				<p class="hero__availability popular__link search__count" aria-live="polite" aria-atomic="true">435 результатов</p>
				<div class="search__grid"></div>
			</div>

			<div class="popular popular--slider search__popular">
				<div class="popular__top">
					<h2 class="popular__title section-title">Может быть интересно</h2>
					<a class="hero__availability popular__link" href="<?php echo esc_url( minka_page_url( 'catalog' ) ); ?>">Смотреть каталог</a>
					<div class="popular__nav">
						<button class="popular__arrow popular__arrow--prev" type="button" aria-label="Предыдущий слайд">
							<img src="<?php echo esc_url( $minka_icons . '/slider-arrow.svg' ); ?>" alt="" width="10" height="16">
						</button>
						<button class="popular__arrow popular__arrow--next" type="button" aria-label="Следующий слайд">
							<img src="<?php echo esc_url( $minka_icons . '/slider-arrow.svg' ); ?>" alt="" width="10" height="16">
						</button>
					</div>
				</div>

				<div class="popular__slider">
					<div class="popular__list">
						<?php foreach ( minka_search_interesting() as $minka_interesting ) : ?>
							<?php get_template_part( 'template-parts/card', 'product', array( 'product' => $minka_interesting ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="popular__pagination"></div>

				<a class="hero__availability popular__link popular__link--search" href="<?php echo esc_url( minka_page_url( 'catalog' ) ); ?>">Смотреть каталог</a>
			</div>
		</div>
	</div>
</div>
