<?php
/**
 * Template Name: Уход за шубой
 */

defined( 'ABSPATH' ) || exit;

get_header();

$minka_intro    = minka_care_intro();
$minka_table    = minka_care_table();
$minka_cleaning = minka_care_cleaning();
$minka_cta      = minka_care_cta();
$minka_articles = minka_recent_posts( 3 );
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

			<div class="care__intro">
				<div class="care__text">
					<h2 class="care__title section-title"><?php echo esc_html( $minka_intro['title'] ); ?></h2>
					<p class="care__desc"><?php echo wp_kses_post( $minka_intro['desc'] ); ?></p>
					<?php if ( $minka_intro['note'] ) : ?>
						<span class="care__note"><?php echo esc_html( $minka_intro['note'] ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $minka_intro['image'] ) : ?>
					<img class="care__image" src="<?php echo esc_url( $minka_intro['image']['url'] ); ?>" alt="<?php echo esc_attr( $minka_intro['image']['alt'] ); ?>" width="<?php echo esc_attr( $minka_intro['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_intro['image']['height'] ); ?>">
				<?php endif; ?>
			</div>

			<?php if ( $minka_table['rows'] ) : ?>
				<div class="care__table">
					<div class="care__row care__row--head">
						<div class="care__cell care__cell--left section-title"><?php echo esc_html( $minka_table['head_left'] ); ?></div>
						<div class="care__cell section-title"><?php echo esc_html( $minka_table['head_right'] ); ?></div>
					</div>
					<?php foreach ( $minka_table['rows'] as $minka_row ) : ?>
						<div class="care__row">
							<div class="care__cell care__cell--left"><?php echo esc_html( $minka_row['can'] ); ?></div>
							<div class="care__cell"><?php echo esc_html( $minka_row['cannot'] ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php
	// В вёрстке два блока вопросов: до блока про чистку и после него.
	$minka_faq = minka_care_faq_items();

	get_template_part( 'template-parts/faq', null, array( 'items' => $minka_faq ) );
	?>

	<?php if ( $minka_cleaning['steps'] ) : ?>
		<section class="buy">
			<div class="container buy__container" data-aos="fade-up" data-aos-delay="100">
				<h2 class="buy__title section-title"><?php echo esc_html( $minka_cleaning['title'] ); ?></h2>

				<div class="buy__steps">
					<?php foreach ( $minka_cleaning['steps'] as $minka_index => $minka_step ) : ?>
						<div class="buy__step">
							<span class="buy__number"><?php echo esc_html( sprintf( '%02d', $minka_index + 1 ) ); ?></span>
							<p class="buy__text"><?php echo wp_kses_post( $minka_step ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_care_faq_second_items() ) ); ?>

	<?php if ( $minka_articles ) : ?>
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

	<section class="cta" data-aos="fade-up">
		<div class="container cta__container">
			<h2 class="cta__title section-title"><?php echo esc_html( $minka_cta['title'] ); ?></h2>
			<p class="cta__desc"><?php echo wp_kses_post( $minka_cta['text'] ); ?></p>
			<button class="hero__catalog buy__catalog cta__btn" type="button" data-popup="cta">Оставить заявку</button>
		</div>
	</section>
</main>

<?php
get_footer();
