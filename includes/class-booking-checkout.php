<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Booking_Checkout {

	public static function init() {
		add_action( 'wp_ajax_tbs_add_booking_to_cart', array( __CLASS__, 'add_booking_to_cart' ) );
		add_action( 'wp_ajax_nopriv_tbs_add_booking_to_cart', array( __CLASS__, 'add_booking_to_cart' ) );

		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'set_booking_cart_price' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_booking_cart_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_booking_order_item_meta' ), 10, 4 );
	}

	public static function add_booking_to_cart() {
		if (
			! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tbs_frontend_nonce' )
		) {
			wp_send_json_error(
				array(
					'message' => 'Security check failed.',
				)
			);
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => 'WooCommerce is not active.',
				)
			);
		}

		if ( null === WC()->cart ) {
			wc_load_cart();
		}

		$product_id = absint( get_option( 'tbs_booking_product_id', 0 ) );

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error(
				array(
					'message' => 'Booking product is not configured. Please set it in Bookings → Settings.',
				)
			);
		}

		$route_id     = isset( $_POST['route_id'] ) ? absint( $_POST['route_id'] ) : 0;
		$route_date   = isset( $_POST['route_date'] ) ? sanitize_text_field( wp_unslash( $_POST['route_date'] ) ) : '';
		$price        = isset( $_POST['price'] ) ? floatval( wp_unslash( $_POST['price'] ) ) : 0;
		$street       = isset( $_POST['street'] ) ? sanitize_text_field( wp_unslash( $_POST['street'] ) ) : '';
		$house_number = isset( $_POST['house_number'] ) ? sanitize_text_field( wp_unslash( $_POST['house_number'] ) ) : '';
		$postcode     = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
		$city         = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
		$notes        = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( ! $route_id || empty( $route_date ) || empty( $street ) || empty( $house_number ) || empty( $postcode ) || empty( $city ) ) {
			wp_send_json_error(
				array(
					'message' => 'Please fill in all required booking fields.',
				)
			);
		}

		if ( $price <= 0 ) {
			wp_send_json_error(
				array(
					'message' => 'Invalid booking price.',
				)
			);
		}

		$booking_data = array(
			'route_id'     => $route_id,
			'route_title'  => get_the_title( $route_id ),
			'route_date'   => $route_date,
			'price'        => $price,
			'street'       => $street,
			'house_number' => $house_number,
			'postcode'     => $postcode,
			'city'         => $city,
			'notes'        => $notes,
		);

		WC()->cart->empty_cart();

		$cart_item_key = WC()->cart->add_to_cart(
			$product_id,
			1,
			0,
			array(),
			array(
				'tbs_booking_data' => $booking_data,
			)
		);

		if ( ! $cart_item_key ) {
			wp_send_json_error(
				array(
					'message' => 'Could not add booking to cart.',
				)
			);
		}

		wp_send_json_success(
			array(
				'message'      => 'Booking added to cart.',
				'checkout_url' => wc_get_checkout_url(),
			)
		);
	}

	public static function set_booking_cart_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( empty( $cart->get_cart() ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['tbs_booking_data']['price'] ) ) {
				$cart_item['data']->set_price( floatval( $cart_item['tbs_booking_data']['price'] ) );
			}
		}
	}

	public static function display_booking_cart_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['tbs_booking_data'] ) ) {
			return $item_data;
		}

		$data = $cart_item['tbs_booking_data'];

		$item_data[] = array(
			'name'  => 'Service Date',
			'value' => esc_html( $data['route_date'] ),
		);

		$item_data[] = array(
			'name'  => 'Route',
			'value' => esc_html( $data['route_title'] ),
		);

		$item_data[] = array(
			'name'  => 'Address',
			'value' => esc_html( $data['street'] . ' ' . $data['house_number'] . ', ' . $data['postcode'] . ' ' . $data['city'] ),
		);

		if ( ! empty( $data['notes'] ) ) {
			$item_data[] = array(
				'name'  => 'Notes',
				'value' => esc_html( $data['notes'] ),
			);
		}

		return $item_data;
	}

	public static function save_booking_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['tbs_booking_data'] ) ) {
			return;
		}

		$data = $values['tbs_booking_data'];

		$item->add_meta_data( '_tbs_booking_data', $data, true );

		$item->add_meta_data( 'Service Date', $data['route_date'], true );
		$item->add_meta_data( 'Route', $data['route_title'], true );
		$item->add_meta_data( 'Street', $data['street'], true );
		$item->add_meta_data( 'Nr.', $data['house_number'], true );
		$item->add_meta_data( 'Postcode', $data['postcode'], true );
		$item->add_meta_data( 'City', $data['city'], true );

		if ( ! empty( $data['notes'] ) ) {
			$item->add_meta_data( 'Notes', $data['notes'], true );
		}
	}
}