<?php
/**
 * Карточка товара.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$minka_product = wc_get_product( get_the_ID() );

	if ( ! $minka_product ) {
		continue;
	}

	$minka_prices    = minka_product_prices( $minka_product );
	$minka_price     = $minka_prices['current'];
	$minka_price_old = $minka_prices['old'];

	$minka_gallery = minka_product_gallery( $minka_product );
	$minka_specs   = minka_product_specs( $minka_product );
	$minka_related = minka_related_products( $minka_product );
	$minka_desc    = minka_product_description( $minka_product );
	?>

<main id="main-content">
	<div class="catalog" data-aos="fade-up">
		<div class="container">
			<nav class="catalog__breadcrumbs" aria-label="Навигационная цепочка">
				<a class="catalog__crumb" href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
				<span class="catalog__sep">/</span>
				<a class="catalog__crumb" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">Каталог</a>
				<span class="catalog__sep">/</span>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php the_title(); ?></span>
			</nav>
		</div>
	</div>

	<section class="single" data-aos="fade-up">
		<div class="single__layout container">
			<div class="single__gallery">
				<div class="single__slider">
					<div class="single__gallery-list">
						<?php foreach ( $minka_gallery as $minka_index => $minka_image ) : ?>
							<?php
							// Каждое фото получает своё описание: пустой alt делает
							// картинку декоративной, а в галерее это содержимое.
							// Что записано у файла в медиатеке — важнее нашей заготовки.
							$minka_image_alt = $minka_index
								? sprintf( '%s — фото %d', get_the_title(), $minka_index + 1 )
								: get_the_title();

							if ( ! empty( $minka_image['alt'] ) ) {
								$minka_image_alt = $minka_image['alt'];
							}
							?>
							<img class="single__img" src="<?php echo esc_url( $minka_image['url'] ); ?>" alt="<?php echo esc_attr( $minka_image_alt ); ?>" width="<?php echo esc_attr( $minka_image['width'] ); ?>" height="<?php echo esc_attr( $minka_image['height'] ); ?>"<?php echo $minka_index ? ' loading="lazy"' : ''; ?>>
						<?php endforeach; ?>
					</div>
					<button class="single__arrow single__arrow--prev" type="button" aria-label="Предыдущее фото">
						<img src="<?php echo esc_url( minka_asset( 'assets/icons/slider-arrow.svg' ) ); ?>" alt="" width="10" height="16">
					</button>
					<button class="single__arrow single__arrow--next" type="button" aria-label="Следующее фото">
						<img src="<?php echo esc_url( minka_asset( 'assets/icons/slider-arrow.svg' ) ); ?>" alt="" width="10" height="16">
					</button>
				</div>
				<div class="single__pagination"></div>
			</div>

			<div class="single__info">
				<div class="single__head">
					<?php if ( $minka_product->get_sku() ) : ?>
						<span class="single__art"><?php echo esc_html( $minka_product->get_sku() ); ?></span>
					<?php endif; ?>

					<div class="single__title-row">
						<h1 class="single__title section-title"><?php the_title(); ?></h1>
						<div class="single__icons">
							<button class="single__icon" type="button" aria-label="В подарок" data-popup="gift">
								<img src="<?php echo esc_url( minka_asset( 'assets/icons/gift.svg' ) ); ?>" alt="" width="24" height="24">
							</button>
							<button class="single__icon single__fav" type="button" aria-label="В избранное" data-product-id="<?php echo esc_attr( $minka_product->get_id() ); ?>">
								<img src="<?php echo esc_url( minka_asset( 'assets/icons/favorite.svg' ) ); ?>" alt="" width="24" height="24">
							</button>
						</div>
					</div>

					<?php if ( $minka_price ) : ?>
						<p class="single__price">
							<?php echo wp_kses_post( $minka_price ); ?>
							<?php if ( $minka_price_old ) : ?>
								<span class="single__price-old"><?php echo wp_kses_post( $minka_price_old ); ?></span>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<button class="single__availability hero__catalog buy__catalog" type="button" data-popup="cta">Узнать наличие</button>
					<button class="hero__availability popular__link single__fitting" data-popup="showroom" type="button">Заказать примерку</button>
				</div>

				<?php if ( $minka_specs ) : ?>
					<div class="single__specs">
						<?php foreach ( $minka_specs as $minka_spec ) : ?>
							<div class="single__spec">
								<span class="single__spec-label"><?php echo esc_html( $minka_spec['label'] ); ?></span>
								<span class="single__spec-value"><?php echo esc_html( $minka_spec['value'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php foreach ( $minka_desc as $minka_paragraph ) : ?>
					<p class="single__desc"><?php echo wp_kses_post( $minka_paragraph ); ?></p>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( $minka_related ) : ?>
		<section class="popular popular--slider section-padding" data-aos="fade-up">
			<div class="container">
				<div class="popular__top">
					<h2 class="popular__title section-title">Похожие модели</h2>
					<a class="hero__availability popular__link" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">Смотреть каталог</a>
					<div class="popular__nav">
						<button class="popular__arrow popular__arrow--prev" type="button" aria-label="Предыдущий слайд">
							<img src="<?php echo esc_url( minka_asset( 'assets/icons/slider-arrow.svg' ) ); ?>" alt="" width="10" height="16">
						</button>
						<button class="popular__arrow popular__arrow--next" type="button" aria-label="Следующий слайд">
							<img src="<?php echo esc_url( minka_asset( 'assets/icons/slider-arrow.svg' ) ); ?>" alt="" width="10" height="16">
						</button>
					</div>
				</div>

				<div class="popular__slider">
					<div class="popular__list">
						<?php foreach ( $minka_related as $minka_item ) : ?>
							<?php get_template_part( 'template-parts/card', 'product', array( 'product' => $minka_item ) ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="popular__pagination"></div>

				<a class="popular__catalog hero__catalog buy__catalog" href="<?php echo esc_url( minka_catalog_base_url() ); ?>">Смотреть каталог</a>
			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_product_faq_items() ) ); ?>
</main>

<?php get_template_part( 'template-parts/popup', 'gift' ); ?>

	<?php
endwhile;

get_footer();
