<?php
/**
 * Plugin Name: MINKA CRM
 * Description: Атрибуция заявок и передача лидов в EspoCRM.
 * Version:     0.1.0
 *
 * Живёт в mu-plugins намеренно: интеграция не должна зависеть от правок темы.
 * WordPress подключает из mu-plugins только файлы верхнего уровня, поэтому
 * сам код лежит в подпапке, а этот файл её загружает.
 */

defined( 'ABSPATH' ) || exit;

define( 'MINKA_CRM_DIR', __DIR__ . '/minka-crm' );
define( 'MINKA_CRM_URL', content_url( 'mu-plugins/minka-crm' ) );

require_once MINKA_CRM_DIR . '/config.php';
require_once MINKA_CRM_DIR . '/phone.php';
require_once MINKA_CRM_DIR . '/attribution.php';
require_once MINKA_CRM_DIR . '/espo-client.php';
require_once MINKA_CRM_DIR . '/capi.php';
require_once MINKA_CRM_DIR . '/lead-push.php';
require_once MINKA_CRM_DIR . '/telegram.php';
require_once MINKA_CRM_DIR . '/webhook.php';
require_once MINKA_CRM_DIR . '/pixel.php';
