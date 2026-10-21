<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Export {

	public static function init() {
		add_action( 'admin_post_tbs_export_bookings_csv', array( __CLASS__, 'export_bookings_csv' ) );
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to access this page.' );
		}

		$export_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=tbs_export_bookings_csv' ),
			'tbs_export_bookings_csv'
		);

		echo '<div class="wrap">';
		echo '<h1>Export Bookings</h1>';
		echo '<p>Download all bookings as a CSV file for route planning, operations, and reporting.</p>';
		echo '<a href="' . esc_url( $export_url ) . '" class="button button-primary">Download Bookings CSV</a>';
		echo '</div>';
	}

	public static function export_bookings_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission denied.' );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'tbs_export_bookings_csv' ) ) {
			wp_die( 'Invalid export link.' );
		}

		$bookings = get_posts(
			array(
				'post_type'      => 'tbs_booking',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$filename = 'tonniclean-bookings-' . date( 'Y-m-d-H-i-s' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// Add BOM to fix UTF-8 encoding in Excel
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv(
			$output,
			array(
				'Booking ID',
				'Order ID',
				'Customer Name',
				'Email',
				'Phone',
				'Booking Status',
				'Appointment Type',
				'Service Date',
				'Street',
				'Nr',
				'Postcode',
				'City',
				'Route',
				'Worker',
				'Bin Type',
				'Bin Size',
				'Quantity',
				'Extras',
				'Base Price',
				'Urgent Surcharge',
				'Extras Total',
				'Total',
				'Payment Method',
				'Job Completed At',
				'Checklist',
				'Worker Notes',
				'Created',
			)
		);

		foreach ( $bookings as $booking ) {
			$booking_id = $booking->ID;
			$route_id   = get_post_meta( $booking_id, '_tbs_route_id', true );
			$worker_id  = get_post_meta( $booking_id, '_tbs_worker_id', true );
			$extras     = get_post_meta( $booking_id, '_tbs_extras', true );
			$report     = get_post_meta( $booking_id, '_tbs_service_report', true );

			if ( ! is_array( $extras ) ) {
				$extras = array();
			}

			$worker_name = '';
			if ( $worker_id ) {
				$worker = get_user_by( 'id', $worker_id );
				$worker_name = $worker ? $worker->display_name : '';
			}

			$checklist_str = '';
			$notes_str = '';
			$completed_at = '';

			if ( is_array( $report ) ) {
				if ( ! empty( $report['checklist'] ) && is_array( $report['checklist'] ) ) {
					$checklist_str = implode( ', ', $report['checklist'] );
				}
				if ( ! empty( $report['worker_notes'] ) ) {
					$notes_str = $report['worker_notes'];
				}
				if ( ! empty( $report['completed_at'] ) ) {
					$completed_at = $report['completed_at'];
				}
			}

			$order_status = '';
			$order_id = get_post_meta( $booking_id, '_tbs_order_id', true );
			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$order_status = $order->get_status();
				}
			}

			fputcsv(
				$output,
				array(
					$booking_id,
					$order_id,
					get_post_meta( $booking_id, '_tbs_customer_name', true ),
					get_post_meta( $booking_id, '_tbs_customer_email', true ),
					get_post_meta( $booking_id, '_tbs_customer_phone', true ),
					get_post_meta( $booking_id, '_tbs_booking_status', true ),
					get_post_meta( $booking_id, '_tbs_appointment_type', true ),
					get_post_meta( $booking_id, '_tbs_service_date', true ),
					get_post_meta( $booking_id, '_tbs_street', true ),
					get_post_meta( $booking_id, '_tbs_house_number', true ),
					get_post_meta( $booking_id, '_tbs_postcode', true ),
					get_post_meta( $booking_id, '_tbs_city', true ),
					$route_id ? get_the_title( $route_id ) : '',
					$worker_name,
					get_post_meta( $booking_id, '_tbs_bin_type', true ),
					get_post_meta( $booking_id, '_tbs_bin_size', true ),
					get_post_meta( $booking_id, '_tbs_quantity', true ),
					implode( ', ', $extras ),
					get_post_meta( $booking_id, '_tbs_base_price', true ),
					get_post_meta( $booking_id, '_tbs_urgent_surcharge', true ),
					get_post_meta( $booking_id, '_tbs_extras_total', true ),
					get_post_meta( $booking_id, '_tbs_total', true ),
					get_post_meta( $booking_id, '_tbs_payment_method_selected', true ),
					$completed_at,
					$checklist_str,
					$notes_str,
					$booking->post_date,
				)
			);
		}

		fclose( $output );
		exit;
	}
}