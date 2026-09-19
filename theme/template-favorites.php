<?php
/**
 * Template Name: Избранное
 *
 * Список хранится в браузере, поэтому карточки подставляет favorites.js:
 * пустое состояние и список лежат в разметке рядом, а скрипт показывает нужное.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main-content">
	<section class="catalog" data-aos="fade-up">
		<div class="container">
			<nav class="catalog__breadcrumbs" aria-label="Навигационная цепочка">
				<a class="catalog__crumb" href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
				<span class="catalog__sep">/</span>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php the_title(); ?></span>
			</nav>
			<h1 class="catalog__title section-title"><?php the_title(); ?></h1>

			<div class="favorites-empty" data-favorites-empty hidden>
				<h2 class="favorites-empty__title section-title">Вы пока ничего не&nbsp;добавили в&nbsp;избранное</h2>
				<p class="favorites-empty__text">Перейдите в&nbsp;каталог&nbsp;— мы собрали модели, достойные внимания</p>
				<a class="hero__catalog buy__catalog favorites-empty__btn" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">В&nbsp;каталог</a>
			</div>
		</div>
	</section>

	<div class="popular favorites section-padding" data-aos="fade-up" data-favorites hidden>
		<div class="container">
			<div class="popular__list" data-favorites-list></div>
		</div>
	</div>

	<?php
	$minka_popular = minka_popular_products();

	if ( $minka_popular ) :
		?>
		<section class="popular popular--slider section-padding" data-aos="fade-up">
			<div class="container">
				<div class="popular__top">
					<h2 class="popular__title section-title">Популярные модели</h2>
					<a class="hero__availability popular__link" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">Смотреть каталог</a>
					<div class="popular__nav">
						<button class="popular__arrow popular__arrow--prev" type="button" aria-label="Предыдущий слайд">
							<img src="<?php echo esc_url( minka_asset( 'assets/icons/slider-arrow.svg' ) ); ?>" alt="" width="10" height="16">
						</button>
						<button class="popular__arrow popular__arrow--next" type="button" aria-label="Следующий слайд">
							<img src="<?php echo esc_url( minka_asset( 'assets/icons/slider-arrow.svg' ) ); ?>" alt="" width="10" height="16">
						</button>
					</div>
				</div>

				<div class="popular__slider">
					<div class="popular__list">
						<?php foreach ( $minka_popular as $minka_product ) : ?>
							<?php get_template_part( 'template-parts/card', 'product', array( 'product' => $minka_product ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="popular__pagination"></div>

				<a class="popular__catalog hero__catalog buy__catalog" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">Смотреть каталог</a>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_faq_items() ) ); ?>
</main>

<?php
get_footer();
