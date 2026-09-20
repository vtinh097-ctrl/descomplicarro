<?php
/**
 * DESCOMPLICARRO — bootstrap do tema.
 * Apenas inclui os módulos de inc/ — nenhuma lógica fica solta aqui.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DC_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/admin-fields.php';
require_once get_template_directory() . '/inc/repeater.php';
require_once get_template_directory() . '/inc/nav-walker.php';
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/options-page.php';
require_once get_template_directory() . '/inc/meta-boxes.php';
require_once get_template_directory() . '/inc/activation.php';
