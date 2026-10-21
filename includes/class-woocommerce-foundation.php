<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_WooCommerce_Foundation {

	public static function init() {
		// WooCommerce order/booking sync will be added in Milestone 2.
	}

	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	public static function is_subscriptions_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	public static function render_status_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'trashcan-booking-system' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>System Status</h1>';

		echo '<table class="widefat striped" style="max-width:700px;">';
		echo '<tbody>';

		echo '<tr>';
		echo '<th>WooCommerce</th>';
		echo '<td>' . ( self::is_woocommerce_active() ? 'Active' : 'Not Active' ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th>WooCommerce Subscriptions</th>';
		echo '<td>' . ( self::is_subscriptions_active() ? 'Active' : 'Not Active' ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th>Worker Role</th>';
		echo '<td>' . ( get_role( 'tbs_worker' ) ? 'TC Worker role exists' : 'Missing' ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th>Booking CPT</th>';
		echo '<td>' . ( post_type_exists( 'tbs_booking' ) ? 'Registered' : 'Missing' ) . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th>Route CPT</th>';
		echo '<td>' . ( post_type_exists( 'tbs_route' ) ? 'Registered' : 'Missing' ) . '</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';

		echo '</div>';
	}
}