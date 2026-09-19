<?php
/**
 * Template Name: Правовая страница
 *
 * Политика конфиденциальности, оферта и подобные тексты: та же вёрстка, что
 * у статьи блога, и те же блоки в редакторе (заголовки, текст, таблица).
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

<main id="main-content">
	<div class="catalog" data-aos="fade-up">
		<div class="container">
			<nav class="catalog__breadcrumbs" aria-label="Навигационная цепочка">
				<a class="catalog__crumb" href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
				<span class="catalog__sep">/</span>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php the_title(); ?></span>
			</nav>
		</div>
	</div>

	<div data-aos="fade-up">
		<div class="container">
			<article <?php post_class( 'post' ); ?>>
				<h1 class="section-title"><?php the_title(); ?></h1>
				<?php get_template_part( 'template-parts/post-blocks' ); ?>
			</article>
		</div>
	</div>
</main>

	<?php
endwhile;

get_footer();
