<?php
/**
 * Счётчики посещаемости.
 *
 * Идентификаторы задаются в MINKA → Аналитика, поэтому подключить или
 * отключить счётчик можно без правки кода.
 *
 * Пока посетитель не согласился на статистику, скрипты не выполняются:
 * они отдаются с type="text/plain" и категорией, а запускает их Complianz,
 * когда согласие получено. Тот же подход у Meta Pixel, только он ждёт
 * согласия на маркетинг.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Атрибуты тега скрипта в зависимости от того, управляет ли согласием Complianz.
 *
 * Без плагина согласия блокировать нечего — тогда это обычный скрипт.
 */
function minka_consent_script_atts( $category = 'statistics', $service = '' ) {
	if ( ! minka_has_complianz() ) {
		return '';
	}

	$atts = sprintf( ' type="text/plain" data-category="%s"', esc_attr( $category ) );

	if ( $service ) {
		$atts .= sprintf( ' data-service="%s"', esc_attr( $service ) );
	}

	return $atts;
}

/**
 * Google Analytics 4 и Яндекс.Метрика в <head>.
 */
function minka_analytics_tags() {
	if ( is_admin() ) {
		return;
	}

	// Собственные визиты в статистику не попадают.
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}

	$ga      = trim( (string) minka_option( 'ga_measurement_id' ) );
	$metrika = trim( (string) minka_option( 'metrika_id' ) );

	if ( $ga ) {
		$atts = minka_consent_script_atts( 'statistics', 'google-analytics' );
		?>
		<!-- Google tag (gtag.js) -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga ); ?>"<?php echo $atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></script>
		<script<?php echo $atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '<?php echo esc_js( $ga ); ?>');
		</script>
		<?php
	}

	if ( $metrika ) {
		$atts = minka_consent_script_atts( 'statistics', 'yandex-metrica' );
		?>
		<!-- Yandex.Metrika counter -->
		<script<?php echo $atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			(function(m,e,t,r,i,k,a){
				m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
				m[i].l=1*new Date();
				for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
				k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
			})(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=<?php echo esc_js( $metrika ); ?>', 'ym');

			ym(<?php echo esc_js( $metrika ); ?>, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
		</script>
		<?php

		// Запасной пиксель для браузеров без JavaScript. Он срабатывает сразу,
		// в обход баннера: согласие собирается скриптом, а тут скриптов нет.
		// Поэтому выводим его, только когда согласием никто не управляет.
		if ( ! minka_has_complianz() ) {
			?>
			<noscript><div><img src="https://mc.yandex.ru/watch/<?php echo esc_attr( $metrika ); ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
			<?php
		}
		?>
		<!-- /Yandex.Metrika counter -->
		<?php
	}
}
add_action( 'wp_head', 'minka_analytics_tags', 3 );
