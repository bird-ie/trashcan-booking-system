<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Menu {

	public static function init() {
		add_action(
			'admin_menu',
			array(
				__CLASS__,
				'register_menu_pages'
			)
		);
	}

	public static function register_menu_pages() {
		add_submenu_page(
			'edit.php?post_type=tbs_booking',
			__( 'Pricing Rules', 'trashcan-booking-system' ),
			__( 'Pricing Rules', 'trashcan-booking-system' ),
			'manage_options',
			'tbs-pricing-rules',
			array(
				'TBS_Admin_Pricing_Page',
				'render'
			)
		);

		add_submenu_page(
			'edit.php?post_type=tbs_booking',
			__( 'Settings', 'trashcan-booking-system' ),
			__( 'Settings', 'trashcan-booking-system' ),
			'manage_options',
			'tbs-settings',
			array(
				'TBS_Admin_Settings_Page',
				'render'
			)
		);

		add_submenu_page(
			'edit.php?post_type=tbs_booking',
			__( 'Export Bookings', 'trashcan-booking-system' ),
			__( 'Export Bookings', 'trashcan-booking-system' ),
			'manage_options',
			'tbs-export-bookings',
			array(
				'TBS_Admin_Export',
				'render_page'
			)
		);

		add_submenu_page(
			'edit.php?post_type=tbs_booking',
			__( 'System Status', 'trashcan-booking-system' ),
			__( 'System Status', 'trashcan-booking-system' ),
			'manage_options',
			'tbs-system-status',
			array(
				'TBS_WooCommerce_Foundation',
				'render_status_page'
			)
		);
	}
}