<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Wizard_Order {

	public static function init() {
		add_action( 'wp_ajax_tbs_create_wizard_order', array( __CLASS__, 'create_order' ) );
		add_action( 'wp_ajax_nopriv_tbs_create_wizard_order', array( __CLASS__, 'create_order' ) );
	}

	public static function create_order() {
		if (
			! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tbs_frontend_nonce' )
		) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
		}

		$booking_data = self::sanitize_booking_data( $_POST );

		// Handle Credit Payment
		$user_id = get_current_user_id();
		$used_credit = false;

		if ( ! empty( $booking_data['use_credit'] ) && $user_id ) {
			$current_credits = TBS_Credits::get_available_credits( $user_id );
			
			if ( $current_credits > 0 ) {
				TBS_Credits::deduct_credit( $user_id, 1 );
				$used_credit = true;
				
				$booking_data['total'] = 0;
				$booking_data['base_price'] = 0;
				$booking_data['urgent_surcharge'] = 0;
				$booking_data['extras_total'] = 0;
				$booking_data['payment_method'] = 'Credit';
			} else {
				wp_send_json_error( array( 'message' => 'You do not have enough credits.' ) );
			}
		}

		$validation = self::validate_booking_data( $booking_data );

		if ( true !== $validation ) {
			wp_send_json_error( array( 'message' => $validation ) );
		}

		$product_id = absint( get_option( 'tbs_booking_product_id', 0 ) );

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error(
				array(
					'message' => 'Booking product is not configured. Please set it in Bookings → Settings.',
				)
			);
		}

		try {
			$order = wc_create_order();

			if ( is_wp_error( $order ) ) {
				wp_send_json_error( array( 'message' => $order->get_error_message() ) );
			}

			// Explicitly link the order to the logged-in user
			$current_user_id = get_current_user_id();
			if ( $current_user_id ) {
				$order->set_customer_id( $current_user_id );
			}

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				wp_send_json_error( array( 'message' => 'Booking product could not be found.' ) );
			}

			$item_id = $order->add_product(
				$product,
				1,
				array(
					'subtotal' => $booking_data['total'],
					'total'    => $booking_data['total'],
				)
			);

			if ( $item_id ) {
				$item = $order->get_item( $item_id );

				if ( $item ) {
					$item->add_meta_data( '_tbs_booking_data', $booking_data, true );
					$item->add_meta_data( 'Appointment Type', self::format_appointment_type( $booking_data['appointment_type'] ), true );
					$item->add_meta_data( 'Service Date', $booking_data['preferred_date'], true );
					$item->add_meta_data( 'Route', ! empty( $booking_data['route_title'] ) ? $booking_data['route_title'] : 'Admin confirmation required', true );
					$item->add_meta_data( 'Trashcan', $booking_data['bin_type'] . ' / ' . $booking_data['bin_size'] . ' × ' . $booking_data['quantity'], true );
					$item->add_meta_data( 'Pickup Address', $booking_data['street'] . ' ' . $booking_data['house_number'] . ', ' . $booking_data['postcode'] . ' ' . $booking_data['city'], true );

					if ( $booking_data['urgent_surcharge'] > 0 ) {
						$item->add_meta_data( 'Urgent Surcharge', wc_price( $booking_data['urgent_surcharge'] ), true );
					}

					if ( ! empty( $booking_data['extras'] ) ) {
						$item->add_meta_data( 'Extras', implode( ', ', $booking_data['extras'] ), true );
					}

					if ( ! empty( $booking_data['pickup_note'] ) ) {
						$item->add_meta_data( 'Pickup Note', $booking_data['pickup_note'], true );
					}

					$item->save();
				}
			}

			$order->set_address(
				array(
					'first_name' => $booking_data['first_name'],
					'last_name'  => $booking_data['last_name'],
					'email'      => $booking_data['email'],
					'phone'      => $booking_data['phone'],
					'address_1'  => $booking_data['billing_street'] . ' ' . $booking_data['billing_house_number'],
					'city'       => $booking_data['billing_city'],
					'postcode'   => $booking_data['billing_postcode'],
					'country'    => 'DE',
				),
				'billing'
			);

			$order->update_meta_data( '_tbs_booking_data', $booking_data );
			$order->update_meta_data( '_tbs_appointment_type', $booking_data['appointment_type'] );
			$order->update_meta_data( '_tbs_preferred_date', $booking_data['preferred_date'] );
			$order->update_meta_data( '_tbs_route_id', $booking_data['route_id'] );
			$order->update_meta_data( '_tbs_route_date', $booking_data['preferred_date'] );
			$order->update_meta_data( '_tbs_booking_postcode', $booking_data['postcode'] );
			$order->update_meta_data( '_tbs_urgent_surcharge', $booking_data['urgent_surcharge'] );
			$order->update_meta_data( '_tbs_payment_method_selected', $booking_data['payment_method'] );

			$order->set_customer_note( 'TonniClean booking created from custom booking wizard.' );
			$order->calculate_totals();
			
			if ( $used_credit ) {
				$order->set_status( 'completed' );
				$order->add_order_note( 'Paid using 1 booking credit.' );
			} else {
				$order->set_status( 'pending' );
			}
			
			$order->save();

			$booking_id = self::create_booking_from_order( $order->get_id(), $booking_data );

			if ( ! $booking_id ) {
				wp_send_json_error(
					array(
						'message' => 'Order was created, but booking could not be created.',
					)
				);
			}

			// Trigger the booking created email
			do_action( 'tbs_booking_created', $booking_id );

			$order->update_meta_data( '_tbs_booking_id', $booking_id );
			$order->save();

			if ( $used_credit ) {
				$redirect_url = $order->get_checkout_order_received_url();
			} else {
				$redirect_url = $order->get_checkout_payment_url();
			}

			wp_send_json_success(
				array(
					'message'      => 'Order and booking created successfully.',
					'order_id'     => $order->get_id(),
					'booking_id'   => $booking_id,
					'redirect_url' => $redirect_url,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	private static function create_booking_from_order( $order_id, $booking_data ) {
		$title = 'Booking #' . $order_id . ' - ' . $booking_data['first_name'] . ' ' . $booking_data['last_name'];

		$booking_id = wp_insert_post(
			array(
				'post_type'   => 'tbs_booking',
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $title ),
			)
		);

		if ( is_wp_error( $booking_id ) || ! $booking_id ) {
			return false;
		}

		update_post_meta( $booking_id, '_tbs_customer_name', $booking_data['first_name'] . ' ' . $booking_data['last_name'] );
		update_post_meta( $booking_id, '_tbs_customer_email', $booking_data['email'] );
		update_post_meta( $booking_id, '_tbs_customer_phone', $booking_data['phone'] );

		update_post_meta( $booking_id, '_tbs_order_id', absint( $order_id ) );
		update_post_meta( $booking_id, '_tbs_appointment_type', $booking_data['appointment_type'] );
		update_post_meta( $booking_id, '_tbs_preferred_date', $booking_data['preferred_date'] );
		update_post_meta( $booking_id, '_tbs_service_date', $booking_data['preferred_date'] );
		update_post_meta( $booking_id, '_tbs_street', $booking_data['street'] );
		update_post_meta( $booking_id, '_tbs_house_number', $booking_data['house_number'] );
		update_post_meta( $booking_id, '_tbs_postcode', $booking_data['postcode'] );
		update_post_meta( $booking_id, '_tbs_city', $booking_data['city'] );
		update_post_meta( $booking_id, '_tbs_pickup_note', $booking_data['pickup_note'] );

		update_post_meta( $booking_id, '_tbs_route_id', absint( $booking_data['route_id'] ) );
		update_post_meta( $booking_id, '_tbs_worker_id', self::get_route_worker_id( $booking_data['route_id'] ) );
		update_post_meta( $booking_id, '_tbs_booking_status', self::get_initial_booking_status( $booking_data['appointment_type'] ) );

		update_post_meta( $booking_id, '_tbs_bin_type', $booking_data['bin_type'] );
		update_post_meta( $booking_id, '_tbs_bin_size', $booking_data['bin_size'] );
		update_post_meta( $booking_id, '_tbs_quantity', absint( $booking_data['quantity'] ) );

		update_post_meta( $booking_id, '_tbs_extras', $booking_data['extras'] );
		update_post_meta( $booking_id, '_tbs_base_price', floatval( $booking_data['base_price'] ) );
		update_post_meta( $booking_id, '_tbs_urgent_surcharge', floatval( $booking_data['urgent_surcharge'] ) );
		update_post_meta( $booking_id, '_tbs_extras_total', floatval( $booking_data['extras_total'] ) );
		update_post_meta( $booking_id, '_tbs_total', floatval( $booking_data['total'] ) );

		update_post_meta( $booking_id, '_tbs_payment_method_selected', $booking_data['payment_method'] );
		update_post_meta( $booking_id, '_tbs_booking_data', $booking_data );

		return $booking_id;
	}

	private static function get_initial_booking_status( $appointment_type ) {
		if ( 'urgent_single_trip' === $appointment_type ) {
			return 'urgent_request';
		}

		if ( 'preferred_date' === $appointment_type ) {
			return 'date_request_pending';
		}

		return 'pending_payment';
	}

	private static function format_appointment_type( $type ) {
		if ( 'urgent_single_trip' === $type ) {
			return 'Urgent single trip';
		}

		if ( 'preferred_date' === $type ) {
			return 'Free preferred date';
		}

		return 'Tour';
	}

	private static function get_route_worker_id( $route_id ) {
		$route_id = absint( $route_id );

		if ( ! $route_id ) {
			return '';
		}

		return get_post_meta( $route_id, '_tbs_assigned_worker', true );
	}

	private static function sanitize_booking_data( $raw ) {
		$extras = array();

		if ( isset( $raw['extras'] ) && is_array( $raw['extras'] ) ) {
			foreach ( $raw['extras'] as $extra ) {
				$extras[] = sanitize_text_field( wp_unslash( $extra ) );
			}
		}

		$appointment_type = isset( $raw['appointment_type'] ) ? sanitize_text_field( wp_unslash( $raw['appointment_type'] ) ) : 'tour';

		if ( ! in_array( $appointment_type, array( 'tour', 'urgent_single_trip', 'preferred_date' ), true ) ) {
			$appointment_type = 'tour';
		}

		$route_date     = isset( $raw['route_date'] ) ? sanitize_text_field( wp_unslash( $raw['route_date'] ) ) : '';
		$preferred_date = isset( $raw['preferred_date'] ) ? sanitize_text_field( wp_unslash( $raw['preferred_date'] ) ) : '';

		if ( 'tour' === $appointment_type ) {
			$preferred_date = $route_date;
		}

		return array(
			'street'               => isset( $raw['street'] ) ? sanitize_text_field( wp_unslash( $raw['street'] ) ) : '',
			'house_number'         => isset( $raw['house_number'] ) ? sanitize_text_field( wp_unslash( $raw['house_number'] ) ) : '',
			'postcode'             => isset( $raw['postcode'] ) ? sanitize_text_field( wp_unslash( $raw['postcode'] ) ) : '',
			'city'                 => isset( $raw['city'] ) ? sanitize_text_field( wp_unslash( $raw['city'] ) ) : '',
			'pickup_note'          => isset( $raw['pickup_note'] ) ? sanitize_text_field( wp_unslash( $raw['pickup_note'] ) ) : '',

			'bin_type'             => isset( $raw['bin_type'] ) ? sanitize_text_field( wp_unslash( $raw['bin_type'] ) ) : '',
			'bin_size'             => isset( $raw['bin_size'] ) ? sanitize_text_field( wp_unslash( $raw['bin_size'] ) ) : '',
			'quantity'             => isset( $raw['quantity'] ) ? absint( $raw['quantity'] ) : 1,

			'appointment_type'     => $appointment_type,
			'preferred_date'       => $preferred_date,

			'route_id'             => isset( $raw['route_id'] ) ? absint( $raw['route_id'] ) : 0,
			'route_title'          => isset( $raw['route_title'] ) ? sanitize_text_field( wp_unslash( $raw['route_title'] ) ) : '',
			'route_date'           => $route_date,

			'base_price'           => isset( $raw['base_price'] ) ? floatval( wp_unslash( $raw['base_price'] ) ) : 0,
			'urgent_surcharge'     => isset( $raw['urgent_surcharge'] ) ? floatval( wp_unslash( $raw['urgent_surcharge'] ) ) : 0,
			'extras'               => $extras,
			'extras_total'         => isset( $raw['extras_total'] ) ? floatval( wp_unslash( $raw['extras_total'] ) ) : 0,
			'total'                => isset( $raw['total'] ) ? floatval( wp_unslash( $raw['total'] ) ) : 0,

			'first_name'           => isset( $raw['first_name'] ) ? sanitize_text_field( wp_unslash( $raw['first_name'] ) ) : '',
			'last_name'            => isset( $raw['last_name'] ) ? sanitize_text_field( wp_unslash( $raw['last_name'] ) ) : '',
			'email'                => isset( $raw['email'] ) ? sanitize_email( wp_unslash( $raw['email'] ) ) : '',
			'phone'                => isset( $raw['phone'] ) ? sanitize_text_field( wp_unslash( $raw['phone'] ) ) : '',

			'billing_same'         => isset( $raw['billing_same'] ) && 'yes' === $raw['billing_same'] ? 'yes' : 'no',
			'billing_street'       => isset( $raw['billing_street'] ) ? sanitize_text_field( wp_unslash( $raw['billing_street'] ) ) : '',
			'billing_house_number' => isset( $raw['billing_house_number'] ) ? sanitize_text_field( wp_unslash( $raw['billing_house_number'] ) ) : '',
			'billing_postcode'     => isset( $raw['billing_postcode'] ) ? sanitize_text_field( wp_unslash( $raw['billing_postcode'] ) ) : '',
			'billing_city'         => isset( $raw['billing_city'] ) ? sanitize_text_field( wp_unslash( $raw['billing_city'] ) ) : '',

			'payment_method'       => isset( $raw['payment_method'] ) ? sanitize_text_field( wp_unslash( $raw['payment_method'] ) ) : '',
			'use_credit'           => isset( $raw['use_credit'] ) && 'true' === $raw['use_credit'] ? true : false,
		);
	}

	private static function validate_booking_data( $data ) {
		if ( empty( $data['street'] ) || empty( $data['house_number'] ) || empty( $data['postcode'] ) || empty( $data['city'] ) ) {
			return 'Please complete the pickup address.';
		}

		if ( empty( $data['bin_type'] ) || empty( $data['bin_size'] ) || $data['quantity'] < 1 ) {
			return 'Please complete the trashcan details.';
		}

		if ( 'tour' === $data['appointment_type'] && ( empty( $data['route_id'] ) || empty( $data['preferred_date'] ) ) ) {
			return 'Please select a tour/date.';
		}

		if ( 'urgent_single_trip' === $data['appointment_type'] && empty( $data['preferred_date'] ) ) {
			return 'Please select your preferred urgent date.';
		}

		if ( 'preferred_date' === $data['appointment_type'] && empty( $data['preferred_date'] ) ) {
			return 'Please select your preferred date.';
		}

		if ( $data['total'] <= 0 && empty( $data['use_credit'] ) ) {
			return 'Invalid booking total.';
		}

		if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['email'] ) || empty( $data['phone'] ) ) {
			return 'Please complete the customer details.';
		}

		if ( ! is_email( $data['email'] ) ) {
			return 'Please enter a valid email address.';
		}

		if ( 'no' === $data['billing_same'] ) {
			if ( empty( $data['billing_street'] ) || empty( $data['billing_house_number'] ) || empty( $data['billing_postcode'] ) || empty( $data['billing_city'] ) ) {
				return 'Please complete the billing address.';
			}
		}

		return true;
	}
}