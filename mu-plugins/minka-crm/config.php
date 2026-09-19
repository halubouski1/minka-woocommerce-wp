<?php
/**
 * Чтение настроек интеграции.
 *
 * Локально значения приходят переменными окружения из docker-compose. На
 * обычном хостинге контейнеров нет и переменных окружения тоже — там их задают
 * константами в wp-config.php:
 *
 *     define( 'MINKA_ESPO_API_KEY', '...' );
 *
 * Поэтому читаем оба источника. Окружение в приоритете: так поведение в Docker
 * остаётся ровно прежним, а константы подхватываются там, где окружения нет.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Значение настройки.
 *
 * @param string $name    Имя переменной или константы.
 * @param string $default Что вернуть, если нигде не задано.
 * @return string
 */
function minka_crm_config( $name, $default = '' ) {
	$value = getenv( $name );

	if ( false !== $value && '' !== $value ) {
		return (string) $value;
	}

	if ( defined( $name ) ) {
		$value = constant( $name );

		if ( null !== $value && '' !== $value ) {
			return (string) $value;
		}
	}

	return $default;
}

/**
 * Настройка-переключатель.
 *
 * Отдельно от minka_crm_config(), потому что здесь важно отличать «не задано»
 * от «задано как 0»: у строгого режима согласия значение по умолчанию — да.
 *
 * @param string $name    Имя переменной или константы.
 * @param bool   $default Значение, когда нигде не задано.
 * @return bool
 */
function minka_crm_config_flag( $name, $default ) {
	$value = minka_crm_config( $name, null );

	if ( null === $value ) {
		return $default;
	}

	return '0' !== $value && 'false' !== strtolower( $value );
}
