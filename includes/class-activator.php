<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Activator {

	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'tbs_pricing_rules';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			postcode VARCHAR(20) NOT NULL,
			price DECIMAL(10,2) NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (id)
		) {$wpdb->get_charset_collate()};";

		dbDelta( $sql );

		update_option(
			'tbs_version',
			TBS_VERSION
		);

		// Auto-create pages and products for standalone use
		self::create_pages();
		self::create_booking_product();
	}

	private static function create_pages() {
		$pages = array(
			'booking-wizard' => array(
				'title'   => 'Book Now',
				'content' => '[tbs_booking_wizard]',
			),
			'my-dashboard' => array(
				'title'   => 'My Dashboard',
				'content' => '[tbs_customer_dashboard]',
			),
			'worker-portal' => array(
				'title'   => 'Worker Portal',
				'content' => '[tbs_worker_portal]',
			),
		);

		$dashboard_page_id = 0;

		foreach ( $pages as $slug => $page_data ) {
			$page_exists = get_page_by_path( $slug );

			if ( ! $page_exists ) {
				$page_id = wp_insert_post( array(
					'post_title'   => $page_data['title'],
					'post_content' => $page_data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_name'    => $slug,
				) );

				if ( $slug === 'my-dashboard' && $page_id ) {
					$dashboard_page_id = $page_id;
				}
			} else {
				if ( $slug === 'my-dashboard' ) {
					$dashboard_page_id = $page_exists->ID;
				}
			}
		}

		if ( $dashboard_page_id ) {
			update_option( 'tbs_dashboard_page_id', $dashboard_page_id );
		}
	}

	private static function create_booking_product() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return;
		}

		$product_id = get_option( 'tbs_booking_product_id' );

		// If product already exists, do nothing
		if ( $product_id && get_post( $product_id ) ) {
			return;
		}

		$product = new WC_Product_Simple();
		$product->set_name( 'Trashcan Cleaning Booking' );
		$product->set_price( 1 ); // Will be overridden by custom price
		$product->set_regular_price( 1 );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' ); // Hide from shop
		$product->save();

		update_option( 'tbs_booking_product_id', $product->get_id() );
	}
}