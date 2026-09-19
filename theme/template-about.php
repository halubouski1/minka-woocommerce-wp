<?php
/**
 * Template Name: О нас
 */

defined( 'ABSPATH' ) || exit;

get_header();

$minka_intro     = minka_about_intro();
$minka_selection = minka_about_selection();
$minka_stats     = minka_about_stats();
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

			<div class="about">
				<div class="about__left">
					<div class="about__top">
						<p class="about__title section-title"><?php echo wp_kses_post( $minka_intro['title'] ); ?></p>
						<div class="about__text">
							<?php foreach ( $minka_intro['paragraphs'] as $minka_paragraph ) : ?>
								<p><?php echo wp_kses_post( $minka_paragraph ); ?></p>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="about__bottom">
						<div class="about__bottom-left">
							<h2 class="about__subtitle section-title"><?php echo esc_html( $minka_selection['subtitle'] ); ?></h2>
							<p class="about__desc"><?php echo wp_kses_post( $minka_selection['desc'] ); ?></p>
							<?php if ( $minka_selection['image'] ) : ?>
								<img class="about__img" src="<?php echo esc_url( $minka_selection['image']['url'] ); ?>" alt="<?php echo esc_attr( $minka_selection['image']['alt'] ); ?>" width="<?php echo esc_attr( $minka_selection['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_selection['image']['height'] ); ?>">
							<?php endif; ?>
						</div>

						<?php if ( $minka_stats ) : ?>
							<div class="about__stats">
								<?php foreach ( $minka_stats as $minka_stat ) : ?>
									<div class="about__stat">
										<span class="about__num">
											<?php
											echo esc_html( $minka_stat['number'] );

											// «+» вплотную к числу, слово — через пробел и другим начертанием.
											if ( '+' === $minka_stat['suffix'] ) {
												echo '<span class="about__plus">+</span>';
											} elseif ( $minka_stat['suffix'] ) {
												echo ' <span class="about__num-sub">' . esc_html( $minka_stat['suffix'] ) . '</span>';
											}
											?>
										</span>
										<span class="about__label"><?php echo wp_kses_post( $minka_stat['label'] ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $minka_selection['cover'] ) : ?>
					<img class="about__img--right" src="<?php echo esc_url( $minka_selection['cover']['url'] ); ?>" alt="<?php echo esc_attr( $minka_selection['cover']['alt'] ? $minka_selection['cover']['alt'] : 'Норковая шуба' ); ?>" width="<?php echo esc_attr( $minka_selection['cover']['width'] ); ?>" height="<?php echo esc_attr( $minka_selection['cover']['height'] ); ?>">
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_about_faq_items() ) ); ?>
</main>

<?php
get_footer();
