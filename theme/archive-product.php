<?php
/**
 * Каталог товаров: /catalog/ и категории товаров.
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/catalog-filters', 'mobile' );
?>

<main id="main-content">
<section class="catalog" data-aos="fade-up">
	<div class="container">
		<nav class="catalog__breadcrumbs" aria-label="Навигационная цепочка">
			<a class="catalog__crumb" href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
			<span class="catalog__sep">/</span>
			<?php if ( is_product_taxonomy() ) : ?>
				<a class="catalog__crumb" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">Каталог</a>
				<span class="catalog__sep">/</span>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php echo esc_html( single_term_title( '', false ) ); ?></span>
			<?php else : ?>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php woocommerce_page_title(); ?></span>
			<?php endif; ?>
		</nav>

		<h1 class="catalog__title section-title"><?php echo is_product_taxonomy() ? esc_html( single_term_title( '', false ) ) : esc_html( woocommerce_page_title( false ) ); ?></h1>

		<?php get_template_part( 'template-parts/catalog-filters', 'desktop' ); ?>

		<div class="catalog__results" data-catalog-results>
			<?php get_template_part( 'template-parts/catalog-grid' ); ?>
		</div>
	</div>
</section>

<section class="cta" data-aos="fade-up">
	<div class="container cta__container">
		<h2 class="cta__title section-title">Не нашли свою модель?</h2>
		<p class="cta__desc">Опишите, что ищете&nbsp;&mdash; подберём норковую шубу под ваш размер, бюджет и фасон</p>
		<button class="hero__catalog buy__catalog cta__btn" type="button" data-popup="cta">Оставить заявку</button>
	</div>
</section>

<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_catalog_faq_items() ) ); ?>
</main>

<?php
get_footer();
