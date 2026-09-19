<?php
/**
 * Карточка статьи — блок «Читайте также» и списки блога.
 *
 * Ждёт объект записи в $args['post'].
 */

defined( 'ABSPATH' ) || exit;

$minka_post = isset( $args['post'] ) ? $args['post'] : null;

if ( ! $minka_post ) {
	return;
}

$minka_thumb = get_the_post_thumbnail_url( $minka_post, 'large' );

if ( ! $minka_thumb ) {
	$minka_thumb = minka_asset( 'assets/img/blog-img-1.webp' );
}

$minka_title = get_the_title( $minka_post );
$minka_alt   = minka_attachment_alt( get_post_thumbnail_id( $minka_post ), $minka_title );
?>
<a class="popular-card" href="<?php echo esc_url( get_permalink( $minka_post ) ); ?>">
	<div class="popular-card__media">
		<img class="popular-card__img" src="<?php echo esc_url( $minka_thumb ); ?>" alt="<?php echo esc_attr( $minka_alt ); ?>" width="452" height="535" loading="lazy">
	</div>
	<p class="popular-card__title"><?php echo esc_html( $minka_title ); ?></p>
	<p class="popular-card__price"><?php echo esc_html( minka_reading_time( $minka_post ) ); ?></p>
</a>
