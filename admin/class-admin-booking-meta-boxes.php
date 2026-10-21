<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Booking_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_tbs_booking', array( __CLASS__, 'save_booking_meta' ) );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'tbs_booking_details',
			'Booking Details',
			array( __CLASS__, 'render_booking_details_box' ),
			'tbs_booking',
			'normal',
			'high'
		);

		add_meta_box(
			'tbs_booking_wizard_data',
			'Customer Booking Summary',
			array( __CLASS__, 'render_booking_summary_box' ),
			'tbs_booking',
			'normal',
			'default'
		);
	}

	public static function get_booking_statuses() {
		return array(
			'pending_payment'      => 'Pending Payment',
			'paid'                 => 'Paid',
			'urgent_request'       => 'Urgent Request',
			'date_request_pending' => 'Date Request Pending',
			'scheduled'            => 'Scheduled',
			'assigned'             => 'Assigned',
			'in_progress'          => 'In Progress',
			'completed'            => 'Completed',
			'cancelled'            => 'Cancelled',
		);
	}

	public static function format_status( $status ) {
		$statuses = self::get_booking_statuses();

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : ucwords( str_replace( '_', ' ', $status ) );
	}

	public static function format_appointment_type( $type ) {
		if ( 'urgent_single_trip' === $type ) {
			return 'Urgent single trip';
		}

		if ( 'preferred_date' === $type ) {
			return 'Free preferred date';
		}

		return 'Tour';
	}

	public static function render_booking_details_box( $post ) {
		wp_nonce_field( 'tbs_save_booking_meta', 'tbs_booking_meta_nonce' );

		$customer_id      = get_post_meta( $post->ID, '_tbs_customer_id', true );
		$order_id         = get_post_meta( $post->ID, '_tbs_order_id', true );
		$service_date     = get_post_meta( $post->ID, '_tbs_service_date', true );
		$street           = get_post_meta( $post->ID, '_tbs_street', true );
		$house_number     = get_post_meta( $post->ID, '_tbs_house_number', true );
		$postcode         = get_post_meta( $post->ID, '_tbs_postcode', true );
		$city             = get_post_meta( $post->ID, '_tbs_city', true );
		$route_id         = get_post_meta( $post->ID, '_tbs_route_id', true );
		$worker_id        = get_post_meta( $post->ID, '_tbs_worker_id', true );
		$booking_status   = get_post_meta( $post->ID, '_tbs_booking_status', true );
		$appointment_type = get_post_meta( $post->ID, '_tbs_appointment_type', true );
		$preferred_date   = get_post_meta( $post->ID, '_tbs_preferred_date', true );

		if ( empty( $booking_status ) ) {
			$booking_status = 'pending_payment';
		}

		if ( empty( $appointment_type ) ) {
			$appointment_type = 'tour';
		}

		$routes = self::get_matching_routes( $postcode );

		$workers = get_users(
			array(
				'role'   => 'tbs_worker',
				'fields' => array( 'ID', 'display_name', 'user_email' ),
			)
		);

		echo '<table class="form-table">';

		echo '<tr><th colspan="2"><h3>Customer / Order</h3></th></tr>';

		echo '<tr>';
		echo '<th><label for="tbs_customer_id">Customer ID</label></th>';
		echo '<td><input type="number" name="tbs_customer_id" id="tbs_customer_id" value="' . esc_attr( $customer_id ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_order_id">WooCommerce Order ID</label></th>';
		echo '<td><input type="number" name="tbs_order_id" id="tbs_order_id" value="' . esc_attr( $order_id ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_booking_status">Booking Status</label></th>';
		echo '<td>';
		echo '<select name="tbs_booking_status" id="tbs_booking_status">';

		foreach ( self::get_booking_statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $booking_status, $key, false ) . '>';
			echo esc_html( $label );
			echo '</option>';
		}

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '<tr><th colspan="2"><h3>Appointment</h3></th></tr>';

		echo '<tr>';
		echo '<th><label for="tbs_appointment_type">Appointment Type</label></th>';
		echo '<td>';
		echo '<select name="tbs_appointment_type" id="tbs_appointment_type">';
		echo '<option value="tour" ' . selected( $appointment_type, 'tour', false ) . '>Tour</option>';
		echo '<option value="urgent_single_trip" ' . selected( $appointment_type, 'urgent_single_trip', false ) . '>Urgent single trip</option>';
		echo '<option value="preferred_date" ' . selected( $appointment_type, 'preferred_date', false ) . '>Free preferred date</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_service_date">Service Date</label></th>';
		echo '<td><input type="date" name="tbs_service_date" id="tbs_service_date" value="' . esc_attr( $service_date ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_preferred_date">Preferred Date</label></th>';
		echo '<td><input type="date" name="tbs_preferred_date" id="tbs_preferred_date" value="' . esc_attr( $preferred_date ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr><th colspan="2"><h3>Address</h3></th></tr>';

		echo '<tr>';
		echo '<th><label for="tbs_street">Street</label></th>';
		echo '<td><input type="text" name="tbs_street" id="tbs_street" value="' . esc_attr( $street ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_house_number">Nr.</label></th>';
		echo '<td><input type="text" name="tbs_house_number" id="tbs_house_number" value="' . esc_attr( $house_number ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_postcode">Postcode / PLZ</label></th>';
		echo '<td>';
		echo '<input type="text" name="tbs_postcode" id="tbs_postcode" value="' . esc_attr( $postcode ) . '" class="regular-text">';
		echo '<p class="description">Matching routes update automatically when postcode changes.</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_city">City</label></th>';
		echo '<td><input type="text" name="tbs_city" id="tbs_city" value="' . esc_attr( $city ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr><th colspan="2"><h3>Planning</h3></th></tr>';

		echo '<tr>';
		echo '<th><label for="tbs_route_id">Matching Route</label></th>';
		echo '<td>';
		echo '<select name="tbs_route_id" id="tbs_route_id">';
		echo '<option value="">No route assigned</option>';

		foreach ( $routes as $route ) {
			echo '<option value="' . esc_attr( $route->ID ) . '" ' . selected( $route_id, $route->ID, false ) . '>';
			echo esc_html( self::get_route_label( $route->ID ) );
			echo '</option>';
		}

		echo '</select>';
		echo '<p class="description">Routes without a postcode area are available for all postcodes.</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_worker_id">Worker</label></th>';
		echo '<td>';
		echo '<select name="tbs_worker_id" id="tbs_worker_id">';
		echo '<option value="">No worker assigned</option>';

		foreach ( $workers as $worker ) {
			echo '<option value="' . esc_attr( $worker->ID ) . '" ' . selected( $worker_id, $worker->ID, false ) . '>';
			echo esc_html( $worker->display_name . ' (' . $worker->user_email . ')' );
			echo '</option>';
		}

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	public static function render_booking_summary_box( $post ) {
		$booking_data      = get_post_meta( $post->ID, '_tbs_booking_data', true );
		$customer_name     = get_post_meta( $post->ID, '_tbs_customer_name', true );
		$customer_email    = get_post_meta( $post->ID, '_tbs_customer_email', true );
		$customer_phone    = get_post_meta( $post->ID, '_tbs_customer_phone', true );
		$order_id          = get_post_meta( $post->ID, '_tbs_order_id', true );
		$appointment_type  = get_post_meta( $post->ID, '_tbs_appointment_type', true );
		$service_date      = get_post_meta( $post->ID, '_tbs_service_date', true );
		$street            = get_post_meta( $post->ID, '_tbs_street', true );
		$house_number      = get_post_meta( $post->ID, '_tbs_house_number', true );
		$postcode          = get_post_meta( $post->ID, '_tbs_postcode', true );
		$city              = get_post_meta( $post->ID, '_tbs_city', true );
		$pickup_note       = get_post_meta( $post->ID, '_tbs_pickup_note', true );
		$route_id          = get_post_meta( $post->ID, '_tbs_route_id', true );
		$worker_id         = get_post_meta( $post->ID, '_tbs_worker_id', true );
		$bin_type          = get_post_meta( $post->ID, '_tbs_bin_type', true );
		$bin_size          = get_post_meta( $post->ID, '_tbs_bin_size', true );
		$quantity          = get_post_meta( $post->ID, '_tbs_quantity', true );
		$extras            = get_post_meta( $post->ID, '_tbs_extras', true );
		$base_price        = get_post_meta( $post->ID, '_tbs_base_price', true );
		$urgent_surcharge  = get_post_meta( $post->ID, '_tbs_urgent_surcharge', true );
		$extras_total      = get_post_meta( $post->ID, '_tbs_extras_total', true );
		$total             = get_post_meta( $post->ID, '_tbs_total', true );
		$payment_method    = get_post_meta( $post->ID, '_tbs_payment_method_selected', true );
		$booking_status    = get_post_meta( $post->ID, '_tbs_booking_status', true );

		if ( ! is_array( $extras ) ) {
			$extras = array();
		}

		$route_title = $route_id ? get_the_title( $route_id ) : 'Admin confirmation required';

		$worker_name = 'Not assigned';

		if ( $worker_id ) {
			$worker = get_user_by( 'id', $worker_id );
			$worker_name = $worker ? $worker->display_name : 'Not assigned';
		}

		echo '<div class="tbs-admin-summary-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">';

		self::render_summary_card(
			'Customer',
			array(
				'Name'  => $customer_name,
				'Email' => $customer_email,
				'Phone' => $customer_phone,
			)
		);

		self::render_summary_card(
			'Order / Status',
			array(
				'Order ID'          => $order_id ? '#' . $order_id : '—',
				'Booking Status'    => self::format_status( $booking_status ),
				'Payment Method'    => $payment_method,
				'Appointment Type'  => self::format_appointment_type( $appointment_type ),
			)
		);

		self::render_summary_card(
			'Service Address',
			array(
				'Street'      => trim( $street . ' ' . $house_number ),
				'Postcode'    => $postcode,
				'City'        => $city,
				'Pickup Note' => $pickup_note,
			)
		);

		self::render_summary_card(
			'Route / Worker',
			array(
				'Service Date' => $service_date,
				'Route'        => $route_title,
				'Worker'       => $worker_name,
			)
		);

		self::render_summary_card(
			'Trashcan',
			array(
				'Type'     => $bin_type,
				'Size'     => $bin_size,
				'Quantity' => $quantity,
				'Extras'   => ! empty( $extras ) ? implode( ', ', $extras ) : 'No extras',
			)
		);

		self::render_summary_card(
			'Pricing',
			array(
				'Base Price'        => wc_price( floatval( $base_price ) ),
				'Urgent Surcharge'  => wc_price( floatval( $urgent_surcharge ) ),
				'Extras Total'      => wc_price( floatval( $extras_total ) ),
				'Total'             => wc_price( floatval( $total ) ),
			)
		);

		echo '</div>';

		if ( ! empty( $booking_data ) ) {
			echo '<details style="margin-top:20px;">';
			echo '<summary>Raw booking data</summary>';
			echo '<pre style="background:#f6f7f7;padding:12px;overflow:auto;">' . esc_html( print_r( $booking_data, true ) ) . '</pre>';
			echo '</details>';
		}
	}

	private static function render_summary_card( $title, $rows ) {
		echo '<div style="border:1px solid #dcdcde;border-radius:8px;padding:14px;background:#fff;">';
		echo '<h3 style="margin-top:0;">' . esc_html( $title ) . '</h3>';
		echo '<table class="widefat striped">';

		foreach ( $rows as $label => $value ) {
			echo '<tr>';
			echo '<th style="width:150px;">' . esc_html( $label ) . '</th>';
			echo '<td>' . wp_kses_post( $value ? $value : '—' ) . '</td>';
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';
	}

	public static function get_matching_routes( $booking_postcode ) {
		$booking_postcode = self::normalize_postcode( $booking_postcode );

		$routes = get_posts(
			array(
				'post_type'      => 'tbs_route',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $booking_postcode ) ) {
			return $routes;
		}

		$matching_routes = array();

		foreach ( $routes as $route ) {
			$route_area = get_post_meta( $route->ID, '_tbs_postcode_area', true );

			if ( self::route_matches_postcode( $route_area, $booking_postcode ) ) {
				$matching_routes[] = $route;
			}
		}

		return $matching_routes;
	}

	public static function get_route_label( $route_id ) {
		$route_date = get_post_meta( $route_id, '_tbs_route_date', true );
		$route_area = get_post_meta( $route_id, '_tbs_postcode_area', true );

		$label = get_the_title( $route_id );

		if ( ! empty( $route_date ) ) {
			$label .= ' - ' . $route_date;
		}

		if ( ! empty( $route_area ) ) {
			$label .= ' - PLZ: ' . $route_area;
		} else {
			$label .= ' - All postcodes';
		}

		return $label;
	}

	private static function route_matches_postcode( $route_area, $booking_postcode ) {
		$route_area       = trim( $route_area );
		$booking_postcode = self::normalize_postcode( $booking_postcode );

		if ( empty( $route_area ) ) {
			return true;
		}

		$route_area = strtoupper( preg_replace( '/\s+/', '', $route_area ) );

		// Exact Match
		if ( $route_area === $booking_postcode ) {
			return true;
		}

		// Range Match
		if ( false !== strpos( $route_area, '-' ) ) {
			$parts = explode( '-', $route_area );

			if ( 2 === count( $parts ) ) {
				$from = (int) trim( $parts[0] );
				$to   = (int) trim( $parts[1] );
				$input = (int) $booking_postcode;

				return $input >= $from && $input <= $to;
			}
		}

		return false;
	}

	private static function normalize_postcode( $postcode ) {
		$postcode = strtoupper( trim( $postcode ) );
		$postcode = preg_replace( '/\s+/', '', $postcode );

		return sanitize_text_field( $postcode );
	}

	public static function save_booking_meta( $post_id ) {
		if ( ! isset( $_POST['tbs_booking_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tbs_booking_meta_nonce'] ) ), 'tbs_save_booking_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_tbs_customer_id'     => 'tbs_customer_id',
			'_tbs_order_id'        => 'tbs_order_id',
			'_tbs_service_date'    => 'tbs_service_date',
			'_tbs_preferred_date'  => 'tbs_preferred_date',
			'_tbs_appointment_type'=> 'tbs_appointment_type',
			'_tbs_street'          => 'tbs_street',
			'_tbs_house_number'    => 'tbs_house_number',
			'_tbs_postcode'        => 'tbs_postcode',
			'_tbs_city'            => 'tbs_city',
			'_tbs_route_id'        => 'tbs_route_id',
			'_tbs_worker_id'       => 'tbs_worker_id',
			'_tbs_booking_status'  => 'tbs_booking_status',
		);

		foreach ( $fields as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta(
					$post_id,
					$meta_key,
					sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) )
				);
			}
		}
	}
}