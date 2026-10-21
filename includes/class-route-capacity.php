<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Route_Capacity {

	public static function get_booked_count( $route_id ) {
		$route_id = absint( $route_id );

		if ( ! $route_id ) {
			return 0;
		}

		$active_statuses = array(
			'pending_payment',
			'paid',
			'scheduled',
			'assigned',
			'in_progress',
			'urgent_request',
			'date_request_pending',
		);

		$query = new WP_Query(
			array(
				'post_type'      => 'tbs_booking',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_tbs_route_id',
						'value' => $route_id,
					),
					array(
						'key'     => '_tbs_booking_status',
						'value'   => $active_statuses,
						'compare' => 'IN',
					),
				),
			)
		);

		return absint( $query->found_posts );
	}

	public static function get_capacity( $route_id ) {
		return absint( get_post_meta( absint( $route_id ), '_tbs_capacity', true ) );
	}

	public static function get_remaining_capacity( $route_id ) {
		$capacity = self::get_capacity( $route_id );

		if ( ! $capacity ) {
			return null;
		}

		return max( 0, $capacity - self::get_booked_count( $route_id ) );
	}

	public static function is_full( $route_id ) {
		$remaining = self::get_remaining_capacity( $route_id );

		if ( null === $remaining ) {
			return false;
		}

		return $remaining <= 0;
	}
}