<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Order_Sync {

	public static function init() {
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'handle_order_status_changed' ), 10, 4 );
	}

	public static function handle_order_status_changed( $order_id, $old_status, $new_status, $order ) {
		if ( ! $order ) {
			return;
		}

		$booking_id = self::get_booking_id_from_order( $order );

		if ( ! $booking_id ) {
			return;
		}

		$current_booking_status = get_post_meta( $booking_id, '_tbs_booking_status', true );

		// Don't overwrite if the booking is already marked as completed by a worker
		if ( 'completed' === $current_booking_status ) {
			return;
		}

		// Don't overwrite if the booking is already marked as cancelled/refunded
		if ( in_array( $current_booking_status, array( 'cancelled', 'refunded' ), true ) ) {
			return;
		}

		// If Order is Paid/Completed, update Booking status to Paid
		if ( in_array( $new_status, array( 'processing', 'completed' ), true ) ) {
			update_post_meta( $booking_id, '_tbs_booking_status', 'paid' );
			update_post_meta( $booking_id, '_tbs_paid_at', current_time( 'mysql' ) );
			update_post_meta( $booking_id, '_tbs_last_synced_order_status', $new_status );
		}

		// If Order is Cancelled/Refunded, update Booking status
		if ( in_array( $new_status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			$status = ( 'refunded' === $new_status ) ? 'refunded' : 'cancelled';
			update_post_meta( $booking_id, '_tbs_booking_status', $status );
			update_post_meta( $booking_id, '_tbs_cancelled_or_refunded_at', current_time( 'mysql' ) );
			update_post_meta( $booking_id, '_tbs_last_synced_order_status', $new_status );
		}
	}

	private static function get_booking_id_from_order( $order ) {
		if ( ! $order ) {
			return 0;
		}

		$booking_id = $order->get_meta( '_tbs_booking_id' );

		if ( ! $booking_id ) {
			$booking_id = get_post_meta( $order->get_id(), '_tbs_booking_id', true );
		}

		$booking_id = absint( $booking_id );

		if ( ! $booking_id || 'tbs_booking' !== get_post_type( $booking_id ) ) {
			return 0;
		}

		return $booking_id;
	}
}