<?php
/**
 * Страница «не найдено».
 */

defined( 'ABSPATH' ) || exit;

get_header();

$minka_notfound = minka_notfound_content();
?>

<main id="main-content">
	<section class="catalog" data-aos="fade-up">
		<div class="container">
			<div class="notfound">
				<?php if ( $minka_notfound['image'] ) : ?>
					<img class="notfound__img" src="<?php echo esc_url( $minka_notfound['image']['url'] ); ?>" alt="404" width="<?php echo esc_attr( $minka_notfound['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_notfound['image']['height'] ); ?>">
				<?php endif; ?>

				<span class="notfound__label"><?php echo esc_html( $minka_notfound['label'] ); ?></span>
				<h1 class="notfound__title section-title"><?php echo esc_html( $minka_notfound['title'] ); ?></h1>
				<span class="notfound__text"><?php echo esc_html( $minka_notfound['text'] ); ?></span>

				<a class="hero__catalog buy__catalog notfound__btn" href="<?php echo esc_url( minka_catalog_base_url() ); ?>"><?php echo esc_html( $minka_notfound['catalog_button'] ); ?></a>
				<a class="hero__availability notfound__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $minka_notfound['home_button'] ); ?></a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
