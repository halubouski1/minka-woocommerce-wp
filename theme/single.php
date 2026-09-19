<?php
/**
 * Статья блога.
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
				<a class="catalog__crumb" href="<?php echo esc_url( minka_page_url( 'blog' ) ); ?>"><?php echo esc_html( minka_blog_title() ); ?></a>
				<span class="catalog__sep">/</span>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php the_title(); ?></span>
			</nav>
		</div>
	</div>

	<div data-aos="fade-up">
		<div class="container">
			<?php if ( has_post_thumbnail() ) : ?>
				<img class="post__main" src="<?php echo esc_url( get_the_post_thumbnail_url( get_post(), 'full' ) ); ?>" alt="<?php the_title_attribute(); ?>" width="1224" height="561">
			<?php endif; ?>

			<article <?php post_class( 'post' ); ?>>
				<h1 class="section-title"><?php the_title(); ?></h1>
				<?php get_template_part( 'template-parts/post-blocks' ); ?>
			</article>
		</div>
	</div>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_post_faq_items() ) ); ?>

	<?php
	$minka_articles = minka_recent_posts( 3, get_the_ID() );

	if ( $minka_articles ) :
		?>
		<section class="popular popular--slider section-padding" data-aos="fade-up">
			<div class="container">
				<div class="popular__top">
					<h2 class="popular__title section-title">Читайте также</h2>
					<a class="hero__availability popular__link" href="<?php echo esc_url( minka_page_url( 'blog' ) ); ?>">Смотреть статьи</a>
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
						<?php foreach ( $minka_articles as $minka_article ) : ?>
							<?php get_template_part( 'template-parts/card', 'post', array( 'post' => $minka_article ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="popular__pagination"></div>

				<a class="popular__catalog hero__catalog buy__catalog" href="<?php echo esc_url( minka_page_url( 'blog' ) ); ?>">Смотреть статьи</a>
			</div>
		</section>
	<?php endif; ?>
</main>

	<?php
endwhile;

get_footer();
