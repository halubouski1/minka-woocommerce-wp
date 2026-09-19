<?php
/**
 * Блок «Вопросы и ответы».
 *
 * Ждёт список в $args['items'] — по паре question/answer.
 */

defined( 'ABSPATH' ) || exit;

$minka_items = isset( $args['items'] ) ? $args['items'] : array();

if ( ! $minka_items ) {
	return;
}
?>
<section class="faq section-padding">
	<div class="container">
		<ul class="faq__list">
			<?php
			$minka_delay = 100;
			foreach ( $minka_items as $minka_item ) :
				?>
				<li class="faq__item" data-aos="fade-up" data-aos-delay="<?php echo esc_attr( $minka_delay ); ?>">
					<h3 class="faq__question section-title">
						<button class="faq__header" type="button" aria-expanded="false">
							<span class="faq__question section-title"><?php echo esc_html( $minka_item['question'] ); ?></span>
							<img class="faq__chevron" src="<?php echo esc_url( minka_asset( 'assets/icons/chevron.svg' ) ); ?>" alt="" width="28" height="16">
						</button>
					</h3>
					<div class="faq__answer">
						<div class="faq__answer-inner">
							<p class="faq__text"><?php echo esc_html( $minka_item['answer'] ); ?></p>
						</div>
					</div>
				</li>
				<?php
				$minka_delay += 100;
			endforeach;
			?>
		</ul>
	</div>
</section>
