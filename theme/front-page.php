<?php
/**
 * Главная страница.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$minka_icons = minka_asset( 'assets/icons' );
$minka_img   = minka_asset( 'assets/img' );
?>

<main id="main-content">
	<section class="hero">
		<div class="container hero__container">
			<div class="hero__content" data-aos="fade-up">
				<div class="hero__text">
					<h1 class="hero__title section-title">Норковая шуба MINKA&nbsp;&mdash; комфорт, тепло и статус</h1>
					<p class="hero__desc">
						Только натуральный мех норки. <br>
						Прямые поставки, прозрачное ценообразование.
						Персональная примерка перед приобретением.
					</p>
				</div>

				<div class="hero__actions">
					<button class="hero__availability" data-popup="cta" type="button">Узнать наличие</button>
					<a class="hero__catalog" href="<?php echo esc_url( minka_page_url( 'catalog' ) ); ?>">Смотреть каталог</a>
				</div>
			</div>
		</div>
	</section>

	<section class="benefits">
		<div class="container benefits__container">
			<div class="benefits__left">
				<div class="benefits__list">
					<div class="benefits__item" data-aos="fade-up" data-aos-delay="0">
						<h2 class="benefits__title section-title">Натуральная норка</h2>
						<p class="benefits__text">Натуральный мех норки из аукционных шкур высшей селекции. Фабричные изделия от доверенного поставщика. Гарантия производителя, фурнитура премиального класса. Примерка перед покупкой.</p>
					</div>
					<div class="benefits__item" data-aos="fade-up" data-aos-delay="100">
						<span class="benefits__title section-title">Примерка перед покупкой</span>
						<p class="benefits__text">В нашем шоуруме, в обстановке приватного комфорта, <br> Вы можете примерить каждую заинтересовавшую модель.</p>
					</div>
					<div class="benefits__item" data-aos="fade-up" data-aos-delay="200">
						<span class="benefits__title section-title">Гарантия</span>
						<p class="benefits__text">Гарантия 12 месяцев</p>
					</div>
				</div>

				<p class="benefits__note">Приобретение шубы&nbsp;&mdash; серьёзная инвестиция<br> и важное событие. Вопросы при выборе естественны. Консультант шоурума, опираясь на многолетний опыт, поможет развеять сомнения и даст полезные рекомендации для покупки.</p>
			</div>

			<div class="benefits__right">
				<img src="<?php echo esc_url( $minka_img . '/benefits.webp' ); ?>" alt="Девушка в норковой шубе читает газету" width="914" height="981">
			</div>
		</div>
	</section>

	<section class="popular popular--slider section-padding" data-aos="fade-up">
		<div class="container">
			<div class="popular__top">
				<h2 class="popular__title section-title">Популярные модели</h2>
				<a class="hero__availability popular__link" href="<?php echo esc_url( minka_page_url( 'catalog' ) ); ?>">Смотреть каталог</a>
				<div class="popular__nav">
					<button class="popular__arrow popular__arrow--prev" type="button" aria-label="Предыдущий слайд">
						<img src="<?php echo esc_url( $minka_icons . '/slider-arrow.svg' ); ?>" alt="" width="10" height="16">
					</button>
					<button class="popular__arrow popular__arrow--next" type="button" aria-label="Следующий слайд">
						<img src="<?php echo esc_url( $minka_icons . '/slider-arrow.svg' ); ?>" alt="" width="10" height="16">
					</button>
				</div>
			</div>

			<div class="popular__slider">
				<div class="popular__list">
					<?php foreach ( minka_chosen_products( 'home_popular' ) as $minka_popular ) : ?>
						<?php get_template_part( 'template-parts/card', 'product', array( 'product' => $minka_popular ) ); ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="popular__pagination"></div>

			<a class="popular__catalog hero__catalog buy__catalog" href="<?php echo esc_url( minka_page_url( 'catalog' ) ); ?>">Смотреть каталог</a>
		</div>
	</section>

	<section class="material section-padding" data-aos="fade-up" data-aos-delay="100">
		<div class="container material__container">
			<div class="material__image">
				<img src="<?php echo esc_url( $minka_img . '/material.webp' ); ?>" alt="Мех норки крупным планом" width="605" height="797">
			</div>

			<div class="material__content">
				<h2 class="material__title section-title">Почему именно норка</h2>

				<div class="material__text">
					<p>Помимо стиля, норковая шуба обладает и практическими качествами.</p>
					<p>Она тёплая, уютная и лёгкая. В такой шубе комфортно даже в сильные морозы, и к концу дня вы не чувствуете тяжести.</p>
					<p>Цветовая гамма натурального меха разнообразна: оттенок зависит от вида животного. В наличии&nbsp;&mdash; рыжий, ореховый, бежевый, белый, чёрный, голубой, серый. Палитра окрашенного меха практически безгранична.</p>
					<p>Норке не страшны дождь и снег. Достаточно высушить мех естественным путём&nbsp;&mdash; без фена, батарей и обогревателей&nbsp;&mdash; затем слегка встряхнуть, и каждая ворсинка вернётся на место, возвращая блеск.</p>
					<p>Мездра мягкая, гибкая и эластичная. Это позволяет создавать сложные фасоны и силуэты без риска повредить шкурку.</p>
					<p>Норковая шуба служит десятилетиями. Даже когда модель устареет, её легко обновить&nbsp;&mdash; норка хорошо переносит перекрой и перешив.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="buy">
		<div class="container buy__container" data-aos="fade-up" data-aos-delay="100">
			<h2 class="buy__title section-title">Как приобрести шубу у нас</h2>

			<div class="buy__steps">
				<div class="buy__step">
					<span class="buy__number">01</span>
					<p class="buy__text">Выберите модель в каталоге или напишите нам&nbsp;&mdash; подберём под вас.</p>
				</div>
				<div class="buy__step">
					<span class="buy__number">02</span>
					<p class="buy__text">Оставьте заявку «Узнать наличие»&nbsp;&mdash; <br> сообщим цену и размеры.</p>
				</div>
				<div class="buy__step">
					<span class="buy__number">03</span>
					<p class="buy__text">Примерьте и выберите наиболее подходящий вариант для вас в шоуруме</p>
				</div>
			</div>

			<a class="hero__catalog buy__catalog" href="<?php echo esc_url( minka_page_url( 'catalog' ) ); ?>">Смотреть каталог</a>
		</div>
	</section>

	<section class="reviews section-padding" data-aos="fade-up" data-aos-delay="100">
		<div class="container">
			<div class="reviews__top">
				<h2 class="reviews__title section-title">О наших шубах говорят</h2>
				<div class="reviews__nav">
					<button class="reviews__arrow reviews__arrow--prev" type="button" aria-label="Предыдущий слайд">
						<img src="<?php echo esc_url( $minka_icons . '/slider-arrow.svg' ); ?>" alt="" width="10" height="16">
					</button>
					<button class="reviews__arrow reviews__arrow--next" type="button" aria-label="Следующий слайд">
						<img src="<?php echo esc_url( $minka_icons . '/slider-arrow.svg' ); ?>" alt="" width="10" height="16">
					</button>
				</div>
			</div>

			<div class="reviews__slider">
				<div class="reviews__list">
					<?php foreach ( minka_reviews() as $minka_review ) : ?>
						<?php if ( 'image' === $minka_review['type'] ) : ?>
							<div class="review-card review-card--image">
								<?php if ( $minka_review['image'] ) : ?>
									<img class="review-card__img" src="<?php echo esc_url( $minka_review['image']['url'] ); ?>" alt="<?php echo esc_attr( $minka_review['image']['alt'] ); ?>" width="<?php echo esc_attr( $minka_review['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_review['image']['height'] ); ?>">
								<?php endif; ?>
								<span class="review-card__name"><?php echo esc_html( $minka_review['name'] ); ?></span>
								<p class="review-card__text"><?php echo esc_html( $minka_review['text'] ); ?></p>
							</div>

						<?php elseif ( 'double' === $minka_review['type'] ) : ?>
							<div class="review-card review-card--double">
								<?php foreach ( $minka_review['blocks'] as $minka_block ) : ?>
									<div class="review-card__block">
										<span class="review-card__name"><?php echo esc_html( $minka_block['name'] ); ?></span>
										<p class="review-card__text"><?php echo esc_html( $minka_block['text'] ); ?></p>
									</div>
								<?php endforeach; ?>
							</div>

						<?php elseif ( 'cta' === $minka_review['type'] ) : ?>
							<div class="review-card review-card--last">
								<span class="review-card__name"><?php echo esc_html( $minka_review['name'] ); ?></span>
								<p class="review-card__text review-card__text--last"><?php echo esc_html( $minka_review['text'] ); ?></p>
								<a class="hero__catalog buy__catalog" href="<?php echo esc_url( $minka_review['link']['url'] ); ?>"><?php echo esc_html( $minka_review['link']['title'] ); ?></a>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="reviews__pagination"></div>
		</div>
	</section>

	<section class="showroom">
		<div class="container showroom__container" data-aos="fade-up" data-aos-delay="100">
			<h2 class="showroom__title section-title">Шоурум</h2>
			<p class="showroom__desc">Приезжайте на примерку&nbsp;&mdash; покажем модели вживую, поможем с размером и фасоном. Запись по телефону обязательна.</p>
			<button class="hero__catalog showroom__btn" type="button" data-popup="showroom">Записаться</button>
		</div>
	</section>

	<?php $minka_faq = minka_faq_items(); ?>
	<?php get_template_part( 'template-parts/faq', null, array( 'items' => $minka_faq ) ); ?>

	<section class="cta" data-aos="fade-up">
		<div class="container cta__container">
			<h2 class="cta__title section-title">Не нашли свою модель?</h2>
			<p class="cta__desc">Опишите, что ищете&nbsp;&mdash; подберём норковую шубу под ваш размер, бюджет и фасон</p>
			<button class="hero__catalog buy__catalog cta__btn" type="button" data-popup="cta">Оставить заявку</button>
		</div>
	</section>
</main>

<?php
// Микроразметка FAQ переехала в inc/schema.php: там она встраивается в граф
// Yoast и работает на всех страницах с блоком вопросов, а не только здесь.
get_footer();
