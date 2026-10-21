<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Post_Types {

	public static function register() {
		self::register_booking_cpt();
		self::register_route_cpt();
	}

	private static function register_booking_cpt() {

		register_post_type(
			'tbs_booking',
			array(
				'labels' => array(
					'name'          => 'Bookings',
					'singular_name' => 'Booking',
					'add_new_item'  => 'Add New Booking',
					'edit_item'     => 'Edit Booking',
					'menu_name'     => 'Bookings',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-calendar-alt',
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'exclude_from_search' => true,
				'show_in_rest'        => true,
			)
		);

	}

	private static function register_route_cpt() {

		register_post_type(
			'tbs_route',
			array(
				'labels' => array(
					'name'          => 'Routes',
					'singular_name' => 'Route',
					'add_new_item'  => 'Add New Route',
					'edit_item'     => 'Edit Route',
					'menu_name'     => 'Routes',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=tbs_booking',
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'exclude_from_search' => true,
				'show_in_rest'        => true,
			)
		);

	}
}