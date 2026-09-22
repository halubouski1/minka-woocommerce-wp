<?php
/**
 * Карточка товара в сетке и слайдерах.
 *
 * Ждёт в $args['product'] объект WC_Product. Без него рисует заглушку
 * из вёрстки — так слайдеры на страницах без товаров не ломаются.
 */

defined( 'ABSPATH' ) || exit;

$minka_product = isset( $args['product'] ) ? $args['product'] : null;

if ( $minka_product instanceof WC_Product ) {
	$minka_link  = get_permalink( $minka_product->get_id() );
	$minka_title = $minka_product->get_name();

	$minka_prices    = minka_product_prices( $minka_product );
	$minka_price     = $minka_prices['current'];
	$minka_price_old = $minka_prices['old'];

	// full — это сам загруженный файл. Уменьшенные копии WooCommerce (600px)
	// на ретине и в крупной карточке выглядят мягче оригинала.
	$minka_main = wp_get_attachment_image_src( $minka_product->get_image_id(), 'full' );
	$minka_main = $minka_main ? $minka_main[0] : wc_placeholder_img_src( 'woocommerce_single' );

	$minka_gallery = $minka_product->get_gallery_image_ids();
	$minka_hover   = $minka_gallery ? wp_get_attachment_image_src( $minka_gallery[0], 'full' ) : null;
	$minka_hover   = $minka_hover ? $minka_hover[0] : '';
	$minka_id      = $minka_product->get_id();

	// Данные для событий электронной торговли GA4: их читает main.js при клике
	// по карточке и при показе списка.
	$minka_item = function_exists( 'minka_ecommerce_item' ) ? minka_ecommerce_item( $minka_product ) : array();

	// Описания фотографий: своё из медиатеки, иначе — по названию модели.
	$minka_main_alt  = minka_attachment_alt( $minka_product->get_image_id(), $minka_title );
	$minka_hover_alt = $minka_gallery
		? minka_attachment_alt( $minka_gallery[0], sprintf( '%s — фото 2', $minka_title ) )
		: '';
} else {
	$minka_link      = minka_page_url( 'catalog' );
	$minka_title     = 'Модель шубы';
	$minka_price     = '$ 1200';
	$minka_price_old = '';
	$minka_main  = minka_asset( 'assets/img/popular-card-1.webp' );
	$minka_hover = minka_asset( 'assets/img/review-card-1.webp' );
	$minka_id    = 0;

	$minka_main_alt  = $minka_title;
	$minka_hover_alt = sprintf( '%s — фото 2', $minka_title );
	$minka_item      = array();
}
?>
<div class="popular-card"<?php echo $minka_id ? ' data-product-id="' . esc_attr( $minka_id ) . '"' : ''; ?><?php
	foreach ( $minka_item as $minka_key => $minka_value ) {
		printf( ' data-%s="%s"', esc_attr( str_replace( '_', '-', $minka_key ) ), esc_attr( $minka_value ) );
	}
?>>
	<button class="popular-card__fav" type="button" aria-label="В избранное">
		<img src="<?php echo esc_url( minka_asset( 'assets/icons/favorite.svg' ) ); ?>" alt="" width="24" height="24">
	</button>
	<a class="popular-card__link" href="<?php echo esc_url( $minka_link ); ?>">
		<span class="popular-card__figure">
		<img class="popular-card__img" src="<?php echo esc_url( $minka_main ); ?>" alt="<?php echo esc_attr( $minka_main_alt ); ?>" width="452" height="535" loading="lazy">
		<?php if ( $minka_hover ) : ?>
			<?php
			// Второе фото — та же модель с другого ракурса. alt нужен поисковикам,
			// а aria-hidden оставляем: для читалки это повтор уже озвученной
			// картинки внутри той же ссылки.
			?>
			<img class="popular-card__img popular-card__img--hover" src="<?php echo esc_url( $minka_hover ); ?>" alt="<?php echo esc_attr( $minka_hover_alt ); ?>" width="452" height="535" aria-hidden="true" loading="lazy">
		<?php endif; ?>
		</span>
		<p class="popular-card__title"><?php echo esc_html( $minka_title ); ?></p>
		<p class="popular-card__price">
			<?php echo wp_kses_post( $minka_price ); ?>
			<?php if ( $minka_price_old ) : ?>
				<span class="popular-card__price-old"><?php echo wp_kses_post( $minka_price_old ); ?></span>
			<?php endif; ?>
		</p>
	</a>
</div>
