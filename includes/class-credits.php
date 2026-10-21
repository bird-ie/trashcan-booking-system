<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Credits {

	public static function init() {
		// Listen to the master status change hook (catches bulk edits, single edits, and payment gateway changes)
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'handle_order_status_change' ), 10, 4 );
	}

	public static function get_available_credits( $user_id ) {
		$credits = get_user_meta( $user_id, '_tbs_available_credits', true );
		return absint( $credits );
	}

	public static function add_credits( $user_id, $amount ) {
		$current = self::get_available_credits( $user_id );
		$new_total = $current + absint( $amount );
		update_user_meta( $user_id, '_tbs_available_credits', $new_total );
		return $new_total;
	}

	public static function deduct_credit( $user_id, $amount = 1 ) {
		$current = self::get_available_credits( $user_id );
		$amount = absint( $amount );

		if ( $current < $amount ) {
			return false;
		}

		$new_total = $current - $amount;
		update_user_meta( $user_id, '_tbs_available_credits', $new_total );
		return true;
	}

	/**
	 * Listen for WooCommerce orders and grant credits.
	 * Fires on ANY status change (bulk edit, single edit, payment).
	 */
	public static function handle_order_status_change( $order_id, $old_status, $new_status, $order ) {
		// Only grant credits when the order is paid (Processing or Completed)
		if ( ! in_array( $new_status, array( 'processing', 'completed' ), true ) ) {
			return;
		}

		if ( ! $order ) {
			return;
		}

		// Prevent granting credits twice for the same order
		if ( $order->get_meta( '_tbs_credits_granted' ) ) {
			return;
		}

		$user_id = $order->get_user_id();

		// If guest checkout, we can't save credits to an account.
		if ( ! $user_id ) {
			$order->add_order_note( 'Credit grant skipped: Guest checkout (no user account).' );
			return;
		}

		// Define which Product IDs grant how many credits.
		// Format: 'Product ID' => 'Number of Credits'
		$credit_products = array(
			32 => 4, // Product ID 32 gives 4 credits
			124 => 8, // Product ID 124 gives 8 credits
		);

		$credits_to_add = 0;

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$qty        = $item->get_quantity();

			if ( isset( $credit_products[ $product_id ] ) ) {
				$credits_to_add += ( $credit_products[ $product_id ] * $qty );
			}
		}

		if ( $credits_to_add > 0 ) {
			self::add_credits( $user_id, $credits_to_add );
			$order->update_meta_data( '_tbs_credits_granted', 'yes' );
			$order->save();
			
			// Add order note for admin visibility
			$order->add_order_note( 'Granted ' . $credits_to_add . ' booking credits to user #' . $user_id . ' (' . $order->get_billing_email() . ')' );
		}
	}
}