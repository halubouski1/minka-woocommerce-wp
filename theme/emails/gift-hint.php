<?php
/**
 * Письмо «Вам намекнули о подарке».
 *
 * Вёрстка письма отличается от вёрстки сайта: почтовые клиенты (особенно
 * Outlook с движком Word) не понимают flex, grid и внешние стили, поэтому
 * здесь таблицы, инлайновые стили и только безопасные шрифты.
 *
 * Логотипы набраны текстом, а не картинкой: почтовые клиенты по умолчанию
 * блокируют изображения, и текстовый логотип виден всегда.
 *
 * Ожидает:
 * @var string $recipient  Имя получателя.
 * @var string $sender     Имя того, кто намекает.
 * @var array  $product    url, image, title, sku — может быть пустым.
 * @var string $home       Адрес сайта.
 * @var string $preheader  Короткий текст для списка писем.
 * @var array  $contacts   socials, phone, phone_label, email.
 */

defined( 'ABSPATH' ) || exit;

$text  = '#000000';
$muted = '#777777';

// Те же гарнитуры, что на сайте: Cormorant Garamond в заголовках и логотипе,
// Lato в тексте. Запасные шрифты подобраны по рисунку и ширине очка: в Gmail
// и Outlook веб-шрифты не загружаются, и письмо остаётся в той же пропорции.
$serif = "'Cormorant Garamond', Georgia, 'Times New Roman', Times, serif";
$sans  = "'Lato', Arial, 'Helvetica Neue', Helvetica, sans-serif";
$fonts  = get_template_directory_uri() . '/assets/fonts';
$images = get_template_directory_uri() . '/assets/img/email';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="x-apple-disable-message-reformatting">
	<!-- Письмо свёрстано только в светлой схеме. Без этих двух строк Apple Mail
	     и Outlook при включённой тёмной теме инвертируют цвета сами: фон
	     становится чёрным, текст белым, и вёрстка разваливается. -->
	<meta name="color-scheme" content="light">
	<meta name="supported-color-schemes" content="light">
	<title><?php echo esc_html( $preheader ); ?></title>
	<!--[if mso]>
	<style type="text/css">
		body, table, td, span, p { font-family: Arial, sans-serif !important; }
		h1, .minka-model { font-family: Georgia, serif !important; }
	</style>
	<![endif]-->
	<style type="text/css">
		:root { color-scheme: light; supported-color-schemes: light; }

		/* Шрифты сайта. Работают в Apple Mail, iOS, Thunderbird и Samsung Mail;
		   Gmail и Outlook их игнорируют и берут запасные из font-family. */
		@font-face {
			font-family: 'Cormorant Garamond';
			src: url('<?php echo esc_url( $fonts ); ?>/CormorantGaramond-Regular.woff2') format('woff2'),
			     url('<?php echo esc_url( $fonts ); ?>/CormorantGaramond-Regular.woff') format('woff');
			font-weight: 400;
			font-style: normal;
		}

		@font-face {
			font-family: 'Lato';
			src: url('<?php echo esc_url( $fonts ); ?>/Lato-Regular.woff2') format('woff2'),
			     url('<?php echo esc_url( $fonts ); ?>/Lato-Regular.woff') format('woff');
			font-weight: 400;
			font-style: normal;
		}

		@font-face {
			font-family: 'Lato';
			src: url('<?php echo esc_url( $fonts ); ?>/Lato-Light.woff2') format('woff2'),
			     url('<?php echo esc_url( $fonts ); ?>/Lato-Light.woff') format('woff');
			font-weight: 300;
			font-style: normal;
		}

		body { margin: 0; padding: 0; width: 100% !important; background-color: #ffffff; }
		img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
		a { text-decoration: none; }
		table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }

		/* Узкие экраны: меняется только раскладка. Кегль, начертания и трекинг
		   заданы инлайново и одинаковы везде — иначе письмо выглядело бы
		   по-разному на телефоне и на компьютере. */
		/* Gmail игнорирует color-scheme и инвертирует цвета принудительно, но
		   помечает изменённые элементы атрибутами data-ogsc (текст) и data-ogsb
		   (фон). По ним возвращаем исходные цвета. */
		[data-ogsb] .minka-wrap,
		u + .minka-body .minka-wrap { background-color: #ffffff !important; }

		[data-ogsc] .minka-title,
		[data-ogsc] .minka-greeting,
		[data-ogsc] .minka-model,
		[data-ogsc] .minka-text { color: #000000 !important; }

		@media screen and (max-width: 620px) {
			.minka-wrap { width: 100% !important; }
			.minka-pad { padding-left: 20px !important; padding-right: 20px !important; }
			.minka-wordmark { width: 125px !important; }
			.minka-footmark { width: 100% !important; }
			.minka-button a { display: block !important; }
		}
	</style>
</head>
<body class="minka-body" style="margin:0; padding:0; background-color:#ffffff;">

<!-- Текст в списке писем: виден в превью, но не на самой странице письма. -->
<div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; mso-hide:all;">
	<?php echo esc_html( $preheader ); ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;">
	<tr>
		<td align="center" style="padding:0;">

			<table role="presentation" class="minka-wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff;">

				<!-- Логотип: 30px сверху -->
				<tr>
					<td align="center" class="minka-pad" style="padding:30px 20px 0 10px;">
						<a href="<?php echo esc_url( $home ); ?>" style="text-decoration:none;">
							<img src="<?php echo esc_url( $images ); ?>/logo.png" alt="MINKA" width="145" height="26" class="minka-wordmark" style="display:block; width:145px; height:auto; border:0;">
						</a>
					</td>
				</tr>

				<!-- Заголовок: 40px от логотипа -->
				<tr>
					<td align="center" class="minka-pad" style="padding:40px 20px 0 10px;">
						<h1 class="minka-title" style="margin:0; font-family:<?php echo esc_attr( $serif ); ?>; font-size:18px; line-height:1.3; letter-spacing:-0.08em; font-weight:400; color:<?php echo esc_attr( $text ); ?>; text-transform:uppercase;">Вам намекнули о подарке!</h1>
					</td>
				</tr>

				<!-- Приветствие: 24px от заголовка -->
				<tr>
					<td align="center" class="minka-pad" style="padding:24px 20px 0 10px;">
						<span class="minka-greeting" style="font-family:<?php echo esc_attr( $sans ); ?>; font-size:15px; font-weight:300; line-height:1.3; color:<?php echo esc_attr( $text ); ?>;">Здравствуйте, <?php echo esc_html( $recipient ); ?>!</span>
					</td>
				</tr>

				<!-- Первый абзац: 12px от приветствия -->
				<tr>
					<td align="center" class="minka-pad" style="padding:12px 20px 0 10px;">
						<p class="minka-text" style="margin:0; font-family:<?php echo esc_attr( $sans ); ?>; font-size:14px; font-weight:300; line-height:1.3; color:<?php echo esc_attr( $text ); ?>;">
							Мы узнали, что <?php echo esc_html( $sender ); ?> мечтает о подарке из салона норковых шуб MINKA. Мы внимательно относимся к желаниям наших клиентов и решили намекнуть вам об этом.
						</p>
					</td>
				</tr>

				<!-- Второй абзац: 16px от первого -->
				<tr>
					<td align="center" class="minka-pad" style="padding:16px 20px 0 20px;">
						<p class="minka-text" style="margin:0; font-family:<?php echo esc_attr( $sans ); ?>; font-size:14px; font-weight:300; line-height:1.3; color:<?php echo esc_attr( $text ); ?>;">
							Дарите тепло и будьте внимательны к мечтам близких! Если у вас возникли вопросы или вы хотите обсудить детали, мы всегда готовы помочь.
						</p>
					</td>
				</tr>

				<?php if ( ! empty( $product['title'] ) ) : ?>
					<!-- Карточка модели -->
					<tr>
						<td align="center" class="minka-pad" style="padding:40px 40px 0 40px;">
							<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;">
								<?php if ( ! empty( $product['image'] ) ) : ?>
									<tr>
										<td align="center" style="padding:0;">
											<a href="<?php echo esc_url( $product['url'] ); ?>" style="text-decoration:none;">
												<img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>" width="300" style="display:block; width:300px; max-width:100%; height:auto;">
											</a>
										</td>
									</tr>
								<?php endif; ?>

								<?php if ( ! empty( $product['sku'] ) ) : ?>
									<!-- Артикул: 10px от картинки -->
									<tr>
										<td align="center" style="padding:10px 0 0 0; font-family:<?php echo esc_attr( $sans ); ?>; font-size:12px; font-weight:300; line-height:1.4; color:<?php echo esc_attr( $muted ); ?>;">
											<?php echo esc_html( $product['sku'] ); ?>
										</td>
									</tr>
								<?php endif; ?>

								<!-- Название: 4px от артикула -->
								<tr>
									<td align="center" style="padding:4px 0 0 0;">
										<a href="<?php echo esc_url( $product['url'] ); ?>" class="minka-model" style="font-family:<?php echo esc_attr( $serif ); ?>; font-size:19px; line-height:1.3; letter-spacing:-0.03em; color:<?php echo esc_attr( $text ); ?>; text-decoration:none; text-transform:uppercase;">«<?php echo esc_html( $product['title'] ); ?>»</a>
									</td>
								</tr>

								<!-- Кнопка: 32px от названия -->
								<tr>
									<td align="center" class="minka-button" style="padding:32px 0 0 0;">
										<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
											<tr>
												<td align="center" style="border:1px solid <?php echo esc_attr( $text ); ?>;">
													<a href="<?php echo esc_url( $product['url'] ); ?>" style="display:inline-block; padding:18px 40px; font-family:<?php echo esc_attr( $sans ); ?>; font-size:14px; line-height:1; color:<?php echo esc_attr( $text ); ?>; text-decoration:none; text-transform:uppercase;">Посмотреть шубу</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				<?php endif; ?>

				<!-- Контакты: иконки, телефон, почта -->
				<tr>
					<td align="center" class="minka-pad" style="padding:40px 40px 0 40px;">
						<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
							<tr>
								<?php foreach ( $contacts['socials'] as $minka_social ) : ?>
									<td align="center" style="padding:0 6px;">
										<?php if ( $minka_social['url'] ) : ?>
											<a href="<?php echo esc_url( $minka_social['url'] ); ?>" style="text-decoration:none;">
										<?php endif; ?>
										<img src="<?php echo esc_url( $images ); ?>/<?php echo esc_attr( $minka_social['icon'] ); ?>" alt="<?php echo esc_attr( $minka_social['label'] ); ?>" width="19" height="19" style="display:block; width:19px; height:19px; border:0;">
										<?php if ( $minka_social['url'] ) : ?>
											</a>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						</table>
					</td>
				</tr>

				<?php if ( ! empty( $contacts['phone'] ) ) : ?>
					<tr>
						<td align="center" class="minka-pad" style="padding:20px 40px 0 40px;">
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $contacts['phone'] ) ); ?>" class="minka-greeting" style="font-family:<?php echo esc_attr( $sans ); ?>; font-size:15px; font-weight:300; line-height:1.3; color:<?php echo esc_attr( $text ); ?>; text-decoration:none;"><?php echo esc_html( $contacts['phone_label'] ? $contacts['phone_label'] : $contacts['phone'] ); ?></a>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( ! empty( $contacts['email'] ) ) : ?>
					<tr>
						<td align="center" class="minka-pad" style="padding:12px 40px 0 40px;">
							<a href="mailto:<?php echo esc_attr( $contacts['email'] ); ?>" class="minka-greeting" style="font-family:<?php echo esc_attr( $sans ); ?>; font-size:15px; font-weight:300; line-height:1.3; color:<?php echo esc_attr( $text ); ?>; text-decoration:none;"><?php echo esc_html( $contacts['email'] ); ?></a>
						</td>
					</tr>
				<?php endif; ?>

				<!-- Логотип во всю ширину -->
				<tr>
					<td align="center" style="padding:50px 0 0 0;">
						<a href="<?php echo esc_url( $home ); ?>" style="text-decoration:none; display:block;">
							<img src="<?php echo esc_url( $images ); ?>/logo-wide.png" alt="MINKA" width="600" class="minka-footmark" style="display:block; width:100%; max-width:600px; height:auto; border:0;">
						</a>
					</td>
				</tr>

			</table>

		</td>
	</tr>
</table>

</body>
</html>
