<?php
/**
 * Plugin Name: Trashcan Booking System
 * Description: Custom booking, route, pricing, credits and service documentation system.
 * Version: 1.0.0
 * Author: Developed by Tonniclean
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TBS_VERSION', '1.0.0' );
define( 'TBS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TBS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once TBS_PLUGIN_DIR . 'includes/class-activator.php';
require_once TBS_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook(
	__FILE__,
	array(
		'TBS_Activator',
		'activate'
	)
);

function tbs_run_plugin() {
	$plugin = new TBS_Plugin();
	$plugin->run();
}

add_action(
	'plugins_loaded',
	'tbs_run_plugin'
);