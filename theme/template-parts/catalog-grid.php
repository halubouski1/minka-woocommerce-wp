<?php
/**
 * Сетка каталога: ряды «четыре карточки + одна крупная» и кнопка «Показать ещё».
 *
 * Отдаётся и обычным запросом, и AJAX-обновлением фильтров, поэтому
 * разметка живёт в одном месте.
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$minka_products = array();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$minka_product = wc_get_product( get_the_ID() );

		if ( $minka_product && $minka_product->is_visible() ) {
			$minka_products[] = $minka_product;
		}
	}
	rewind_posts();
}

$minka_rows = array_chunk( $minka_products, 5 );

// Ряды чередуют сторону крупной карточки, и на второй странице чередование
// должно продолжиться, а не начаться заново.
$minka_paged     = max( 1, (int) $wp_query->get( 'paged' ) );
$minka_per_page  = (int) $wp_query->get( 'posts_per_page' );
$minka_row_shift = ( $minka_paged - 1 ) * (int) ceil( max( 1, $minka_per_page ) / 5 );
?>
<?php if ( $minka_rows ) : ?>
	<div class="catalog__grid">
		<?php
		foreach ( $minka_rows as $minka_index => $minka_row ) :
			// Неполный хвост ряда идёт обычной сеткой: без крупной карточки
			// квадрат растянулся бы на всю ширину, и карточки стали бы огромными.
			$minka_is_rest = count( $minka_row ) < 5;
			$minka_classes = 'catalog__row';

			if ( $minka_is_rest ) {
				$minka_classes .= ' catalog__row--rest';
			} elseif ( ( $minka_index + $minka_row_shift ) % 2 ) {
				$minka_classes .= ' catalog__row--reverse';
			}
			?>
			<div class="<?php echo esc_attr( $minka_classes ); ?>">
				<div class="catalog__quad">
					<?php foreach ( array_slice( $minka_row, 0, 4 ) as $minka_product ) : ?>
						<?php get_template_part( 'template-parts/card', 'product', array( 'product' => $minka_product ) ); ?>
					<?php endforeach; ?>
				</div>

				<?php if ( isset( $minka_row[4] ) ) : ?>
					<div class="catalog__feature">
						<?php get_template_part( 'template-parts/card', 'product', array( 'product' => $minka_row[4] ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php
	$minka_next = minka_catalog_next_link();

	if ( $minka_next ) :
		?>
		<a class="hero__catalog buy__catalog catalog__more" href="<?php echo esc_url( $minka_next ); ?>" data-aos="fade-up">Показать ещё</a>
	<?php endif; ?>

<?php else : ?>
	<div class="catalog__empty">
		<p class="catalog__empty-text">По выбранным параметрам ничего не нашлось. Попробуйте изменить фильтры или <a href="<?php echo esc_url( minka_catalog_base_url() ); ?>" data-filters-reset>сбросить их</a>.</p>
	</div>
<?php endif; ?>
