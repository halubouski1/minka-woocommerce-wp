<?php
/**
 * Template Name: Контакты
 */

defined( 'ABSPATH' ) || exit;

get_header();

$minka_intro    = minka_contacts_intro();
$minka_details  = minka_contacts_details();
$minka_socials  = minka_contacts_socials();
$minka_showroom = minka_contacts_showroom();
$minka_form     = minka_contacts_form();
?>

<main id="main-content">
	<section class="catalog" data-aos="fade-up">
		<div class="container">
			<nav class="catalog__breadcrumbs" aria-label="Навигационная цепочка">
				<a class="catalog__crumb" href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
				<span class="catalog__sep">/</span>
				<span class="catalog__crumb catalog__crumb--current" aria-current="page"><?php the_title(); ?></span>
			</nav>

			<h1 class="catalog__title section-title"><?php the_title(); ?></h1>

			<div class="contacts">
				<?php if ( $minka_intro['image2'] ) : ?>
					<img class="contacts__image contacts__image--end" src="<?php echo esc_url( $minka_intro['image2']['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_intro['image2']['width'] ); ?>" height="<?php echo esc_attr( $minka_intro['image2']['height'] ); ?>">
				<?php endif; ?>
				<?php if ( $minka_intro['image'] ) : ?>
					<img class="contacts__image" src="<?php echo esc_url( $minka_intro['image']['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_intro['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_intro['image']['height'] ); ?>">
				<?php endif; ?>

				<div class="contacts__right">
					<h2 class="contacts__title section-title"><?php echo esc_html( $minka_intro['title'] ); ?></h2>
					<p class="contacts__desc"><?php echo wp_kses_post( $minka_intro['desc'] ); ?></p>

					<div class="contacts__details">
						<?php if ( $minka_details['phone_label'] ) : ?>
							<div class="contacts__row">
								<a class="contacts__value" href="<?php echo esc_attr( $minka_details['phone_href'] ); ?>"><?php echo esc_html( $minka_details['phone_label'] ); ?></a>
								<button class="hero__availability popular__link contacts__copy" type="button" aria-label="Скопировать номер телефона">Скопировать</button>
							</div>
						<?php endif; ?>

						<?php if ( $minka_details['email'] ) : ?>
							<div class="contacts__row">
								<a class="contacts__value" href="mailto:<?php echo esc_attr( $minka_details['email'] ); ?>"><?php echo esc_html( $minka_details['email'] ); ?></a>
								<button class="hero__availability popular__link contacts__copy" type="button" aria-label="Скопировать адрес электронной почты">Скопировать</button>
							</div>
						<?php endif; ?>

						<?php if ( $minka_socials ) : ?>
							<div class="contacts__socials">
								<?php foreach ( $minka_socials as $minka_social ) : ?>
									<a class="contacts__social hero__catalog buy__catalog" href="<?php echo esc_url( $minka_social['url'] ); ?>" target="_blank" rel="noopener">
										<img src="<?php echo esc_url( $minka_social['icon']['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_social['icon']['width'] ); ?>" height="<?php echo esc_attr( $minka_social['icon']['height'] ); ?>">
										<span><?php echo esc_html( $minka_social['label'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="showroom">
		<div class="container showroom__container" data-aos="fade-up" data-aos-delay="100">
			<h2 class="showroom__title section-title"><?php echo esc_html( $minka_showroom['title'] ); ?></h2>
			<p class="showroom__desc"><?php echo wp_kses_post( $minka_showroom['desc'] ); ?></p>
			<button class="hero__catalog showroom__btn" type="button" data-popup="showroom"><?php echo esc_html( $minka_showroom['button'] ); ?></button>
		</div>
	</section>

	<section class="contact-form">
		<div class="container">
			<div class="popup popup--inline">
				<div class="popup__window">
					<?php if ( $minka_form['image'] ) : ?>
						<div class="popup__image">
							<img src="<?php echo esc_url( $minka_form['image']['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_form['image']['width'] ); ?>" height="<?php echo esc_attr( $minka_form['image']['height'] ); ?>">
						</div>
					<?php endif; ?>

					<div class="popup__body">
						<h2 class="popup__title section-title"><?php echo esc_html( $minka_form['title'] ); ?></h2>
						<p class="popup__desc"><?php echo wp_kses_post( $minka_form['desc'] ); ?></p>

						<form class="popup__form" data-minka-form="contacts">
							<div class="popup__inputs">
								<input class="popup__input" type="text" name="name" placeholder="Имя" aria-label="Имя">
								<input class="popup__input" type="tel" name="phone" placeholder="Телефон" aria-label="Телефон">
								<input class="popup__input" type="email" name="email" placeholder="Email" aria-label="Email" data-optional>
								<input class="popup__input" type="text" name="question" placeholder="Вопрос" aria-label="Вопрос">
							</div>

							<span class="popup__label">Где с вами связаться?</span>
							<div class="popup__checks">
								<?php foreach ( array( 'Телефон', 'WhatsApp', 'Вайбер', 'Telegram' ) as $minka_way ) : ?>
									<label class="popup__check">
										<input class="popup__check-input" type="radio" name="contact" value="<?php echo esc_attr( $minka_way ); ?>">
										<span class="popup__box"></span>
										<span><?php echo esc_html( $minka_way ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>

							<label class="popup__check popup__agree">
								<input class="popup__check-input" type="checkbox" name="agree">
								<span class="popup__box"></span>
								<span>Я соглашаюсь с <a href="<?php echo esc_url( minka_page_url( 'privacy' ) ); ?>">обработкой персональных данных</a></span>
							</label>

							<button class="hero__catalog buy__catalog popup__submit" type="submit">Отправить заявку</button>
						</form>
					</div>
				</div>

				<div class="popup__thanks" role="status" tabindex="-1">
					<h2 class="popup__thanks-title section-title">Спасибо!</h2>
					<span class="popup__thanks-lead">Ваша заявка успешно отправлена.</span>
					<p class="popup__thanks-text">Мы свяжемся с вами в ближайшее время и уточним все детали.</p>
					<button class="hero__catalog buy__catalog" type="button" data-popup-close>Хорошо</button>
				</div>
			</div>
		</div>
	</section>

	<?php get_template_part( 'template-parts/faq', null, array( 'items' => minka_contacts_faq_items() ) ); ?>
</main>

<?php
get_footer();
