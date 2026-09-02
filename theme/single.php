<?php
/**
 * Single post / portfolio case.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="page">
	<div class="page__inner">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'page__article' ); ?>>
				<h1 class="page__title"><?php the_title(); ?></h1>
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="page__thumb"><?php the_post_thumbnail( 'full' ); ?></div>
				<?php endif; ?>
				<div class="page__content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();
