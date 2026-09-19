<?php
/**
 * Блог — список статей.
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
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php echo esc_html( minka_blog_title() ); ?></span>
			</nav>

			<h1 class="catalog__title section-title"><?php echo esc_html( minka_blog_title() ); ?></h1>

			<?php if ( have_posts() ) : ?>
				<div class="blog__grid" data-blog-grid>
					<?php get_template_part( 'template-parts/blog-cards' ); ?>
				</div>

				<?php
				$minka_next = minka_blog_next_link();

				// Кнопка нужна и когда есть следующая страница, и когда часть уже
				// отданных карточек скрыта капом вёрстки (на узких экранах).
				if ( $minka_next || $GLOBALS['wp_query']->post_count > MINKA_BLOG_MOBILE_CAP ) :
					?>
					<a class="hero__catalog buy__catalog blog__more" href="<?php echo esc_url( $minka_next ? $minka_next : '#' ); ?>" data-blog-more>Показать ещё</a>
				<?php endif; ?>

			<?php else : ?>
				<p class="blog__empty">Пока ни одной статьи. Скоро здесь появятся материалы о мехе и уходе за шубой.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_blog_faq_items() ) ); ?>
</main>

<?php
get_footer();
