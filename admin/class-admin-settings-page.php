<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Settings_Page {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to access this page.' );
		}

		self::handle_save();

		$booking_product_id = get_option( 'tbs_booking_product_id', '' );
		$dashboard_page_id  = get_option( 'tbs_dashboard_page_id', '' );
		$urgent_surcharge   = get_option( 'tbs_urgent_surcharge', 15 );
		$express_price      = get_option( 'tbs_express_service_price', 10 );
		$photo_price        = get_option( 'tbs_photo_documentation_price', 5 );
		$hygiene_price      = get_option( 'tbs_hygienic_documentation_price', 8 );

		echo '<div class="wrap">';
		echo '<h1>Booking System Settings</h1>';

		echo '<form method="post">';
		wp_nonce_field( 'tbs_save_settings', 'tbs_settings_nonce' );

		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th><label for="tbs_booking_product_id">WooCommerce Booking Product ID</label></th>';
		echo '<td>';
		echo '<input type="number" name="tbs_booking_product_id" id="tbs_booking_product_id" value="' . esc_attr( $booking_product_id ) . '" class="regular-text">';
		echo '<p class="description">Create a simple WooCommerce product like “Trashcan Cleaning Booking” and paste its product ID here.</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_dashboard_page_id">Customer Dashboard Page</label></th>';
		echo '<td>';
		wp_dropdown_pages( array(
			'name'             => 'tbs_dashboard_page_id',
			'id'               => 'tbs_dashboard_page_id',
			'selected'         => $dashboard_page_id,
			'show_option_none' => '— Select Page —',
			'option_none_value' => '',
		) );
		echo '<p class="description">Select the page containing the <code>[tbs_customer_dashboard]</code> shortcode. Used in email links.</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_urgent_surcharge">Urgent Single Trip Surcharge</label></th>';
		echo '<td>';
		echo '<input type="number" step="0.01" min="0" name="tbs_urgent_surcharge" id="tbs_urgent_surcharge" value="' . esc_attr( $urgent_surcharge ) . '" class="regular-text">';
		echo '<p class="description">Extra fee added when customer selects “Urgent single trip”.</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_express_service_price">Express Service Price</label></th>';
		echo '<td><input type="number" step="0.01" min="0" name="tbs_express_service_price" id="tbs_express_service_price" value="' . esc_attr( $express_price ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_photo_documentation_price">Photo Documentation Price</label></th>';
		echo '<td><input type="number" step="0.01" min="0" name="tbs_photo_documentation_price" id="tbs_photo_documentation_price" value="' . esc_attr( $photo_price ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_hygienic_documentation_price">Hygienic Documentation Price</label></th>';
		echo '<td><input type="number" step="0.01" min="0" name="tbs_hygienic_documentation_price" id="tbs_hygienic_documentation_price" value="' . esc_attr( $hygiene_price ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '</table>';

		submit_button( 'Save Settings' );

		echo '</form>';
		echo '</div>';
	}

	private static function handle_save() {
		if ( ! isset( $_POST['tbs_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tbs_settings_nonce'] ) ), 'tbs_save_settings' ) ) {
			return;
		}

		update_option( 'tbs_booking_product_id', isset( $_POST['tbs_booking_product_id'] ) ? absint( $_POST['tbs_booking_product_id'] ) : 0 );
		update_option( 'tbs_dashboard_page_id', isset( $_POST['tbs_dashboard_page_id'] ) ? absint( $_POST['tbs_dashboard_page_id'] ) : 0 );
		update_option( 'tbs_urgent_surcharge', isset( $_POST['tbs_urgent_surcharge'] ) ? floatval( wp_unslash( $_POST['tbs_urgent_surcharge'] ) ) : 0 );
		update_option( 'tbs_express_service_price', isset( $_POST['tbs_express_service_price'] ) ? floatval( wp_unslash( $_POST['tbs_express_service_price'] ) ) : 0 );
		update_option( 'tbs_photo_documentation_price', isset( $_POST['tbs_photo_documentation_price'] ) ? floatval( wp_unslash( $_POST['tbs_photo_documentation_price'] ) ) : 0 );
		update_option( 'tbs_hygienic_documentation_price', isset( $_POST['tbs_hygienic_documentation_price'] ) ? floatval( wp_unslash( $_POST['tbs_hygienic_documentation_price'] ) ) : 0 );

		echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
	}
}