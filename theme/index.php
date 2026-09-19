<?php
/**
 * Общий фолбэк-шаблон.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main-content" class="page">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'page__article' ); ?>>
					<h1 class="page__title section-title"><?php the_title(); ?></h1>
					<div class="page__content"><?php the_content(); ?></div>
				</article>
				<?php
			endwhile;

			the_posts_pagination();
		else :
			?>
			<p>Записей не найдено.</p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
