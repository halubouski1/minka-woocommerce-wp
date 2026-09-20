<?php
/**
 * Подвал: футер, общие попапы (шоурум / заявка) и cookie-баннер.
 */

defined( 'ABSPATH' ) || exit;

$minka_icons   = minka_asset( 'assets/icons' );
$minka_img     = minka_asset( 'assets/img' );
$minka_nav     = minka_footer_nav();
$minka_policy  = minka_footer_policy_links();
$minka_socials = minka_socials();

// На карточке товара в попапах показываем главное фото модели.
$minka_popup_image = minka_popup_image();
?>

<footer class="footer">
	<div class="container footer__container">
		<div class="footer__top">
			<nav class="footer__nav">
				<?php foreach ( $minka_nav as $minka_link ) : ?>
					<a class="footer__link" href="<?php echo esc_url( $minka_link['url'] ); ?>"<?php echo $minka_link['target'] ? ' target="' . esc_attr( $minka_link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $minka_link['title'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="footer__center">
				<div class="footer__socials">
					<?php foreach ( $minka_socials as $minka_social ) : ?>
						<a class="footer__social footer__social--<?php echo esc_attr( sanitize_title( $minka_social['label'] ) ); ?>" href="<?php echo esc_url( $minka_social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $minka_social['label'] ); ?>">
							<img src="<?php echo esc_url( $minka_social['icon']['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_social['icon']['width'] ); ?>" height="<?php echo esc_attr( $minka_social['icon']['height'] ); ?>">
						</a>
					<?php endforeach; ?>
				</div>
				<p class="footer__dev">Разработка сайта&nbsp;- <a href="https://webways.by" target="_blank" rel="noopener">webways.by</a></p>
			</div>

			<div class="footer__policy">
				<nav class="footer__policy-links">
					<?php foreach ( $minka_policy as $minka_link ) : ?>
						<a class="footer__link" href="<?php echo esc_url( $minka_link['url'] ); ?>"<?php echo $minka_link['target'] ? ' target="' . esc_attr( $minka_link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $minka_link['title'] ); ?></a>
					<?php endforeach; ?>

					<?php if ( minka_has_complianz() ) : ?>
						<button class="footer__link footer__link--button" type="button" data-consent-open>Настройки cookie</button>
					<?php endif; ?>
				</nav>
				<span class="footer__copyright">MINKA ©2026 copyright. Все права защищены.</span>
			</div>
		</div>

		<div class="footer__logo">
			<img src="<?php echo esc_url( $minka_icons . '/footer-logo.svg' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="1346" height="235">
		</div>
	</div>
</footer>

<div class="popup" data-popup-window="showroom" role="dialog" aria-modal="true" aria-label="Записаться в шоурум">
	<div class="popup__window">
		<div class="popup__image">
			<img src="<?php echo esc_url( $minka_popup_image['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_popup_image['width'] ); ?>" height="<?php echo esc_attr( $minka_popup_image['height'] ); ?>">
		</div>

		<div class="popup__body" data-lenis-prevent>
			<button class="popup__close" type="button" data-popup-close aria-label="Закрыть">
				<img src="<?php echo esc_url( $minka_icons . '/close.svg' ); ?>" alt="" width="18" height="18">
			</button>

			<h2 class="popup__title section-title">Записаться в шоурум</h2>
			<p class="popup__desc">Оставьте свои данные и мы свяжемся с вами для уточнения деталей</p>

			<form class="popup__form" data-minka-form="showroom">
				<div class="popup__inputs">
					<input class="popup__input" type="text" name="name" placeholder="Имя" aria-label="Имя">
					<input class="popup__input" type="text" name="visit" placeholder="Дата и время визита" aria-label="Дата и время визита" data-optional>
					<input class="popup__input" type="tel" name="phone" placeholder="Телефон" aria-label="Телефон">
					<input class="popup__input" type="email" name="email" placeholder="Email" aria-label="Email" data-optional>
					<input class="popup__input" type="text" name="comment" placeholder="Комментарий" aria-label="Комментарий" data-optional>
				</div>

				<span class="popup__label">Где с вами связаться?</span>
				<div class="popup__checks">
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="Телефон">
						<span class="popup__box"></span>
						<span>Телефон</span>
					</label>
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="WhatsApp">
						<span class="popup__box"></span>
						<span>WhatsApp</span>
					</label>
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="Вайбер">
						<span class="popup__box"></span>
						<span>Вайбер</span>
					</label>
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="Telegram">
						<span class="popup__box"></span>
						<span>Telegram</span>
					</label>
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

<div class="popup" data-popup-window="cta" role="dialog" aria-modal="true" aria-label="Оставить заявку">
	<div class="popup__window">
		<div class="popup__image">
			<img src="<?php echo esc_url( $minka_popup_image['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_popup_image['width'] ); ?>" height="<?php echo esc_attr( $minka_popup_image['height'] ); ?>">
		</div>

		<div class="popup__body" data-lenis-prevent>
			<button class="popup__close" type="button" data-popup-close aria-label="Закрыть">
				<img src="<?php echo esc_url( $minka_icons . '/close.svg' ); ?>" alt="" width="18" height="18">
			</button>

			<h2 class="popup__title section-title">Оставьте контакты</h2>
			<p class="popup__desc">Сообщим наличие размеров, актуальную цену и пришлём дополнительные фото.</p>

			<form class="popup__form" data-minka-form="cta">
				<div class="popup__inputs">
					<input class="popup__input" type="text" name="name" placeholder="Имя" aria-label="Имя">
					<input class="popup__input" type="tel" name="phone" placeholder="Телефон" aria-label="Телефон">
					<input class="popup__input" type="email" name="email" placeholder="Email" aria-label="Email" data-optional>
					<input class="popup__input" type="text" name="comment" placeholder="Комментарий" aria-label="Комментарий" data-optional>
				</div>

				<span class="popup__label">Где с вами связаться?</span>
				<div class="popup__checks">
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="Телефон">
						<span class="popup__box"></span>
						<span>Телефон</span>
					</label>
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="WhatsApp">
						<span class="popup__box"></span>
						<span>WhatsApp</span>
					</label>
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="Вайбер">
						<span class="popup__box"></span>
						<span>Вайбер</span>
					</label>
					<label class="popup__check">
						<input class="popup__check-input" type="radio" name="contact" value="Telegram">
						<span class="popup__box"></span>
						<span>Telegram</span>
					</label>
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

<div class="cookie" id="cookie">
	<div class="cookie__window">
		<h2 class="cookie__title section-title">Мы используем файлы cookie</h2>
		<p class="cookie__desc">Эти файлы необходимы для корректной работы сайта. Без них сайт не будет функционировать должным образом. Они не собирают личные данные.</p>
		<div class="cookie__actions">
			<button class="hero__catalog buy__catalog cookie__accept" type="button" data-cookie-close>Принять</button>
		</div>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
