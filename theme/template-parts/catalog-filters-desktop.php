<?php
/**
 * Фильтры каталога — десктопная строка.
 */

defined( 'ABSPATH' ) || exit;

$minka_price = minka_catalog_price_range();
$minka_stock = minka_catalog_chosen_stock();
$minka_sorts = minka_catalog_sort_options();
$minka_sort  = minka_catalog_current_sort();
?>
<div class="catalog__filters" data-catalog-filters>
	<div class="catalog__filters-left">
		<div class="catalog__filter-group">
			<?php
			foreach ( minka_catalog_filter_groups() as $minka_group ) :
				$minka_terms = minka_catalog_terms( $minka_group['taxonomy'] );

				if ( ! $minka_terms ) {
					continue;
				}

				$minka_chosen = minka_catalog_chosen_terms( $minka_group['taxonomy'] );
				?>
				<div class="catalog__filter">
					<button class="catalog__filter-btn" type="button" aria-expanded="false"><?php echo esc_html( $minka_group['label'] ); ?></button>
					<div class="catalog__dropdown">
						<?php foreach ( $minka_terms as $minka_term ) : ?>
							<label class="catalog__option">
								<input class="catalog__checkbox" type="checkbox"
									data-filter-taxonomy="<?php echo esc_attr( $minka_group['taxonomy'] ); ?>"
									data-filter-value="<?php echo esc_attr( $minka_term->slug ); ?>"
									<?php checked( in_array( $minka_term->slug, $minka_chosen, true ) ); ?>>
								<span class="catalog__box"></span>
								<span><?php echo esc_html( $minka_term->name ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>

			<div class="catalog__filter catalog__filter--price">
				<button class="catalog__filter-btn" type="button" aria-expanded="false">Цена</button>
				<div class="catalog__dropdown catalog__dropdown--price">
					<div class="catalog__range">
						<div class="catalog__range-track"><div class="catalog__range-fill"></div></div>
						<input class="catalog__range-input catalog__range-input--min" type="range" data-filter-price="min" min="<?php echo esc_attr( $minka_price['bounds']['min'] ); ?>" max="<?php echo esc_attr( $minka_price['bounds']['max'] ); ?>" step="<?php echo esc_attr( $minka_price['bounds']['step'] ); ?>" value="<?php echo esc_attr( $minka_price['min'] ); ?>" aria-label="Минимальная цена">
						<input class="catalog__range-input catalog__range-input--max" type="range" data-filter-price="max" min="<?php echo esc_attr( $minka_price['bounds']['min'] ); ?>" max="<?php echo esc_attr( $minka_price['bounds']['max'] ); ?>" step="<?php echo esc_attr( $minka_price['bounds']['step'] ); ?>" value="<?php echo esc_attr( $minka_price['max'] ); ?>" aria-label="Максимальная цена">
					</div>
					<div class="catalog__range-values">
						<span class="catalog__range-from"><?php echo wp_kses_post( wc_price( $minka_price['min'] ) ); ?></span>
						<span class="catalog__range-to"><?php echo wp_kses_post( wc_price( $minka_price['max'] ) ); ?></span>
					</div>
					<p class="catalog__range-summary">От <?php echo wp_kses_post( wc_price( $minka_price['min'] ) ); ?> до <?php echo wp_kses_post( wc_price( $minka_price['max'] ) ); ?></p>
				</div>
			</div>

			<div class="catalog__filter">
				<button class="catalog__filter-btn" type="button" aria-expanded="false">Наличие</button>
				<div class="catalog__dropdown">
					<?php foreach ( minka_catalog_stock_options() as $minka_value => $minka_label ) : ?>
						<label class="catalog__option">
							<input class="catalog__checkbox" type="checkbox"
								data-filter-stock="<?php echo esc_attr( $minka_value ); ?>"
								<?php checked( in_array( $minka_value, $minka_stock, true ) ); ?>>
							<span class="catalog__box"></span>
							<span><?php echo esc_html( $minka_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<button class="catalog__clear<?php echo minka_catalog_has_active_filters() ? ' catalog__clear--visible' : ''; ?>" type="button" data-filters-clear>Очистить</button>
	</div>

	<button class="catalog__filters-toggle" type="button" data-filters-open>Фильтры<img class="catalog__sort-chevron catalog__sort-filters" src="<?php echo esc_url( minka_asset( 'assets/icons/filters.svg' ) ); ?>" alt="" width="10" height="10"></button>

	<div class="catalog__filter catalog__filter--sort">
		<button class="catalog__filter-btn catalog__sort" type="button" aria-expanded="false"><?php echo esc_html( $minka_sorts[ $minka_sort ] ); ?><img class="catalog__sort-chevron" src="<?php echo esc_url( minka_asset( 'assets/icons/dropdown-chevron.svg' ) ); ?>" alt="" width="10" height="9"></button>
		<div class="catalog__dropdown catalog__dropdown--sort">
			<?php foreach ( $minka_sorts as $minka_value => $minka_label ) : ?>
				<button class="catalog__sort-option<?php echo $minka_value === $minka_sort ? ' catalog__sort-option--active' : ''; ?>" type="button" data-sort="<?php echo esc_attr( $minka_value ); ?>"<?php echo $minka_value === $minka_sort ? ' aria-current="true"' : ''; ?> data-text="<?php echo esc_attr( $minka_label ); ?>"><?php echo esc_html( $minka_label ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>
</div>
