<?php
/**
 * Нормализация телефона в E.164.
 *
 * Нужна дважды: как ключ дедупликации лидов в EspoCRM и как основа хэша `ph`
 * для Meta — там номер должен быть в международном формате, иначе совпадение
 * с профилем не находится.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Приводит номер к виду +375291112233.
 *
 * Возвращает пустую строку, если распознать номер не удалось: лучше оставить
 * поле пустым, чем записать мусор, по которому потом склеятся разные люди.
 *
 * @param string $raw Номер как его ввёл пользователь.
 * @return string
 */
function minka_crm_normalize_phone( $raw ) {
	$raw = (string) $raw;

	$plus   = ( strpos( trim( $raw ), '+' ) === 0 );
	$digits = preg_replace( '/\D+/', '', $raw );

	if ( '' === $digits ) {
		return '';
	}

	// Уже международный: +375..., +7..., +48...
	// Длина проверяется по E.164: меньше восьми или больше пятнадцати цифр —
	// это заведомо не номер, и отправлять его в CRM смысла нет.
	if ( $plus ) {
		$length = strlen( $digits );

		if ( $length < 8 || $length > 15 ) {
			return '';
		}

		return '+' . $digits;
	}

	// 375291112233
	if ( 12 === strlen( $digits ) && strpos( $digits, '375' ) === 0 ) {
		return '+' . $digits;
	}

	// Местная запись через 8: в Беларуси это 8 0XX, в России — 8 9XX.
	if ( 11 === strlen( $digits ) && '8' === $digits[0] ) {
		if ( '0' === $digits[1] ) {
			return '+375' . substr( $digits, 2 );
		}

		return '+7' . substr( $digits, 1 );
	}

	// 79991112233
	if ( 11 === strlen( $digits ) && '7' === $digits[0] ) {
		return '+' . $digits;
	}

	// Без кода страны: 291112233. Коды мобильных операторов Беларуси.
	if ( 9 === strlen( $digits ) && in_array( substr( $digits, 0, 2 ), array( '29', '25', '33', '44' ), true ) ) {
		return '+375' . $digits;
	}

	return '';
}
