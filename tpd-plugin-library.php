<?php

/**
 * Plugin Name:       Marketplace Library
 * Description:       Allow your themes and plugins to be available to the public.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           1.0
 * Author:            
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       marketplace-library
 *
 * @package           create-block
 */
define('MARKETPLACE_FILE', __FILE__);
define('MARKETPLACE_URL', plugin_dir_url(__FILE__));
define('MARKETPLACE_PATH', plugin_dir_path(__FILE__));
define('MARKETPLACE_TESTMODE', get_option('marketplace_testmode') );

require_once MARKETPLACE_PATH . '/includes/class-marketplace-authorization.php';
require_once MARKETPLACE_PATH . '/includes/class-rest-plugins-controller.php';
require_once MARKETPLACE_PATH . '/includes/class-rest-themes-controller.php';
require_once MARKETPLACE_PATH . '/includes/admin/class-admin.php';

function marketplace_plugin_library_set_wp_is_appication_passwords_available( $available ) {
	
	$dev_enviornments = array( 'local', 'development', 'staging', 'dev', 'localhost', 'test' );
	$needle = explode('.', wp_parse_url( site_url(), PHP_URL_HOST ))[0];
	if (in_array(wp_get_environment_type(), $dev_enviornments)) {
		$available = true;
	}

	if (in_array( $needle, $dev_enviornments)) {
		$available = true;
	}

	return $available;
}
add_filter('wp_is_application_passwords_available', 'marketplace_plugin_library_set_wp_is_appication_passwords_available');

function marketplace_plugin_library_set_wp_is_appication_passwords_available_for_user($available, $user){
	if (!user_can($user, 'manage_options')) {
		$available = true;
	}
	return $available;
}

add_filter('wp_is_application_passwords_available_for_user', 'marketplace_plugin_library_set_wp_is_appication_passwords_available_for_user', 10, 2);