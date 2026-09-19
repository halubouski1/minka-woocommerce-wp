<?php
/**
 * Попап «Намекните близкому о подарке» — нужен там, где есть кнопка подарка.
 */

defined( 'ABSPATH' ) || exit;

// На карточке товара — фото самой модели, иначе картинка из вёрстки.
$minka_gift_image = minka_popup_image( 'assets/img/popular-card-1.webp' );
?>
<div class="popup popup__gift" data-popup-window="gift" role="dialog" aria-modal="true" aria-label="Намекните близкому о подарке">
	<div class="popup__window">
		<div class="popup__image">
			<img src="<?php echo esc_url( $minka_gift_image['url'] ); ?>" alt="" width="<?php echo esc_attr( $minka_gift_image['width'] ); ?>" height="<?php echo esc_attr( $minka_gift_image['height'] ); ?>">
		</div>

		<div class="popup__body" data-lenis-prevent>
			<button class="popup__close" type="button" data-popup-close aria-label="Закрыть">
				<img src="<?php echo esc_url( minka_asset( 'assets/icons/close.svg' ) ); ?>" alt="" width="18" height="18">
			</button>

			<h2 class="popup__title section-title">Намекните близкому о подарке</h2>

			<div class="popup__gift-text">
				<p>Здравствуйте, <span class="popup__fill popup__fill--wide" data-fill-target="recipient"></span> !</p>
				<p>Мы узнали, что <span class="popup__fill" data-fill-target="sender"></span> мечтает о подарке <br> из салона норковых шуб «MINKA». Мы внимательно относимся к желаниям наших клиентов и решили намекнуть вам об этом.</p>
				<p>Дарите тепло и будьте внимательны к мечтам близких! Если у вас возникли вопросы или вы хотите обсудить детали, мы всегда готовы помочь.</p>
			</div>

			<form class="popup__form popup__form--gift" data-minka-form="gift">
				<input type="hidden" name="product" value="<?php echo esc_attr( get_the_title() ); ?>">
				<span class="popup__label">Кому намекнуть</span>
				<div class="popup__inputs">
					<input class="popup__input" type="text" name="recipient_name" placeholder="Имя" aria-label="Имя" data-fill-source="recipient">
					<input class="popup__input" type="email" name="recipient_email" placeholder="Эл. почта" aria-label="Эл. почта">
				</div>

				<span class="popup__label">От кого</span>
				<div class="popup__inputs">
					<input class="popup__input" type="text" name="sender_name" placeholder="Имя" aria-label="Имя" data-fill-source="sender">
					<input class="popup__input" type="email" name="sender_email" placeholder="Эл. почта" aria-label="Эл. почта">
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
		<span class="popup__thanks-lead">Ваш намёк успешно отправлен.</span>
		<p class="popup__thanks-text">Мы бережно передадим его вашему близкому.</p>
		<button class="hero__catalog buy__catalog" type="button" data-popup-close>Хорошо</button>
	</div>
</div>
