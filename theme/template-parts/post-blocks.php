<?php
/**
 * Тело статьи, собранное из блоков ACF.
 *
 * Каждый блок печатается той разметкой, что и в вёрстке: текст — внутри
 * .post__text (там задан отступ между абзацами), картинка — .post__figure
 * с подписью, таблица — .post__table-wrap со скроллом на узких экранах.
 */

defined( 'ABSPATH' ) || exit;

$minka_blocks = function_exists( 'get_field' ) ? get_field( 'post_blocks' ) : null;

// Блоков нет — значит статья написана обычным редактором.
if ( ! $minka_blocks ) {
	the_content();

	return;
}

foreach ( $minka_blocks as $minka_block ) :
	$minka_type = isset( $minka_block['acf_fc_layout'] ) ? $minka_block['acf_fc_layout'] : '';

	if ( 'heading2' === $minka_type ) :
		?>
		<h2><?php echo esc_html( $minka_block['heading'] ); ?></h2>

		<?php
	elseif ( 'heading3' === $minka_type ) :
		?>
		<h3><?php echo esc_html( $minka_block['heading'] ); ?></h3>

		<?php
	elseif ( 'text' === $minka_type ) :
		?>
		<div class="post__text"><?php echo wp_kses_post( $minka_block['text'] ); ?></div>

		<?php
	elseif ( 'image' === $minka_type ) :
		$minka_image = isset( $minka_block['image'] ) ? $minka_block['image'] : null;

		if ( ! empty( $minka_image['url'] ) ) :
			?>
			<div class="post__figure">
				<img src="<?php echo esc_url( $minka_image['url'] ); ?>" alt="<?php echo esc_attr( $minka_image['alt'] ); ?>" width="<?php echo esc_attr( $minka_image['width'] ); ?>" height="<?php echo esc_attr( $minka_image['height'] ); ?>" loading="lazy">
				<?php if ( ! empty( $minka_block['caption'] ) ) : ?>
					<span class="post__caption"><?php echo esc_html( $minka_block['caption'] ); ?></span>
				<?php endif; ?>
			</div>

			<?php
		endif;
	elseif ( 'table' === $minka_type ) :
		$minka_columns = isset( $minka_block['columns'] ) ? (array) $minka_block['columns'] : array();
		$minka_rows    = isset( $minka_block['rows'] ) ? (array) $minka_block['rows'] : array();
		$minka_corner  = isset( $minka_block['corner'] ) ? $minka_block['corner'] : '';

		// В вёрстке первая колонка выровнена по левому краю; переключатель в
		// блоке позволяет поставить её по центру, как остальные.
		$minka_first_left = isset( $minka_block['first_column_left'] ) ? (bool) $minka_block['first_column_left'] : true;

		if ( $minka_rows ) :
			?>
			<div class="post__table-wrap">
				<table class="post__table<?php echo $minka_first_left ? '' : ' post__table--first-center'; ?>">
					<?php if ( $minka_columns || $minka_corner ) : ?>
						<thead>
							<tr>
								<?php if ( $minka_corner ) : ?>
									<th class="post__table-corner" scope="col"><?php echo esc_html( $minka_corner ); ?></th>
								<?php else : ?>
									<td class="post__table-corner"></td>
								<?php endif; ?>
								<?php foreach ( $minka_columns as $minka_column ) : ?>
									<th scope="col"><?php echo esc_html( $minka_column['column'] ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
					<?php endif; ?>

					<tbody>
						<?php foreach ( $minka_rows as $minka_row ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( isset( $minka_row['title'] ) ? $minka_row['title'] : '' ); ?></th>
								<?php foreach ( (array) ( isset( $minka_row['cells'] ) ? $minka_row['cells'] : array() ) as $minka_cell ) : ?>
									<td><?php echo esc_html( $minka_cell['cell'] ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php
		endif;
	endif;
endforeach;
