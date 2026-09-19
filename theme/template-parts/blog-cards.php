<?php
/**
 * Карточки статей блога.
 *
 * Отдаются и обычным запросом, и AJAX-догрузкой по кнопке «Показать ещё»,
 * поэтому разметка живёт в одном месте.
 */

defined( 'ABSPATH' ) || exit;

while ( have_posts() ) :
	the_post();
	?>
	<a class="blog__card" href="<?php the_permalink(); ?>">
		<div class="blog__img-wrap">
			<?php
			// Обложка статьи — содержательная картинка, а не украшение: alt берём
			// из медиатеки, а если он там не заполнен — из заголовка статьи.
			$minka_card_alt = minka_attachment_alt( get_post_thumbnail_id(), get_the_title() );
			?>
			<img class="blog__img" src="<?php echo esc_url( minka_post_thumbnail_url( get_post() ) ); ?>" alt="<?php echo esc_attr( $minka_card_alt ); ?>" width="607" height="480" loading="lazy">
		</div>
		<h2 class="blog__title"><?php the_title(); ?></h2>
		<span class="blog__time"><?php echo esc_html( minka_reading_time( get_post() ) ); ?></span>
	</a>
	<?php
endwhile;
