<?php
/**
 * Фильтры каталога — выезжающая панель для мобильных (≤1024).
 */

defined( 'ABSPATH' ) || exit;

$minka_price = minka_catalog_price_range();
$minka_stock = minka_catalog_chosen_stock();
$minka_chev  = minka_asset( 'assets/icons/dropdown-chevron.svg' );
?>
<div class="filters-overlay" data-filters-close></div>
<div class="filters" id="filters" data-lenis-prevent role="dialog" aria-modal="true" aria-label="Фильтры">
	<div class="filters__inner">
		<div class="filters__content">
			<button class="filters__close" type="button" data-filters-close aria-label="Закрыть фильтры">
				<img src="<?php echo esc_url( minka_asset( 'assets/icons/close.svg' ) ); ?>" alt="" width="18" height="18">
			</button>
			<a class="filters__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( minka_asset( 'assets/icons/logo-dark.svg' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="261" height="47">
			</a>
			<span class="filters__label">Фильтры</span>

			<div class="filters__list">
				<?php
				foreach ( minka_catalog_filter_groups() as $minka_group ) :
					$minka_terms = minka_catalog_terms( $minka_group['taxonomy'] );

					if ( ! $minka_terms ) {
						continue;
					}

					$minka_chosen = minka_catalog_chosen_terms( $minka_group['taxonomy'] );
					?>
					<div class="filters-acc">
						<button class="filters-acc__btn" type="button" aria-expanded="false"><?php echo esc_html( $minka_group['label'] ); ?><img class="filters-acc__chevron" src="<?php echo esc_url( $minka_chev ); ?>" alt="" width="10" height="9"></button>
						<div class="filters-acc__body">
							<div class="filters-acc__inner">
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
					</div>
				<?php endforeach; ?>

				<div class="filters-acc filters-acc--price">
					<button class="filters-acc__btn" type="button" aria-expanded="false">Цена<img class="filters-acc__chevron" src="<?php echo esc_url( $minka_chev ); ?>" alt="" width="10" height="9"></button>
					<div class="filters-acc__body">
						<div class="filters-acc__inner">
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
				</div>

				<div class="filters-acc">
					<button class="filters-acc__btn" type="button" aria-expanded="false">Наличие<img class="filters-acc__chevron" src="<?php echo esc_url( $minka_chev ); ?>" alt="" width="10" height="9"></button>
					<div class="filters-acc__body">
						<div class="filters-acc__inner">
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
			</div>
		</div>

		<div class="filters__actions">
			<button class="filters__clear" type="button" data-filters-clear>Очистить</button>
			<button class="hero__catalog buy__catalog filters__apply" type="button" data-filters-apply data-filters-close>Применить</button>
		</div>
	</div>
</div>
